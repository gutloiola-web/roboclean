<?php
/**
 * ============================================================================
 * CABEÇALHO PRINCIPAL E MENU FIXO - ROBOCLEAN (EVENTO SÍNTESE)
 * ============================================================================
 */
require_once __DIR__ . '/db.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="RoboClean - Tecnologia a serviço do meio ambiente. Um robô catador de lixo feito com LEGO SPIKE e controlado por um controle de Xbox.">
    <title><?= e(SITE_NOME) ?> — <?= e(SITE_SLOGAN) ?> | <?= e(EVENTO_NOME) ?> <?= e(ANO_PROJETO) ?></title>

    <!-- Ícone da aba (Logo do Robô) -->
    <link rel="icon" type="image/svg+xml" href="assets/img/logo_roboclean.svg">

    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Folha de estilo principal -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) @filemtime(dirname(__DIR__) . '/assets/css/style.css') ?>">
</head>
<body>

    <!-- Cabeçalho Fixo -->
    <header class="site-header" id="siteHeader">
        <div class="container header-inner">
            <a href="#inicio" class="brand-logo" aria-label="RoboClean - Voltar ao início">
                <img src="assets/img/logo_roboclean.svg" alt="Logo RoboClean" class="brand-logo-img" width="34" height="34">
                <span class="brand-title">Robo<span class="brand-highlight">Clean</span></span>
            </a>

            <!-- Navegação Principal (6 itens) -->
            <nav class="main-nav" id="mainNav" aria-label="Menu principal">
                <ul class="nav-list">
                    <li><a href="#inicio" class="nav-link active">Início</a></li>
                    <li><a href="#projeto" class="nav-link">O Projeto</a></li>
                    <li><a href="#como-funciona" class="nav-link">Como Funciona</a></li>
                    <li><a href="#equipe" class="nav-link">Equipe</a></li>
                    <li><a href="#galeria" class="nav-link">Galeria</a></li>
                    <li><a href="#mural" class="nav-link">Mural</a></li>
                </ul>
            </nav>

            <!-- Botão Menu Hambúrguer no Celular -->
            <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Abrir menu de navegação" aria-expanded="false">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </header>
