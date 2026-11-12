<?php
/**
 * ============================================================================
 * ARQUIVO DE CONFIGURAÇÃO - PROJETO ROBOCLEAN (EVENTO SÍNTESE)
 * ============================================================================
 * Edite os dados abaixo conforme o seu ambiente (XAMPP, Laragon ou Hospedagem).
 */

// Previne acesso direto se necessário e configura fuso horário
date_default_timezone_set('America/Sao_Paulo');

// ----------------------------------------------------------------------------
// 1. CONFIGURAÇÕES DO BANCO DE DADOS (MySQL / MariaDB)
// Compatível com Localhost (XAMPP/Laragon) e Nuvem/Render (Variáveis de Ambiente)
// ----------------------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'roboclean_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// ----------------------------------------------------------------------------
// 2. INFORMAÇÕES GERAIS DO PROJETO E DA ESCOLA (Fáceis de editar)
// ----------------------------------------------------------------------------
define('SITE_NOME', 'RoboClean');
define('SITE_SLOGAN', 'Tecnologia a serviço do meio ambiente');
define('EVENTO_NOME', 'Síntese');
define('ESCOLA_NOME', 'Colégio / Escola — Feira de Ciências Síntese'); // Edite com o nome da sua escola
define('ANO_PROJETO', '2026');

// ----------------------------------------------------------------------------
// 3. LIMITES DE SEGURANÇA E UPLOADS
// ----------------------------------------------------------------------------
define('UPLOAD_MAX_IMG', 5 * 1024 * 1024);    // 5 MB para fotos (JPG, PNG, WEBP)
define('UPLOAD_MAX_VIDEO', 25 * 1024 * 1024); // 25 MB para vídeos (MP4)
define('MSG_MAX_NOME', 60);                   // Máximo de caracteres no nome do mural
define('MSG_MAX_TEXTO', 500);                 // Máximo de caracteres na mensagem do mural
define('MSG_COOLDOWN_SEGUNDOS', 20);          // Intervalo mínimo entre envios seguidos

// ----------------------------------------------------------------------------
// 4. INICIALIZAÇÃO SEGURA DE SESSÃO
// ----------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// ----------------------------------------------------------------------------
// 5. FUNÇÕES AUXILIARES DE SEGURANÇA (XSS & CSRF)
// ----------------------------------------------------------------------------

/**
 * Escapa strings para exibição segura no HTML (prevenção contra XSS)
 */
function e(?string $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Gera ou retorna o token CSRF da sessão atual
 */
function gerarCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF recebido em formulários POST
 */
function validarCsrfToken(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

