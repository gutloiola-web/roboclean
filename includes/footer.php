<?php
/**
 * ============================================================================
 * RODAPÉ PRINCIPAL - ROBOCLEAN (EVENTO SÍNTESE)
 * ============================================================================
 */
$totalVisitas = isset($pdo) ? obterContadorVisitas($pdo) : null;
?>
    <!-- Rodapé Principal -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Coluna 1: Identidade do Projeto -->
                <div class="footer-brand-col">
                    <div class="brand-logo" style="margin-bottom: 8px;">
                        <img src="assets/img/logo_roboclean.svg" alt="Logo RoboClean" class="brand-logo-img" width="32" height="32">
                        <h3 class="footer-title" style="margin-bottom: 0;">Robo<span class="text-green">Clean</span></h3>
                    </div>
                    <p class="footer-subtitle"><?= e(SITE_SLOGAN) ?></p>
                    <p class="footer-desc">
                        Projeto escolar de robótica sustentável desenvolvido com LEGO Education SPIKE e controlado via Bluetooth por Controle de Xbox.
                    </p>
                </div>

                <!-- Coluna 2: Navegação Rápida -->
                <div class="footer-links-col">
                    <h4 class="footer-col-heading">Navegação</h4>
                    <ul class="footer-nav-list">
                        <li><a href="#inicio">Início</a></li>
                        <li><a href="#projeto">O Projeto</a></li>
                        <li><a href="#como-funciona">Como Funciona</a></li>
                        <li><a href="#equipe">Equipe</a></li>
                        <li><a href="#galeria">Galeria</a></li>
                        <li><a href="#mural">Mural</a></li>
                    </ul>
                </div>

                <!-- Coluna 3: Dados do Evento e Escola -->
                <div class="footer-info-col">
                    <h4 class="footer-col-heading">Apresentação Escolar</h4>
                    <div class="footer-event-card">
                        <p class="footer-event-line"><strong>Evento:</strong> Feira <?= e(EVENTO_NOME) ?></p>
                        <p class="footer-event-line"><strong>Escola:</strong> <?= e(ESCOLA_NOME) ?></p>
                        <p class="footer-event-line"><strong>Ano:</strong> <?= e(ANO_PROJETO) ?></p>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="footer-copy">
                    &copy; <?= e(ANO_PROJETO) ?> <strong><?= e(SITE_NOME) ?></strong> — Evento <strong><?= e(EVENTO_NOME) ?></strong>.
                </p>

                <div class="footer-meta-right">
                    <?php if ($totalVisitas !== null): ?>
                        <span class="visit-counter-badge" title="Total de visitas registradas">
                            Visitas: <strong><?= number_format($totalVisitas, 0, ',', '.') ?></strong>
                        </span>
                    <?php else: ?>
                        <span class="visit-counter-badge">
                            Feira <?= e(EVENTO_NOME) ?> <?= e(ANO_PROJETO) ?>
                        </span>
                    <?php endif; ?>

                    <a href="admin/login.php" class="footer-admin-link">
                        Painel Admin
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Botão Flutuante Voltar ao Topo -->
    <button type="button" class="back-to-top-btn" id="backToTopBtn" aria-label="Voltar ao topo da página" title="Voltar ao topo">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 15l-6-6-6 6"/>
        </svg>
    </button>

    <!-- Lightbox Modal para ampliar imagens da Galeria no telão -->
    <div class="lightbox-modal" id="lightboxModal" role="dialog" aria-modal="true" aria-label="Visualização ampliada da imagem" hidden>
        <div class="lightbox-backdrop" id="lightboxBackdrop"></div>
        <div class="lightbox-dialog">
            <button type="button" class="lightbox-close-btn" id="lightboxCloseBtn" aria-label="Fechar imagem">&times;</button>
            <img src="" alt="" class="lightbox-image" id="lightboxImage">
            <p class="lightbox-caption" id="lightboxCaption"></p>
        </div>
    </div>

    <!-- Script Principal -->
    <script src="assets/js/main.js?v=<?= (int) @filemtime(dirname(__DIR__) . '/assets/js/main.js') ?>"></script>
</body>
</html>
