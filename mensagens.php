<?php
/**
 * ============================================================================
 * PROCESSAMENTO DO MURAL DE MENSAGENS - ROBOCLEAN (EVENTO SÍNTESE)
 * ============================================================================
 * Recebe o formulário do mural via POST, valida CSRF, tamanho, intervalo entre
 * envios e grava no MySQL com aprovada = 0 (aguardando moderação no painel).
 */

require_once __DIR__ . '/includes/db.php';

// Redireciona acessos diretos via GET para a seção do mural na página inicial
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#mural');
    exit;
}

// Função auxiliar para retornar com feedback amigável
function redirecionarMural(string $tipo, string $mensagem): void
{
    $_SESSION['mural_feedback'] = [
        'tipo'  => $tipo,
        'texto' => $mensagem
    ];
    header('Location: index.php#mural');
    exit;
}

// 1. Validação do Token CSRF
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validarCsrfToken($csrfToken)) {
    redirecionarMural('erro', 'Sessão expirada ou token de segurança inválido. Por favor, tente enviar novamente.');
}

// 2. Verifica se o banco de dados está disponível
if (!$db_disponivel || !$pdo) {
    redirecionarMural('aviso', 'O banco de dados não está conectado no momento. Tente novamente quando o servidor MySQL estiver ativo.');
}

// 3. Limpeza e validação dos campos
$nome  = trim((string) ($_POST['nome'] ?? ''));
$texto = trim((string) ($_POST['texto'] ?? ''));

// Remove quebras de linha excessivas mantendo leitura limpa
$nome  = preg_replace('/\s+/u', ' ', $nome);
$texto = preg_replace("/(\r?\n){3,}/u", "\n\n", $texto);

$tamNome  = mb_strlen($nome, 'UTF-8');
$tamTexto = mb_strlen($texto, 'UTF-8');

if ($tamNome < 2 || $tamNome > MSG_MAX_NOME) {
    redirecionarMural('erro', 'Por favor, informe um nome válido (entre 2 e ' . MSG_MAX_NOME . ' caracteres).');
}

if ($tamTexto < 3 || $tamTexto > MSG_MAX_TEXTO) {
    redirecionarMural('erro', 'A mensagem deve ter entre 3 e ' . MSG_MAX_TEXTO . ' caracteres.');
}

// 4. Proteção contra envios repetidos em sequência (Rate Limit & Duplicidade)
$agora = time();
$ultimoEnvio = (int) ($_SESSION['ultimo_envio_mural'] ?? 0);
$ultimoTexto = (string) ($_SESSION['ultimo_texto_mural'] ?? '');

if (($agora - $ultimoEnvio) < MSG_COOLDOWN_SEGUNDOS) {
    $restante = MSG_COOLDOWN_SEGUNDOS - ($agora - $ultimoEnvio);
    redirecionarMural('aviso', "Aguarde {$restante} segundos antes de enviar outra mensagem.");
}

if ($ultimoTexto !== '' && mb_strtolower($ultimoTexto, 'UTF-8') === mb_strtolower($texto, 'UTF-8')) {
    redirecionarMural('aviso', 'Você já enviou esta mesma mensagem recentemente.');
}

// 5. Inserção segura com PDO Prepared Statement (aprovada = 0 por padrão)
try {
    $stmt = $pdo->prepare(
        'INSERT INTO mensagens (nome, texto, aprovada, data) VALUES (:nome, :texto, 0, NOW())'
    );
    $stmt->execute([
        ':nome'  => $nome,
        ':texto' => $texto,
    ]);

    // Atualiza marcadores de proteção na sessão
    $_SESSION['ultimo_envio_mural'] = $agora;
    $_SESSION['ultimo_texto_mural'] = $texto;

    redirecionarMural(
        'sucesso',
        'Obrigado pela sua participação na feira Síntese! Sua mensagem foi enviada e aparecerá no mural assim que for aprovada pela equipe.'
    );
} catch (Throwable $e) {
    redirecionarMural('erro', 'Não foi possível salvar sua mensagem no momento. Tente novamente em instantes.');
}

