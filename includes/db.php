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
        PDO::ATTR_TIMEOUT                  => 3,
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

    if (!$db_disponivel) {
        $pdo = null;
        $db_mensagem_status = 'O banco de dados MySQL não está conectado no momento. As informações dinâmicas serão exibidas assim que o serviço MySQL for iniciado.';
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
            'titulo'  => 'Robô Catador de Lixo construído com LEGO Education SPIKE',
            'arquivo' => 'assets/img/spike_galeria_1.jpg',
            'tipo'    => 'foto',
            'data'    => date('Y-m-d H:i:s')
        ],
        [
            'titulo'  => 'Controle de Xbox conectado via Bluetooth ao Hub LEGO SPIKE',
            'arquivo' => 'assets/img/spike_galeria_2.jpg',
            'tipo'    => 'foto',
            'data'    => date('Y-m-d H:i:s')
        ],
        [
            'titulo'  => 'Garra/Pá Coletora e Sensor de Distância em Testes Práticos',
            'arquivo' => 'assets/img/spike_galeria_3.jpg',
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

