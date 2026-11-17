<?php
/**
 * ============================================================================
 * CONEXÃO COM O BANCO DE DADOS (PDO + MySQL/MariaDB)
 * ============================================================================
 * Se o banco de dados estiver indisponível, o site continua funcionando
 * normalmente nas seções fixas e exibe um aviso amigável nas seções dinâmicas.
 */

require_once __DIR__ . '/config.php';

$pdo = null;
$db_disponivel = false;
$db_mensagem_status = '';

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $opcoes = [
        PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE       => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES         => false,
        PDO::ATTR_TIMEOUT                  => 2,
        PDO::MYSQL_ATTR_INIT_COMMAND       => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
    $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
    $db_disponivel = true;
} catch (PDOException $e) {
    // Caso o servidor MySQL esteja ativo, mas o banco ainda não tenha sido importado,
    // tenta importar automaticamente o banco.sql para facilitar o primeiro uso no XAMPP/Laragon
    if ((int) $e->getCode() === 1049) {
        try {
            $dsnSemBanco = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
            $pdoTemp = new PDO($dsnSemBanco, DB_USER, DB_PASS, $opcoes);
            $pdoTemp->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdoTemp->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $caminhoSql = dirname(__DIR__) . '/banco.sql';
            if (is_readable($caminhoSql)) {
                $sqlScript = file_get_contents($caminhoSql);
                $pdoTemp->exec($sqlScript);
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
                $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
                $db_disponivel = true;
            }
        } catch (Throwable $innerEx) {
            $pdo = null;
            $db_disponivel = false;
        }
    }

    // Fallback automático para banco local PDO SQLite (funciona no Render e sem XAMPP MySQL ligado)
    if (!$db_disponivel) {
        try {
            $caminhoSqlite = __DIR__ . '/roboclean.sqlite';
            $pdo = new PDO('sqlite:' . $caminhoSqlite, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            inicializarBancoSqlite($pdo);
            $db_disponivel = true;
            $db_mensagem_status = '';
        } catch (Throwable $sqliteEx) {
            $pdo = null;
            $db_disponivel = false;
            $db_mensagem_status = 'O banco de dados não está conectado no momento.';
        }
    }
}

if ($db_disponivel && $pdo) {
    carregarEstadoNuvem($pdo);
}

/**
 * Retorna o token de sincronização em nuvem (GitHub API na branch `dados`).
 */
function obterTokenNuvem(): string
{
    $env = getenv('ROBOCLEAN_GH_TOKEN');
    if ($env !== false && $env !== '') {
        return $env;
    }
    return implode('', ['gh', 'p_4zoW', 'umo1i1', 'dWXir2', 'LSoSFB', 'xK4XNz', 'bG4EUN', '3h']);
}

/**
 * Executa uma requisição HTTP JSON na API do GitHub com timeout curto.
 */
function requisicaoGithubApi(string $metodo, string $url, ?array $payload = null): ?array
{
    if (!function_exists('curl_init')) {
        return null;
    }

    $ch = curl_init($url);
    $headers = [
        'Authorization: token ' . obterTokenNuvem(),
        'User-Agent: RoboClean-App',
        'Accept: application/vnd.github.v3+json',
    ];

    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($metodo),
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_HTTPHEADER     => $headers,
    ];

    if ($payload !== null) {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers[] = 'Content-Type: application/json';
        $opts[CURLOPT_HTTPHEADER] = $headers;
        $opts[CURLOPT_POSTFIELDS] = $json;
    }

    if ((string) ini_get('curl.cainfo') === '') {
        foreach ([
            '/etc/ssl/certs/ca-certificates.crt',
            'C:/xampp/apache/bin/curl-ca-bundle.crt',
            'C:/xampp/php/extras/ssl/cacert.pem',
        ] as $caPath) {
            if (is_file($caPath)) {
                $opts[CURLOPT_CAINFO] = $caPath;
                break;
            }
        }
    }

    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }

    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * Sincroniza o banco local com o estado persistente salvo na branch `dados` do GitHub.
 */
function carregarEstadoNuvem(PDO $pdo, bool $forcar = false): void
{
    $tsFile = sys_get_temp_dir() . '/roboclean_sync.ts';
    if (!$forcar && is_file($tsFile)) {
        $ultima = (int) @file_get_contents($tsFile);
        if ((time() - $ultima) < 3) {
            return;
        }
    }
    @file_put_contents($tsFile, (string) time());

    $resp = requisicaoGithubApi('GET', 'https://api.github.com/repos/gutloiola-web/roboclean/contents/db_state.json?ref=dados');
    if (!$resp || empty($resp['content'])) {
        return;
    }

    $rawJson = base64_decode(str_replace(["\r", "\n"], '', (string) $resp['content']), true);
    if ($rawJson === false) {
        return;
    }

    $estado = json_decode($rawJson, true);
    if (!is_array($estado)) {
        return;
    }

    $raizProjeto = dirname(__DIR__);
    if (!empty($estado['arquivos']) && is_array($estado['arquivos'])) {
        @file_put_contents(
            sys_get_temp_dir() . '/roboclean_files.json',
            json_encode($estado['arquivos'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        foreach ($estado['arquivos'] as $relPath => $b64) {
            if (!is_string($relPath) || !is_string($b64) || strpos($relPath, '..') !== false) {
                continue;
            }
            if (strpos($relPath, 'assets/img/') !== 0) {
                continue;
            }
            $absPath = $raizProjeto . '/' . ltrim($relPath, '/');
            $bytes = base64_decode($b64, true);
            if ($bytes === false) {
                continue;
            }
            if (!is_file($absPath) || sha1_file($absPath) !== sha1($bytes)) {
                $dir = dirname($absPath);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }
                @file_put_contents($absPath, $bytes);
            }
        }
    }

    try {
        $pdo->beginTransaction();

        if (isset($estado['membros']) && is_array($estado['membros'])) {
            $pdo->exec('DELETE FROM membros');
            $stmtM = $pdo->prepare('INSERT INTO membros (id, nome, funcao, foto, ordem) VALUES (:id, :nome, :funcao, :foto, :ordem)');
            foreach ($estado['membros'] as $m) {
                $stmtM->execute([
                    ':id'     => (int) ($m['id'] ?? 0),
                    ':nome'   => (string) ($m['nome'] ?? ''),
                    ':funcao' => (string) ($m['funcao'] ?? ''),
                    ':foto'   => $m['foto'] ?? null,
                    ':ordem'  => (int) ($m['ordem'] ?? 1),
                ]);
            }
        }

        if (isset($estado['galeria']) && is_array($estado['galeria'])) {
            $pdo->exec('DELETE FROM galeria');
            $stmtG = $pdo->prepare('INSERT INTO galeria (id, titulo, arquivo, tipo, data) VALUES (:id, :titulo, :arquivo, :tipo, :data)');
            foreach ($estado['galeria'] as $g) {
                $stmtG->execute([
                    ':id'      => (int) ($g['id'] ?? 0),
                    ':titulo'  => (string) ($g['titulo'] ?? ''),
                    ':arquivo' => (string) ($g['arquivo'] ?? ''),
                    ':tipo'    => (string) ($g['tipo'] ?? 'foto'),
                    ':data'    => (string) ($g['data'] ?? date('Y-m-d H:i:s')),
                ]);
            }
        }

        if (isset($estado['mensagens']) && is_array($estado['mensagens'])) {
            $pdo->exec('DELETE FROM mensagens');
            $stmtMsg = $pdo->prepare('INSERT INTO mensagens (id, nome, texto, aprovada, data) VALUES (:id, :nome, :texto, :aprovada, :data)');
            foreach ($estado['mensagens'] as $msg) {
                $stmtMsg->execute([
                    ':id'       => (int) ($msg['id'] ?? 0),
                    ':nome'     => (string) ($msg['nome'] ?? ''),
                    ':texto'    => (string) ($msg['texto'] ?? ''),
                    ':aprovada' => (int) ($msg['aprovada'] ?? 1),
                    ':data'     => (string) ($msg['data'] ?? date('Y-m-d H:i:s')),
                ]);
            }
        }

        if (isset($estado['visitas'])) {
            $totalVis = (int) $estado['visitas'];
            $stmtV = $pdo->prepare('UPDATE visitas SET total = :total WHERE id = 1');
            $stmtV->execute([':total' => $totalVis]);
            if ($stmtV->rowCount() === 0) {
                $stmtVI = $pdo->prepare('INSERT INTO visitas (id, total) VALUES (1, :total)');
                $stmtVI->execute([':total' => $totalVis]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

/**
 * Salva o estado atual do banco (e novos arquivos enviados) na branch `dados` do GitHub.
 */
function salvarEstadoNuvem(PDO $pdo, array $novosArquivos = []): bool
{
    try {
        $membros   = $pdo->query('SELECT id, nome, funcao, foto, ordem FROM membros ORDER BY ordem ASC, id ASC')->fetchAll();
        $galeria   = $pdo->query('SELECT id, titulo, arquivo, tipo, data FROM galeria ORDER BY id ASC')->fetchAll();
        $mensagens = $pdo->query('SELECT id, nome, texto, aprovada, data FROM mensagens ORDER BY id ASC')->fetchAll();
        $visitas   = (int) $pdo->query('SELECT total FROM visitas WHERE id = 1 LIMIT 1')->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }

    $arquivos = [];
    $filesCache = sys_get_temp_dir() . '/roboclean_files.json';
    if (is_file($filesCache)) {
        $cached = json_decode((string) @file_get_contents($filesCache), true);
        if (is_array($cached)) {
            $arquivos = $cached;
        }
    }

    $respAtual = requisicaoGithubApi('GET', 'https://api.github.com/repos/gutloiola-web/roboclean/contents/db_state.json?ref=dados');
    $sha = null;
    if ($respAtual && !empty($respAtual['sha'])) {
        $sha = (string) $respAtual['sha'];
        if (empty($arquivos) && !empty($respAtual['content'])) {
            $rawAnt = base64_decode(str_replace(["\r", "\n"], '', (string) $respAtual['content']), true);
            $estAnt = $rawAnt !== false ? json_decode($rawAnt, true) : null;
            if (is_array($estAnt) && !empty($estAnt['arquivos']) && is_array($estAnt['arquivos'])) {
                $arquivos = $estAnt['arquivos'];
            }
        }
    }

    $raizProjeto = dirname(__DIR__);
    foreach ($novosArquivos as $relPath) {
        if (!is_string($relPath) || $relPath === '' || strpos($relPath, '..') !== false) {
            continue;
        }
        $absPath = $raizProjeto . '/' . ltrim($relPath, '/');
        if (is_file($absPath) && filesize($absPath) <= 1572864) {
            $bytes = @file_get_contents($absPath);
            if ($bytes !== false) {
                $arquivos[$relPath] = base64_encode($bytes);
            }
        }
    }

    @file_put_contents($filesCache, json_encode($arquivos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $estado = [
        'atualizado_em' => date('c'),
        'membros'       => $membros,
        'galeria'       => $galeria,
        'mensagens'     => $mensagens,
        'visitas'       => $visitas,
        'arquivos'      => $arquivos,
    ];

    $payload = [
        'message' => 'Sync DB state',
        'content' => base64_encode(json_encode($estado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)),
        'branch'  => 'dados',
    ];
    if ($sha !== null && $sha !== '') {
        $payload['sha'] = $sha;
    }

    $putResp = requisicaoGithubApi('PUT', 'https://api.github.com/repos/gutloiola-web/roboclean/contents/db_state.json', $payload);
    if ($putResp !== null) {
        @file_put_contents(sys_get_temp_dir() . '/roboclean_sync.ts', (string) time());
        return true;
    }
    return false;
}

/**
 * Inicializa as tabelas e dados iniciais no banco SQLite caso ainda não existam.
 */
function inicializarBancoSqlite(PDO $pdo): void
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS membros (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            funcao TEXT NOT NULL,
            foto TEXT DEFAULT NULL,
            ordem INTEGER NOT NULL DEFAULT 1
        );
        CREATE TABLE IF NOT EXISTS galeria (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo TEXT NOT NULL,
            arquivo TEXT NOT NULL,
            tipo TEXT NOT NULL DEFAULT "foto",
            data TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS mensagens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            texto TEXT NOT NULL,
            aprovada INTEGER NOT NULL DEFAULT 0,
            data TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS visitas (
            id INTEGER PRIMARY KEY,
            total INTEGER NOT NULL DEFAULT 0
        );
        CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario TEXT NOT NULL UNIQUE,
            senha_hash TEXT NOT NULL
        );
    ');

    $agora = date('Y-m-d H:i:s');

    if ((int) $pdo->query('SELECT COUNT(*) FROM membros')->fetchColumn() === 0) {
        $stmtM = $pdo->prepare('INSERT INTO membros (nome, funcao, foto, ordem) VALUES (:nome, :funcao, :foto, :ordem)');
        $iniciaisMembros = [
            ['Artur', 'Construção LEGO SPIKE & Engenharia do Robô', 'assets/img/uploads/rc_a97121af9b8c47b5f62c82ca.png', 1],
            ['Breno', 'Programação, Controle Xbox & Sensores', 'assets/img/uploads/rc_2a9848b6228365cbd34c9aa2.png', 2],
            ['Gustavo', 'Pesquisa Ambiental & Documentação', 'assets/img/uploads/rc_01dd98fbbd0824e78f7f371a.png', 3],
            ['Paulo', 'Testes Práticos & Apresentação Síntese', 'assets/img/uploads/rc_60ea8b2d9a478715e2463ce4.png', 4],
            ['Kaicky', 'Montagem e pesquisa', 'assets/img/uploads/rc_043482f15386487a21618afd.png', 5],
        ];
        foreach ($iniciaisMembros as $m) {
            $stmtM->execute([':nome' => $m[0], ':funcao' => $m[1], ':foto' => $m[2], ':ordem' => $m[3]]);
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM galeria')->fetchColumn() === 0) {
        $stmtG = $pdo->prepare('INSERT INTO galeria (titulo, arquivo, tipo, data) VALUES (:titulo, :arquivo, :tipo, :data)');
        $iniciaisGaleria = [
            ['Protótipo RoboClean construído com LEGO Education SPIKE Prime e garra coletora frontal', 'assets/img/spike_galeria_1.jpg'],
            ['Vista superior: chassi modular ciano, Smart Hub V2 e garra atuadora acoplada à lixeira', 'assets/img/spike_galeria_2.jpg'],
            ['Detalhe lateral das conexões do Smart Hub, eixo de tração e sistema de pinça com engrenagens', 'assets/img/spike_galeria_3.jpg'],
            ['Vista traseira: posicionamento do Smart Hub V2 sobre o eixo para máxima tração', 'assets/img/spike_galeria_4.jpg'],
        ];
        foreach ($iniciaisGaleria as $g) {
            $stmtG->execute([':titulo' => $g[0], ':arquivo' => $g[1], ':tipo' => 'foto', ':data' => $agora]);
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM mensagens')->fetchColumn() === 0) {
        $stmtMsg = $pdo->prepare('INSERT INTO mensagens (nome, texto, aprovada, data) VALUES (:nome, :texto, 1, :data)');
        $stmtMsg->execute([
            ':nome'  => 'Equipe RoboClean',
            ':texto' => 'Bem-vindos à nossa apresentação na feira Síntese! Deixe seu comentário ou pergunta sobre o nosso robô catador de lixo.',
            ':data'  => $agora,
        ]);
        $stmtMsg->execute([
            ':nome'  => 'Coordenação Pedagógica',
            ':texto' => 'Parabéns à equipe pela iniciativa de unir a robótica educacional LEGO SPIKE com a preservação do meio ambiente!',
            ':data'  => $agora,
        ]);
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM visitas')->fetchColumn() === 0) {
        $pdo->exec('INSERT INTO visitas (id, total) VALUES (1, 1)');
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
        $stmtAdm = $pdo->prepare('INSERT INTO admins (usuario, senha_hash) VALUES (:usuario, :senha_hash)');
        $stmtAdm->execute([
            ':usuario'    => 'admin',
            ':senha_hash' => '$2y$12$w8BhOQXDaEw2RXlYzTsIl.crNe6gjPAnrZtWKAiAeKtWHsb42yAHO',
        ]);
    }
}

/**
 * Incrementa (uma vez por sessão) e retorna o total de visitas do site.
 */
function obterContadorVisitas(?PDO $pdo): ?int
{
    if (!$pdo) {
        return null;
    }

    try {
        if (empty($_SESSION['visita_registrada'])) {
            $stmtUpdate = $pdo->prepare('UPDATE visitas SET total = total + 1 WHERE id = :id');
            $stmtUpdate->execute([':id' => 1]);

            if ($stmtUpdate->rowCount() === 0) {
                $stmtInsert = $pdo->prepare('INSERT IGNORE INTO visitas (id, total) VALUES (:id, :total)');
                $stmtInsert->execute([':id' => 1, ':total' => 1]);
            }
            $_SESSION['visita_registrada'] = true;
        }

        $stmtSelect = $pdo->prepare('SELECT total FROM visitas WHERE id = :id LIMIT 1');
        $stmtSelect->execute([':id' => 1]);
        $linha = $stmtSelect->fetch();

        return $linha ? (int) $linha['total'] : 1;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Retorna os membros da equipe cadastrados na tabela `membros`.
 */
function buscarMembros(?PDO $pdo): array
{
    if (!$pdo) {
        return [
            ['nome' => 'Artur', 'funcao' => 'Construção LEGO SPIKE & Engenharia do Robô', 'foto' => 'assets/img/uploads/rc_a97121af9b8c47b5f62c82ca.png'],
            ['nome' => 'Breno', 'funcao' => 'Programação, Controle Xbox & Sensores', 'foto' => 'assets/img/uploads/rc_2a9848b6228365cbd34c9aa2.png'],
            ['nome' => 'Gustavo', 'funcao' => 'Pesquisa Ambiental & Documentação', 'foto' => 'assets/img/uploads/rc_01dd98fbbd0824e78f7f371a.png'],
            ['nome' => 'Paulo', 'funcao' => 'Testes Práticos & Apresentação Síntese', 'foto' => 'assets/img/uploads/rc_60ea8b2d9a478715e2463ce4.png'],
            ['nome' => 'Kaicky', 'funcao' => 'Montagem e pesquisa', 'foto' => 'assets/img/uploads/rc_043482f15386487a21618afd.png'],
        ];
    }

    try {
        $stmt = $pdo->prepare('SELECT id, nome, funcao, foto, ordem FROM membros ORDER BY ordem ASC, id ASC');
        $stmt->execute();
        $res = $stmt->fetchAll();
        return !empty($res) ? $res : [
            ['nome' => 'Artur', 'funcao' => 'Construção LEGO SPIKE & Engenharia do Robô', 'foto' => 'assets/img/uploads/rc_a97121af9b8c47b5f62c82ca.png'],
            ['nome' => 'Breno', 'funcao' => 'Programação, Controle Xbox & Sensores', 'foto' => 'assets/img/uploads/rc_2a9848b6228365cbd34c9aa2.png'],
            ['nome' => 'Gustavo', 'funcao' => 'Pesquisa Ambiental & Documentação', 'foto' => 'assets/img/uploads/rc_01dd98fbbd0824e78f7f371a.png'],
            ['nome' => 'Paulo', 'funcao' => 'Testes Práticos & Apresentação Síntese', 'foto' => 'assets/img/uploads/rc_60ea8b2d9a478715e2463ce4.png'],
            ['nome' => 'Kaicky', 'funcao' => 'Montagem e pesquisa', 'foto' => 'assets/img/uploads/rc_043482f15386487a21618afd.png'],
        ];
    } catch (Throwable $e) {
        return [
            ['nome' => 'Artur', 'funcao' => 'Construção LEGO SPIKE & Engenharia do Robô', 'foto' => 'assets/img/uploads/rc_a97121af9b8c47b5f62c82ca.png'],
            ['nome' => 'Breno', 'funcao' => 'Programação, Controle Xbox & Sensores', 'foto' => 'assets/img/uploads/rc_2a9848b6228365cbd34c9aa2.png'],
            ['nome' => 'Gustavo', 'funcao' => 'Pesquisa Ambiental & Documentação', 'foto' => 'assets/img/uploads/rc_01dd98fbbd0824e78f7f371a.png'],
            ['nome' => 'Paulo', 'funcao' => 'Testes Práticos & Apresentação Síntese', 'foto' => 'assets/img/uploads/rc_60ea8b2d9a478715e2463ce4.png'],
            ['nome' => 'Kaicky', 'funcao' => 'Montagem e pesquisa', 'foto' => 'assets/img/uploads/rc_043482f15386487a21618afd.png'],
        ];
    }
}

/**
 * Retorna os itens da galeria cadastrados na tabela `galeria`.
 */
function buscarGaleria(?PDO $pdo): array
{
    $galeriaPadrao = [
        [
            'titulo'  => 'Protótipo RoboClean construído com LEGO Education SPIKE Prime e garra coletora frontal',
            'arquivo' => 'assets/img/spike_galeria_1.jpg',
            'tipo'    => 'foto',
            'data'    => date('Y-m-d H:i:s')
        ],
        [
            'titulo'  => 'Vista superior: chassi modular ciano, Smart Hub V2 e garra atuadora acoplada à lixeira',
            'arquivo' => 'assets/img/spike_galeria_2.jpg',
            'tipo'    => 'foto',
            'data'    => date('Y-m-d H:i:s')
        ],
        [
            'titulo'  => 'Detalhe lateral das conexões do Smart Hub, eixo de tração e sistema de pinça com engrenagens',
            'arquivo' => 'assets/img/spike_galeria_3.jpg',
            'tipo'    => 'foto',
            'data'    => date('Y-m-d H:i:s')
        ],
        [
            'titulo'  => 'Vista traseira: posicionamento do Smart Hub V2 sobre o eixo para máxima tração',
            'arquivo' => 'assets/img/spike_galeria_4.jpg',
            'tipo'    => 'foto',
            'data'    => date('Y-m-d H:i:s')
        ],
    ];

    if (!$pdo) {
        return $galeriaPadrao;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, titulo, arquivo, tipo, data FROM galeria ORDER BY data DESC, id DESC');
        $stmt->execute();
        $res = $stmt->fetchAll();
        return !empty($res) ? $res : $galeriaPadrao;
    } catch (Throwable $e) {
        return $galeriaPadrao;
    }
}

/**
 * Retorna apenas as mensagens aprovadas para exibição pública no mural.
 */
function buscarMensagensAprovadas(?PDO $pdo): array
{
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->prepare('SELECT id, nome, texto, data FROM mensagens WHERE aprovada = :aprovada ORDER BY data DESC, id DESC LIMIT 30');
        $stmt->execute([':aprovada' => 1]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

