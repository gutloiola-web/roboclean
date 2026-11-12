/**
 * ============================================================================
 * SCRIPT PRINCIPAL — PROJETO ROBOCLEAN (EVENTO SÍNTESE 2026)
 * ============================================================================
 * Funcionalidades:
 * 1. Menu Mobile Hambúrguer e ScrollSpy no cabeçalho fixo (6 itens)
 * 2. Abas da seção "O Projeto" (O Problema, Objetivos, Metodologia)
 * 3. Sanfonas (Accordions) fechadas por padrão (Materiais, Disciplinas, etc.)
 * 4. Diagrama interativo do Controle de Xbox
 * 5. Lightbox para ampliar imagens da Galeria
 * 6. Contador de caracteres do Mural de Mensagens e Botão Voltar ao Topo
 */

document.addEventListener('DOMContentLoaded', function () {
    iniciarMenuMobile();
    iniciarScrollSpyETopo();
    iniciarAbasProjeto();
    iniciarSanfonas();
    iniciarDiagramaXbox();
    iniciarLightboxGaleria();
    iniciarContadorMural();
});

/* ----------------------------------------------------------------------------
   1. MENU MOBILE HAMBÚRGUER
   ---------------------------------------------------------------------------- */
function iniciarMenuMobile() {
    var btnMobile = document.getElementById('mobileMenuBtn');
    var nav = document.getElementById('mainNav');
    if (!btnMobile || !nav) return;

    function fecharMenu() {
        nav.classList.remove('open');
        btnMobile.setAttribute('aria-expanded', 'false');
    }

    btnMobile.addEventListener('click', function () {
        var aberto = nav.classList.toggle('open');
        btnMobile.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });

    var links = nav.querySelectorAll('.nav-link');
    links.forEach(function (link) {
        link.addEventListener('click', fecharMenu);
    });

    document.addEventListener('click', function (e) {
        if (nav.classList.contains('open') && !nav.contains(e.target) && !btnMobile.contains(e.target)) {
            fecharMenu();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768 && nav.classList.contains('open')) {
            fecharMenu();
        }
    });
}

/* ----------------------------------------------------------------------------
   2. SCROLLSPY DO MENU E BOTÃO VOLTAR AO TOPO
   ---------------------------------------------------------------------------- */
function iniciarScrollSpyETopo() {
    var backToTopBtn = document.getElementById('backToTopBtn');
    var secoes = document.querySelectorAll('main section[id]');
    var navLinks = document.querySelectorAll('.main-nav .nav-link');

    function aoRolar() {
        var scrollY = window.scrollY || window.pageYOffset;

        if (backToTopBtn) {
            if (scrollY > 400) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        }

        var secaoAtual = '';
        secoes.forEach(function (secao) {
            var topo = secao.offsetTop - 120;
            var altura = secao.offsetHeight;
            if (scrollY >= topo && scrollY < topo + altura) {
                secaoAtual = secao.getAttribute('id');
            }
        });

        if (secaoAtual) {
            navLinks.forEach(function (link) {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + secaoAtual) {
                    link.classList.add('active');
                }
            });
        }
    }

    window.addEventListener('scroll', aoRolar, { passive: true });
    aoRolar();

    if (backToTopBtn) {
        backToTopBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
}

/* ----------------------------------------------------------------------------
   3. ABAS DA SEÇÃO "O PROJETO" (PROBLEMA, OBJETIVOS, METODOLOGIA)
   ---------------------------------------------------------------------------- */
function iniciarAbasProjeto() {
    var botoesAba = document.querySelectorAll('.tab-btn');
    var paineisAba = document.querySelectorAll('.tab-panel');
    if (!botoesAba.length || !paineisAba.length) return;

    botoesAba.forEach(function (botao) {
        botao.addEventListener('click', function () {
            var alvoId = botao.getAttribute('data-tab');

            botoesAba.forEach(function (b) {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });

            paineisAba.forEach(function (painel) {
                if (painel.id === alvoId) {
                    painel.classList.add('active');
                    painel.removeAttribute('hidden');
                } else {
                    painel.classList.remove('active');
                    painel.setAttribute('hidden', 'hidden');
                }
            });

            botao.classList.add('active');
            botao.setAttribute('aria-selected', 'true');
        });
    });
}

/* ----------------------------------------------------------------------------
   4. SANFONAS FECHADAS POR PADRÃO (MATERIAIS, DISCIPLINAS, FUNDAMENTAÇÃO, ETC.)
   ---------------------------------------------------------------------------- */
function iniciarSanfonas() {
    var gatilhos = document.querySelectorAll('.accordion-trigger');
    if (!gatilhos.length) return;

    gatilhos.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = btn.closest('.accordion-item');
            if (!item) return;

            var conteudo = item.querySelector('.accordion-content');
            var estavaAberto = item.classList.contains('open');

            if (estavaAberto) {
                item.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
                if (conteudo) conteudo.setAttribute('hidden', 'hidden');
            } else {
                item.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
                if (conteudo) conteudo.removeAttribute('hidden');
            }
        });
    });
}

/* ----------------------------------------------------------------------------
   5. DIAGRAMA INTERATIVO DO CONTROLE DE XBOX
   ---------------------------------------------------------------------------- */
function iniciarDiagramaXbox() {
    var itensLegenda = document.querySelectorAll('.xbox-legend-item');
    if (!itensLegenda.length) return;

    function limparDestaques() {
        document.querySelectorAll('.xbox-part').forEach(function (p) {
            p.classList.remove('highlight-part');
        });
        itensLegenda.forEach(function (item) {
            item.classList.remove('active');
        });
    }

    itensLegenda.forEach(function (item) {
        var alvoId = 'svg-' + item.getAttribute('data-target');

        function ativar() {
            limparDestaques();
            item.classList.add('active');
            var grupoSvg = document.getElementById(alvoId);
            if (grupoSvg) {
                grupoSvg.classList.add('highlight-part');
            }
        }

        item.addEventListener('mouseenter', ativar);
        item.addEventListener('click', ativar);
        item.addEventListener('mouseleave', limparDestaques);
    });
}

/* ----------------------------------------------------------------------------
   6. LIGHTBOX MODAL DA GALERIA
   ---------------------------------------------------------------------------- */
function iniciarLightboxGaleria() {
    var modal = document.getElementById('lightboxModal');
    var imgModal = document.getElementById('lightboxImage');
    var legendaModal = document.getElementById('lightboxCaption');
    var btnFechar = document.getElementById('lightboxCloseBtn');
    var backdrop = document.getElementById('lightboxBackdrop');
    var gatilhos = document.querySelectorAll('.gallery-zoom-trigger');

    if (!modal || !imgModal || !gatilhos.length) return;

    function abrirLightbox(src, legenda) {
        imgModal.src = src;
        imgModal.alt = legenda || 'Imagem ampliada da galeria RoboClean';
        if (legendaModal) {
            legendaModal.textContent = legenda || '';
        }
        modal.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
    }

    function fecharLightbox() {
        modal.setAttribute('hidden', 'hidden');
        imgModal.src = '';
        document.body.style.overflow = '';
    }

    gatilhos.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var src = btn.getAttribute('data-src');
            var caption = btn.getAttribute('data-caption');
            if (src) abrirLightbox(src, caption);
        });
    });

    if (btnFechar) btnFechar.addEventListener('click', fecharLightbox);
    if (backdrop) backdrop.addEventListener('click', fecharLightbox);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hasAttribute('hidden')) {
            fecharLightbox();
        }
    });
}

/* ----------------------------------------------------------------------------
   7. CONTADOR DE CARACTERES DO MURAL DE MENSAGENS
   ---------------------------------------------------------------------------- */
function iniciarContadorMural() {
    var campoTexto = document.getElementById('campoTexto');
    var contador = document.getElementById('contadorCaracteres');
    if (!campoTexto || !contador) return;

    var max = campoTexto.getAttribute('maxlength') || 500;

    function atualizar() {
        var len = campoTexto.value.length;
        contador.textContent = len + ' / ' + max;
    }

    campoTexto.addEventListener('input', atualizar);
    atualizar();
}
