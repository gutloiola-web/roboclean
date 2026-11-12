<?php
/**
 * ============================================================================
 * PAINEL ADMINISTRATIVO - ROBOCLEAN (EVENTO SÍNTESE)
 * ============================================================================
 * Gerencia Equipe (membros), Galeria (fotos/vídeos) e Mural de Mensagens.
 */

require_once dirname(__DIR__) . '/includes/db.php';

// Verifica autenticação
if (empty($_SESSION['admin_logado'])) {
    header('Location: login.php');
    exit;
}

$alertaTipo  = '';
$alertaTexto = '';

/**
 * Valida e move um arquivo enviado por upload com segurança.
 * Aceita apenas jpg, png, webp (e mp4 quando $permitirVideo = true).
 */
function processarUploadSeguro(array $file, bool $permitirVideo = false): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'erro' => 'Parâmetros de arquivo inválidos.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'erro' => 'Erro no envio do arquivo (código ' . (int) $file['error'] . ').'];
    }

    // Mapeamento estrito de MIME real -> extensão
    $mimesPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if ($permitirVideo) {
        $mimesPermitidos['video/mp4'] = 'mp4';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($file['tmp_name']);

    if (!isset($mimesPermitidos[$mimeReal])) {
        $formatos = $permitirVideo ? 'JPG, PNG, WEBP ou MP4' : 'JPG, PNG ou WEBP';
        return ['ok' => false, 'erro' => "Formato de arquivo não permitido. Envie apenas {$formatos}."];
    }

    $extensaoFinal = $mimesPermitidos[$mimeReal];
    $ehVideo = ($extensaoFinal === 'mp4');

    // Verifica extensão original do nome como camada adicional
    $extOriginal = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $extValidas  = $permitirVideo ? ['jpg', 'jpeg', 'png', 'webp', 'mp4'] : ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extOriginal, $extValidas, true)) {
        return ['ok' => false, 'erro' => 'Extensão do arquivo inválida.'];
    }

    // Verifica limite de tamanho
    $limiteBytes = $ehVideo ? UPLOAD_MAX_VIDEO : UPLOAD_MAX_IMG;
    if ($file['size'] <= 0 || $file['size'] > $limiteBytes) {
        $limiteMb = round($limiteBytes / (1024 * 1024));
        return ['ok' => false, 'erro' => "O arquivo excede o tamanho máximo permitido de {$limiteMb} MB."];
    }

    // Garante que a pasta de destino exista
    $pastaDestino = dirname(__DIR__) . '/assets/img/uploads';
    if (!is_dir($pastaDestino)) {
        mkdir($pastaDestino, 0755, true);
    }

    // Gera nome aleatório seguro
    $nomeArquivoGerado = 'rc_' . bin2hex(random_bytes(12)) . '.' . $extensaoFinal;
    $caminhoCompleto   = $pastaDestino . '/' . $nomeArquivoGerado;

    if (!move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
        return ['ok' => false, 'erro' => 'Falha ao salvar o arquivo enviado no servidor.'];
    }

    return [
        'ok'      => true,
        'caminho' => 'assets/img/uploads/' . $nomeArquivoGerado,
        'tipo'    => $ehVideo ? 'video' : 'foto',
    ];
}

/**
 * Obtém o formato (JPG, PNG, WEBP, SVG, MP4), dimensões em pixels,
 * orientação/proporção e tamanho em KB/MB de uma foto ou vídeo.
 */
function obterDetalhesFormatoMidia(string $caminhoRelativo): array
{
    $caminhoLimpo  = preg_replace('/\?.*$/', '', $caminhoRelativo);
    $caminhoFisico = dirname(__DIR__) . '/' . ltrim($caminhoLimpo, '/');
    $ext           = strtoupper(pathinfo($caminhoLimpo, PATHINFO_EXTENSION));
    if ($ext === 'JPEG') {
        $ext = 'JPG';
    }

    $resultado = [
        'formato'   => $ext !== '' ? $ext : 'N/A',
        'dimensoes' => '',
        'proporcao' => '',
        'tamanho'   => '',
        'resumo'    => $ext !== '' ? $ext : 'Sem arquivo',
    ];

    if (!is_file($caminhoFisico)) {
        return $resultado;
    }

    $bytes = @filesize($caminhoFisico);
    if ($bytes !== false) {
        $resultado['tamanho'] = $bytes >= 1048576
            ? number_format($bytes / 1048576, 2, ',', '.') . ' MB'
            : number_format(max(1, round($bytes / 1024)), 0, ',', '.') . ' KB';
    }

    if (in_array($ext, ['JPG', 'PNG', 'WEBP'], true)) {
        $infoImg = @getimagesize($caminhoFisico);
        if ($infoImg && !empty($infoImg[0]) && !empty($infoImg[1])) {
            $largura = (int) $infoImg[0];
            $altura  = (int) $infoImg[1];
            $resultado['dimensoes'] = "{$largura}×{$altura}px";

            if (abs($largura - $altura) <= 12) {
                $resultado['proporcao'] = 'Quadrada (1:1)';
            } elseif ($largura > $altura) {
                $resultado['proporcao'] = 'Horizontal';
            } else {
                $resultado['proporcao'] = 'Vertical';
            }
        }
    } elseif ($ext === 'SVG') {
        $resultado['dimensoes'] = 'Vetorial';
    }

    $partes = array_filter([
        $resultado['formato'],
        $resultado['dimensoes'],
        $resultado['proporcao'],
        $resultado['tamanho'],
    ]);

    $resultado['resumo'] = implode(' • ', $partes);
    return $resultado;
}

// ============================================================================
// PROCESSAMENTO DE AÇÕES POST DO PAINEL
// ============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_disponivel && $pdo) {
    $csrf = $_POST['csrf_token'] ?? '';
    $acao = $_POST['acao'] ?? '';

    if (!validarCsrfToken($csrf)) {
        $alertaTipo  = 'erro';
        $alertaTexto = 'Token CSRF inválido. Por favor, tente novamente.';
    } else {
        try {
            // ----------------------------------------------------------------
            // 1. AÇÕES DE EQUIPE (MEMBROS)
            // ----------------------------------------------------------------
            if ($acao === 'adicionar_membro') {
                $nome   = trim((string) ($_POST['nome'] ?? ''));
                $funcao = trim((string) ($_POST['funcao'] ?? ''));
                $ordem  = (int) ($_POST['ordem'] ?? 1);
                $foto   = 'assets/img/membro_placeholder.svg';

                if ($nome === '' || $funcao === '') {
                    $alertaTipo  = 'erro';
                    $alertaTexto = 'Informe o nome e a função do integrante.';
                } else {
                    if (!empty($_FILES['foto']['name'])) {
                        $up = processarUploadSeguro($_FILES['foto'], false);
                        if (!$up['ok']) {
                            throw new RuntimeException($up['erro']);
                        }
                        $foto = $up['caminho'];
                    }

                    $stmt = $pdo->prepare('INSERT INTO membros (nome, funcao, foto, ordem) VALUES (:nome, :funcao, :foto, :ordem)');
                    $stmt->execute([
                        ':nome'   => mb_substr($nome, 0, 100, 'UTF-8'),
                        ':funcao' => mb_substr($funcao, 0, 150, 'UTF-8'),
                        ':foto'   => $foto,
                        ':ordem'  => $ordem,
                    ]);

                    $alertaTipo  = 'sucesso';
                    $alertaTexto = 'Integrante adicionado à equipe com sucesso!';
                }
            } elseif ($acao === 'editar_membro') {
                $id     = (int) ($_POST['id'] ?? 0);
                $nome   = trim((string) ($_POST['nome'] ?? ''));
                $funcao = trim((string) ($_POST['funcao'] ?? ''));
                $ordem  = (int) ($_POST['ordem'] ?? 1);

                if ($id <= 0 || $nome === '' || $funcao === '') {
                    $alertaTipo  = 'erro';
                    $alertaTexto = 'Dados inválidos para edição do membro.';
                } else {
                    if (!empty($_FILES['foto']['name'])) {
                        $up = processarUploadSeguro($_FILES['foto'], false);
                        if (!$up['ok']) {
                            throw new RuntimeException($up['erro']);
                        }
                        $stmt = $pdo->prepare('UPDATE membros SET nome = :nome, funcao = :funcao, foto = :foto, ordem = :ordem WHERE id = :id');
                        $stmt->execute([
                            ':nome'   => mb_substr($nome, 0, 100, 'UTF-8'),
                            ':funcao' => mb_substr($funcao, 0, 150, 'UTF-8'),
                            ':foto'   => $up['caminho'],
                            ':ordem'  => $ordem,
                            ':id'     => $id,
                        ]);
                    } else {
                        $stmt = $pdo->prepare('UPDATE membros SET nome = :nome, funcao = :funcao, ordem = :ordem WHERE id = :id');
                        $stmt->execute([
                            ':nome'   => mb_substr($nome, 0, 100, 'UTF-8'),
                            ':funcao' => mb_substr($funcao, 0, 150, 'UTF-8'),
                            ':ordem'  => $ordem,
                            ':id'     => $id,
                        ]);
                    }

                    $alertaTipo  = 'sucesso';
                    $alertaTexto = 'Dados do integrante atualizados com sucesso!';
                }
            } elseif ($acao === 'excluir_membro') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare('DELETE FROM membros WHERE id = :id');
                    $stmt->execute([':id' => $id]);
                    $alertaTipo  = 'sucesso';
                    $alertaTexto = 'Integrante removido da equipe.';
                }
            }

            // ----------------------------------------------------------------
            // 2. AÇÕES DE FOTO PRINCIPAL DO ROBÔ E GALERIA (FOTOS E VÍDEOS)
            // ----------------------------------------------------------------
            elseif ($acao === 'alterar_foto_robo') {
                if (empty($_FILES['foto_robo']['name'])) {
                    $alertaTipo  = 'erro';
                    $alertaTexto = 'Selecione uma foto (JPG, PNG ou WEBP) para o robô principal.';
                } else {
                    $up = processarUploadSeguro($_FILES['foto_robo'], false);
                    if (!$up['ok']) {
                        throw new RuntimeException($up['erro']);
                    }
                    $origem  = dirname(__DIR__) . '/' . $up['caminho'];
                    $destino = dirname(__DIR__) . '/assets/img/robo.jpg';
                    if (@copy($origem, $destino)) {
                        @unlink($origem);
                        $alertaTipo  = 'sucesso';
                        $alertaTexto = 'Foto principal do robô (Início) atualizada com sucesso!';
                    } else {
                        throw new RuntimeException('Não foi possível atualizar a foto principal do robô.');
                    }
                }
            } elseif ($acao === 'adicionar_galeria') {
                $titulo = trim((string) ($_POST['titulo'] ?? ''));
                if ($titulo === '' || empty($_FILES['arquivo']['name'])) {
                    $alertaTipo  = 'erro';
                    $alertaTexto = 'Informe o título e selecione uma foto (JPG, PNG, WEBP) ou vídeo (MP4).';
                } else {
                    $up = processarUploadSeguro($_FILES['arquivo'], true);
                    if (!$up['ok']) {
                        throw new RuntimeException($up['erro']);
                    }

                    $stmt = $pdo->prepare('INSERT INTO galeria (titulo, arquivo, tipo, data) VALUES (:titulo, :arquivo, :tipo, NOW())');
                    $stmt->execute([
                        ':titulo'  => mb_substr($titulo, 0, 150, 'UTF-8'),
                        ':arquivo' => $up['caminho'],
                        ':tipo'    => $up['tipo'],
                    ]);

                    $alertaTipo  = 'sucesso';
                    $alertaTexto = 'Item adicionado à galeria com sucesso!';
                }
            } elseif ($acao === 'editar_galeria') {
                $id     = (int) ($_POST['id'] ?? 0);
                $titulo = trim((string) ($_POST['titulo'] ?? ''));
                if ($id > 0 && $titulo !== '') {
                    if (!empty($_FILES['arquivo']['name'])) {
                        $up = processarUploadSeguro($_FILES['arquivo'], true);
                        if (!$up['ok']) {
                            throw new RuntimeException($up['erro']);
                        }
                        $stmt = $pdo->prepare('UPDATE galeria SET titulo = :titulo, arquivo = :arquivo, tipo = :tipo WHERE id = :id');
                        $stmt->execute([
                            ':titulo'  => mb_substr($titulo, 0, 150, 'UTF-8'),
                            ':arquivo' => $up['caminho'],
                            ':tipo'    => $up['tipo'],
                            ':id'      => $id,
                        ]);
                        $alertaTipo  = 'sucesso';
                        $alertaTexto = 'Foto e título do item da galeria atualizados com sucesso!';
                    } else {
                        $stmt = $pdo->prepare('UPDATE galeria SET titulo = :titulo WHERE id = :id');
                        $stmt->execute([
                            ':titulo' => mb_substr($titulo, 0, 150, 'UTF-8'),
                            ':id'     => $id,
                        ]);
                        $alertaTipo  = 'sucesso';
                        $alertaTexto = 'Título do item da galeria atualizado!';
                    }
                }
            } elseif ($acao === 'excluir_galeria') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare('DELETE FROM galeria WHERE id = :id');
                    $stmt->execute([':id' => $id]);
                    $alertaTipo  = 'sucesso';
                    $alertaTexto = 'Item removido da galeria.';
                }
            }

            // ----------------------------------------------------------------
            // 3. AÇÕES DE MODERAÇÃO DO MURAL DE MENSAGENS
            // ----------------------------------------------------------------
            elseif ($acao === 'aprovar_mensagem') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE mensagens SET aprovada = 1 WHERE id = :id');
                    $stmt->execute([':id' => $id]);
                    $alertaTipo  = 'sucesso';
                    $alertaTexto = 'Mensagem aprovada! Ela já está visível no mural público.';
                }
            } elseif ($acao === 'ocultar_mensagem') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE mensagens SET aprovada = 0 WHERE id = :id');
                    $stmt->execute([':id' => $id]);
                    $alertaTipo  = 'sucesso';
                    $alertaTexto = 'Mensagem ocultada do mural público.';
                }
            } elseif ($acao === 'excluir_mensagem') {
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $stmt = $pdo->prepare('DELETE FROM mensagens WHERE id = :id');
                    $stmt->execute([':id' => $id]);
                    $alertaTipo  = 'sucesso';
                    $alertaTexto = 'Mensagem excluída permanentemente.';
                }
            }
        } catch (RuntimeException $re) {
            $alertaTipo  = 'erro';
            $alertaTexto = $re->getMessage();
        } catch (Throwable $e) {
            $alertaTipo  = 'erro';
            $alertaTexto = 'Ocorreu um erro ao processar a solicitação no banco de dados.';
        }
    }
}

// Consultas para preencher o painel
$listaMembros   = [];
$listaGaleria   = [];
$listaMensagens = [];
$totalVisitas   = 0;

if ($db_disponivel && $pdo) {
    $listaMembros = buscarMembros($pdo);
    $listaGaleria = buscarGaleria($pdo);

    $stmtMsgs = $pdo->prepare('SELECT id, nome, texto, aprovada, data FROM mensagens ORDER BY aprovada ASC, data DESC, id DESC');
    $stmtMsgs->execute();
    $listaMensagens = $stmtMsgs->fetchAll();

    $stmtVis = $pdo->prepare('SELECT total FROM visitas WHERE id = :id LIMIT 1');
    $stmtVis->execute([':id' => 1]);
    $rowVis = $stmtVis->fetch();
    $totalVisitas = $rowVis ? (int) $rowVis['total'] : 0;
}

// Conta mensagens pendentes
$pendentesCount = 0;
foreach ($listaMensagens as $m) {
    if ((int) $m['aprovada'] === 0) {
        $pendentesCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo — <?= e(SITE_NOME) ?> | <?= e(EVENTO_NOME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= (int) @filemtime(dirname(__DIR__) . '/assets/css/style.css') ?>">
</head>
<body class="admin-body">

    <!-- Topo do Painel Admin -->
    <header class="admin-topbar">
        <div class="container admin-topbar-inner">
            <div class="admin-brand">
                <div>
                    <strong>Painel <?= e(SITE_NOME) ?></strong>
                    <span>Logado como: <em><?= e($_SESSION['admin_usuario'] ?? 'admin') ?></em></span>
                </div>
            </div>

            <div class="admin-top-actions">
                <a href="../index.php" class="btn btn-secondary btn-sm" target="_blank">Ver Site Público</a>
                <a href="logout.php" class="btn btn-danger btn-sm">Sair</a>
            </div>
        </div>
    </header>

    <main class="container admin-main">

        <?php if ($alertaTexto !== ''): ?>
            <div class="alert-box alert-<?= e($alertaTipo) ?>" role="alert">
                <?= e($alertaTexto) ?>
            </div>
        <?php endif; ?>

        <!-- Resumo Rápido -->
        <div class="admin-summary-grid">
            <div class="admin-stat-box">
                <span>Integrantes na Equipe</span>
                <strong><?= count($listaMembros) ?></strong>
            </div>
            <div class="admin-stat-box">
                <span>Itens na Galeria</span>
                <strong><?= count($listaGaleria) ?></strong>
            </div>
            <div class="admin-stat-box">
                <span>Mensagens Pendentes</span>
                <strong><?= $pendentesCount ?></strong>
            </div>
            <div class="admin-stat-box">
                <span>Visitas Totais</span>
                <strong><?= number_format($totalVisitas, 0, ',', '.') ?></strong>
            </div>
        </div>

        <!-- ==================================================================
             1. MODERAÇÃO DO MURAL DE MENSAGENS
             ================================================================== -->
        <section class="admin-card-section" id="admin-mensagens">
            <div class="admin-section-header">
                <h2>Mural de Mensagens (Aprovar ou Excluir)</h2>
                <p>Apenas mensagens marcadas como <strong>Aprovadas</strong> aparecem na página pública do evento Síntese.</p>
            </div>

            <?php if (empty($listaMensagens)): ?>
                <p class="admin-empty-text">Nenhuma mensagem recebida até o momento.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Autor</th>
                                <th>Mensagem</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listaMensagens as $msg): ?>
                                <?php $aprovada = ((int) $msg['aprovada'] === 1); ?>
                                <tr>
                                    <td>
                                        <?php if ($aprovada): ?>
                                            <span class="badge-status badge-approved">Aprovada</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-pending">Pendente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= e($msg['nome']) ?></strong></td>
                                    <td><?= nl2br(e($msg['texto'])) ?></td>
                                    <td><?= e(date('d/m/Y H:i', strtotime($msg['data']))) ?></td>
                                    <td class="admin-actions-cell">
                                        <?php if (!$aprovada): ?>
                                            <form action="index.php" method="POST" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">
                                                <input type="hidden" name="acao" value="aprovar_mensagem">
                                                <input type="hidden" name="id" value="<?= (int) $msg['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-xs">Aprovar</button>
                                            </form>
                                        <?php else: ?>
                                            <form action="index.php" method="POST" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">
                                                <input type="hidden" name="acao" value="ocultar_mensagem">
                                                <input type="hidden" name="id" value="<?= (int) $msg['id'] ?>">
                                                <button type="submit" class="btn btn-secondary btn-xs">Ocultar</button>
                                            </form>
                                        <?php endif; ?>

                                        <form action="index.php" method="POST" class="inline-form" onsubmit="return confirm('Tem certeza que deseja excluir esta mensagem?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">
                                            <input type="hidden" name="acao" value="excluir_mensagem">
                                            <input type="hidden" name="id" value="<?= (int) $msg['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-xs">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- ==================================================================
             2. GERENCIAR EQUIPE (ADICIONAR, EDITAR E REMOVER MEMBROS)
             ================================================================== -->
        <section class="admin-card-section" id="admin-equipe">
            <div class="admin-section-header">
                <h2>Gerenciar Equipe</h2>
                <p>Adicione, edite ou remova os integrantes que aparecem na seção Equipe.</p>
            </div>

            <!-- Formulário para Adicionar Novo Integrante -->
            <form action="index.php" method="POST" enctype="multipart/form-data" class="admin-add-form">
                <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">
                <input type="hidden" name="acao" value="adicionar_membro">

                <h3>Adicionar Integrante</h3>
                <div class="admin-form-grid">
                    <div class="form-group">
                        <label for="novoNome">Nome *</label>
                        <input type="text" id="novoNome" name="nome" required maxlength="100" placeholder="Ex.: Lucas">
                    </div>

                    <div class="form-group">
                        <label for="novaFuncao">Função no Projeto *</label>
                        <input type="text" id="novaFuncao" name="funcao" required maxlength="150" placeholder="Ex.: Montagem e Pesquisa">
                    </div>

                    <div class="form-group">
                        <label for="novaOrdem">Ordem de Exibição</label>
                        <input type="number" id="novaOrdem" name="ordem" value="<?= count($listaMembros) + 1 ?>" min="1" max="99">
                    </div>

                    <div class="form-group">
                        <label for="novaFoto">Foto (JPG, PNG, WEBP até 5MB - Opcional)</label>
                        <input type="file" id="novaFoto" name="foto" class="js-file-inspector" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <small class="format-hint-text">Formatos aceitos: <strong>JPG, PNG, WEBP</strong> &middot; Exibição no site: <strong>Foto inteira (sem cortes)</strong></small>
                        <div class="live-file-preview" hidden></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Salvar Novo Integrante</button>
            </form>

            <!-- Lista de Integrantes Atuais com Edição e Exclusão -->
            <div class="admin-items-list">
                <?php foreach ($listaMembros as $membro): ?>
                    <?php
                    $caminhoFotoMembro = $membro['foto'] ?: 'assets/img/membro_placeholder.svg';
                    $infoMembro = obterDetalhesFormatoMidia($caminhoFotoMembro);
                    ?>
                    <div class="admin-member-row">
                        <div class="admin-media-preview-col">
                            <img
                                src="../<?= e($caminhoFotoMembro) ?>"
                                alt="<?= e($membro['nome']) ?>"
                                class="admin-thumb admin-thumb-square"
                                onerror="this.onerror=null;this.src='../assets/img/membro_placeholder.svg';"
                            >
                            <span class="badge-photo-format" title="Formato e detalhes do arquivo atual">
                                Formato: <?= e($infoMembro['resumo']) ?>
                            </span>
                        </div>

                        <form action="index.php" method="POST" enctype="multipart/form-data" class="admin-edit-inline-form">
                            <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">
                            <input type="hidden" name="acao" value="editar_membro">
                            <input type="hidden" name="id" value="<?= (int) $membro['id'] ?>">

                            <input type="text" name="nome" value="<?= e($membro['nome']) ?>" required maxlength="100" aria-label="Nome do membro">
                            <input type="text" name="funcao" value="<?= e($membro['funcao']) ?>" required maxlength="150" aria-label="Função do membro">
                            <input type="number" name="ordem" value="<?= (int) $membro['ordem'] ?>" min="1" max="99" class="input-small" aria-label="Ordem">
                            <div class="inline-file-wrap">
                                <input type="file" name="foto" class="js-file-inspector" accept=".jpg,.jpeg,.png,.webp" aria-label="Trocar foto">
                                <div class="live-file-preview" hidden></div>
                            </div>

                            <button type="submit" class="btn btn-secondary btn-xs">Salvar Edição</button>
                        </form>

                        <form action="index.php" method="POST" onsubmit="return confirm('Remover este integrante da equipe?');">
                            <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">
                            <input type="hidden" name="acao" value="excluir_membro">
                            <input type="hidden" name="id" value="<?= (int) $membro['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-xs">Remover</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ==================================================================
             3. FOTO PRINCIPAL DO ROBÔ (INÍCIO) E GALERIA DE ROBÔS LEGO SPIKE
             ================================================================== -->
        <section class="admin-card-section" id="admin-galeria">
            <div class="admin-section-header">
                <h2>Fotos do Robô LEGO SPIKE (Início e Galeria)</h2>
                <p>Visualize o formato, as dimensões e a proporção de cada foto ou envie novas imagens para o site.</p>
            </div>

            <!-- Formulário para Alterar a Foto Principal do Robô (assets/img/robo.jpg) -->
            <?php $infoFotoHero = obterDetalhesFormatoMidia('assets/img/robo.jpg'); ?>
            <form action="index.php" method="POST" enctype="multipart/form-data" class="admin-add-form">
                <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">
                <input type="hidden" name="acao" value="alterar_foto_robo">

                <h3>Foto Principal do Robô (Seção Início)</h3>
                <div class="admin-member-row">
                    <div class="admin-media-preview-col">
                        <img
                            src="../assets/img/robo.jpg?v=<?= (int) @filemtime(dirname(__DIR__) . '/assets/img/robo.jpg') ?>"
                            alt="Foto principal atual do robô"
                            class="admin-thumb admin-thumb-hero"
                            onerror="this.onerror=null;this.src='../assets/img/galeria_prototipo.svg';"
                        >
                        <span class="badge-photo-format">
                            Formato atual: <?= e($infoFotoHero['resumo']) ?>
                        </span>
                    </div>

                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label for="fotoRoboPrincipal">Substituir foto principal (JPG, PNG ou WEBP até 5MB)</label>
                        <input type="file" id="fotoRoboPrincipal" name="foto_robo" class="js-file-inspector" required accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <small class="format-hint-text">Formatos aceitos: <strong>JPG, PNG, WEBP</strong> &middot; Exibição no topo: <strong>Foto inteira (sem cortes)</strong></small>
                        <div class="live-file-preview" hidden></div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Alterar Foto Principal</button>
                </div>
            </form>

            <!-- Formulário para Adicionar Novo Item na Galeria -->
            <form action="index.php" method="POST" enctype="multipart/form-data" class="admin-add-form">
                <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">
                <input type="hidden" name="acao" value="adicionar_galeria">

                <h3>Adicionar Nova Foto ou Vídeo na Galeria</h3>
                <div class="admin-form-grid">
                    <div class="form-group">
                        <label for="tituloGaleria">Título / Legenda *</label>
                        <input type="text" id="tituloGaleria" name="titulo" required maxlength="150" placeholder="Ex.: Robô LEGO SPIKE recolhendo tampinhas">
                    </div>

                    <div class="form-group">
                        <label for="arquivoGaleria">Arquivo (JPG, PNG, WEBP ou MP4) *</label>
                        <input type="file" id="arquivoGaleria" name="arquivo" class="js-file-inspector" required accept=".jpg,.jpeg,.png,.webp,.mp4,image/jpeg,image/png,image/webp,video/mp4">
                        <small class="format-hint-text">Formatos aceitos: <strong>JPG, PNG, WEBP, MP4</strong> &middot; Exibição na galeria: <strong>Foto inteira (sem cortes)</strong></small>
                        <div class="live-file-preview" hidden></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Enviar para a Galeria</button>
            </form>

            <!-- Lista de Itens Atuais da Galeria (com formato detalhado, miniatura proporcional e troca de foto) -->
            <div class="admin-items-list">
                <?php foreach ($listaGaleria as $item): ?>
                    <?php $infoGaleria = obterDetalhesFormatoMidia($item['arquivo']); ?>
                    <div class="admin-member-row">
                        <div class="admin-media-preview-col">
                            <?php if ($item['tipo'] === 'video'): ?>
                                <div class="admin-thumb admin-thumb-landscape admin-video-badge">MP4</div>
                            <?php else: ?>
                                <img
                                    src="../<?= e($item['arquivo']) ?>"
                                    alt="<?= e($item['titulo']) ?>"
                                    class="admin-thumb admin-thumb-landscape"
                                    onerror="this.onerror=null;this.src='../assets/img/galeria_prototipo.svg';"
                                >
                            <?php endif; ?>
                            <span class="badge-photo-format" title="Formato, dimensões e tamanho do arquivo">
                                Formato: <?= e($infoGaleria['resumo']) ?>
                            </span>
                        </div>

                        <form action="index.php" method="POST" enctype="multipart/form-data" class="admin-edit-inline-form">
                            <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">
                            <input type="hidden" name="acao" value="editar_galeria">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">

                            <input type="text" name="titulo" value="<?= e($item['titulo']) ?>" required maxlength="150" aria-label="Título da foto ou vídeo">
                            <div class="inline-file-wrap">
                                <input type="file" name="arquivo" class="js-file-inspector" accept=".jpg,.jpeg,.png,.webp,.mp4" aria-label="Substituir foto ou vídeo">
                                <div class="live-file-preview" hidden></div>
                            </div>
                            <span class="badge-status"><?= e($infoGaleria['formato']) ?></span>

                            <button type="submit" class="btn btn-secondary btn-xs">Salvar Alteração</button>
                        </form>

                        <form action="index.php" method="POST" onsubmit="return confirm('Excluir este item da galeria?');">
                            <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">
                            <input type="hidden" name="acao" value="excluir_galeria">
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-xs">Excluir</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </main>

    <!-- Script do Painel Admin: mostra instantaneamente o formato, dimensões e prévia da foto selecionada -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var inputs = document.querySelectorAll('.js-file-inspector');

        function formatarTamanho(bytes) {
            if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2).replace('.', ',') + ' MB';
            }
            return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        }

        inputs.forEach(function (input) {
            input.addEventListener('change', function () {
                var container = input.parentElement.querySelector('.live-file-preview');
                if (!container) return;

                if (!input.files || !input.files[0]) {
                    container.hidden = true;
                    container.innerHTML = '';
                    return;
                }

                var file = input.files[0];
                var ext = (file.name.split('.').pop() || '').toUpperCase();
                if (ext === 'JPEG') ext = 'JPG';
                var tamanhoStr = formatarTamanho(file.size);

                container.hidden = false;
                container.innerHTML = '<span class="badge-photo-format">Selecionado: <strong>' + ext + '</strong> &bull; ' + tamanhoStr + '</span>';

                if (file.type && file.type.indexOf('image/') === 0) {
                    var url = URL.createObjectURL(file);
                    var img = new Image();
                    img.onload = function () {
                        var w = img.naturalWidth;
                        var h = img.naturalHeight;
                        var orientacao = Math.abs(w - h) <= 12 ? 'Quadrada (1:1)' : (w > h ? 'Horizontal' : 'Vertical');
                        container.innerHTML =
                            '<div class="live-preview-box">' +
                                '<img src="' + url + '" alt="Prévia da foto selecionada" class="live-preview-img">' +
                                '<span class="badge-photo-format">Formato selecionado: <strong>' + ext + '</strong> &bull; ' + w + '&times;' + h + 'px (' + orientacao + ') &bull; ' + tamanhoStr + '</span>' +
                            '</div>';
                    };
                    img.src = url;
                }
            });
        });
    });
    </script>

</body>
</html>

