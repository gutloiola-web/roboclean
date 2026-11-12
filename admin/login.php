<?php
/**
 * ============================================================================
 * LOGIN DO PAINEL ADMINISTRATIVO - ROBOCLEAN (EVENTO SÍNTESE)
 * ============================================================================
 */

require_once dirname(__DIR__) . '/includes/db.php';

if (!empty($_SESSION['admin_logado'])) {
    header('Location: index.php');
    exit;
}

$erroLogin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $usuario   = trim((string) ($_POST['usuario'] ?? ''));
    $senha     = (string) ($_POST['senha'] ?? '');

    $tentativas  = (int) ($_SESSION['login_tentativas'] ?? 0);
    $bloqueioAte = (int) ($_SESSION['login_bloqueio_ate'] ?? 0);

    if (time() < $bloqueioAte) {
        $seg = $bloqueioAte - time();
        $erroLogin = "Muitas tentativas incorretas. Aguarde {$seg} segundos.";
    } elseif (!validarCsrfToken($csrfToken)) {
        $erroLogin = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    } elseif (!$db_disponivel || !$pdo) {
        $erroLogin = 'O banco de dados MySQL não está conectado. Inicie o MySQL no XAMPP/Laragon.';
    } elseif ($usuario === '' || $senha === '') {
        $erroLogin = 'Preencha o usuário e a senha.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, usuario, senha_hash FROM admins WHERE usuario = :usuario LIMIT 1');
            $stmt->execute([':usuario' => $usuario]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($senha, $admin['senha_hash'])) {
                session_regenerate_id(true);

                $_SESSION['admin_logado']       = true;
                $_SESSION['admin_id']           = (int) $admin['id'];
                $_SESSION['admin_usuario']      = $admin['usuario'];
                $_SESSION['login_tentativas']   = 0;
                $_SESSION['login_bloqueio_ate'] = 0;

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: index.php');
                exit;
            } else {
                $tentativas++;
                $_SESSION['login_tentativas'] = $tentativas;
                if ($tentativas >= 5) {
                    $_SESSION['login_bloqueio_ate'] = time() + 30;
                    $_SESSION['login_tentativas']   = 0;
                }
                $erroLogin = 'Usuário ou senha incorretos.';
            }
        } catch (Throwable $e) {
            $erroLogin = 'Erro ao consultar o banco de dados. Verifique se o banco.sql foi importado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — <?= e(SITE_NOME) ?> | <?= e(EVENTO_NOME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">

    <div class="admin-login-wrapper">
        <div class="admin-login-card">
            <div class="admin-login-brand">
                <h1>Painel <?= e(SITE_NOME) ?></h1>
                <p>Gerenciamento de Equipe, Galeria e Mural • Evento <?= e(EVENTO_NOME) ?></p>
            </div>

            <?php if (!$db_disponivel): ?>
                <div class="alert-box alert-aviso" role="alert">
                    <strong>MySQL Desconectado:</strong> Inicie o MySQL no XAMPP/Laragon e importe o arquivo <code>banco.sql</code> para acessar o painel.
                </div>
            <?php endif; ?>

            <?php if ($erroLogin !== ''): ?>
                <div class="alert-box alert-erro" role="alert">
                    <?= e($erroLogin) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="admin-login-form">
                <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">

                <div class="form-group">
                    <label for="usuario">Usuário</label>
                    <input type="text" id="usuario" name="usuario" required autocomplete="username" placeholder="Digite seu usuário" autofocus>
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required autocomplete="current-password" placeholder="Digite sua senha">
                </div>

                <button type="submit" class="btn btn-primary btn-full" <?= !$db_disponivel ? 'disabled' : '' ?>>
                    Entrar no Painel
                </button>
            </form>

            <div class="admin-login-footer">
                <a href="../index.php" class="back-site-link">&larr; Voltar para a apresentação do RoboClean</a>
            </div>
        </div>
    </div>

</body>
</html>
