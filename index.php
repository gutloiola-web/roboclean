<?php
/**
 * ============================================================================
 * PÁGINA PRINCIPAL - PROJETO ROBOCLEAN (EVENTO SÍNTESE 2026)
 * ============================================================================
 */

require_once __DIR__ . '/includes/header.php';

// Busca dados dinâmicos do banco MySQL (se disponível)
$membrosEquipe      = buscarMembros($pdo);
$itensGaleria       = buscarGaleria($pdo);
$mensagensAprovadas = buscarMensagensAprovadas($pdo);

// Recupera e limpa mensagem de retorno do formulário do mural (se houver)
$feedbackMural = $_SESSION['mural_feedback'] ?? null;
unset($_SESSION['mural_feedback']);

// Verifica se a foto real do robô (assets/img/robo.jpg) já foi colocada na pasta
$caminhoFotoRobo = __DIR__ . '/assets/img/robo.jpg';
$temFotoRealRobo = is_file($caminhoFotoRobo);
$versaoFotoRobo  = $temFotoRealRobo ? (int) @filemtime($caminhoFotoRobo) : 1;
?>

<main id="conteudoPrincipal">

    <!-- ======================================================================
         1. INÍCIO (HERO)
         ====================================================================== -->
    <section class="hero-section" id="inicio">
        <div class="container">
            <div class="hero-grid">
                <!-- Coluna de Texto do Hero -->
                <div class="hero-content">
                    <p class="hero-eyebrow">INÍCIO &middot; FEIRA <?= e(mb_strtoupper(EVENTO_NOME, 'UTF-8')) ?> <?= e(ANO_PROJETO) ?></p>

                    <h1 class="hero-title">Robo<span class="text-green">Clean</span></h1>

                    <p class="hero-slogan"><?= e(SITE_SLOGAN) ?></p>

                    <p class="hero-description">
                        Um robô catador de lixo feito com LEGO SPIKE e controlado por um controle de Xbox.
                    </p>

                    <div class="hero-buttons">
                        <a href="#projeto" class="btn btn-primary">Conheça o projeto</a>
                        <a href="#como-funciona" class="btn btn-secondary">Como funciona</a>
                    </div>
                </div>

                <!-- Espaço para a foto real do robô (assets/img/robo.jpg) com placeholder cinza -->
                <div class="hero-visual">
                    <div class="hero-photo-placeholder <?= $temFotoRealRobo ? 'has-photo' : 'is-placeholder' ?>">
                        <?php if ($temFotoRealRobo): ?>
                            <img
                                src="assets/img/robo.jpg?v=<?= $versaoFotoRobo ?>"
                                alt="Foto real do robô RoboClean"
                                class="hero-real-photo"
                                onerror="this.style.display='none'; this.parentElement.classList.add('is-placeholder');"
                            >
                        <?php endif; ?>
                        <svg class="placeholder-cross-lines" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                            <line x1="0" y1="0" x2="100" y2="100" vector-effect="non-scaling-stroke"/>
                            <line x1="100" y1="0" x2="0" y2="100" vector-effect="non-scaling-stroke"/>
                        </svg>
                        <span class="placeholder-caption-bottom">Foto real do robô &middot; assets/img/robo.jpg</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================================
         2. O PROJETO (ABAS: PROBLEMA, OBJETIVOS E METODOLOGIA + SANFONAS)
         ====================================================================== -->
    <section class="site-section section-alt" id="projeto">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">O Projeto</h2>
                <p class="section-subtitle">Conheça o problema ambiental abordado, nossos objetivos, a metodologia e os fundamentos do RoboClean.</p>
            </div>

            <!-- Navegação das 3 Abas: O Problema | Objetivos | Metodologia -->
            <div class="tabs-container">
                <div class="tabs-nav" role="tablist" aria-label="Informações principais do projeto">
                    <button
                        type="button"
                        class="tab-btn active"
                        role="tab"
                        aria-selected="true"
                        aria-controls="tab-problema"
                        id="btn-tab-problema"
                        data-tab="tab-problema"
                    >
                        O Problema
                    </button>
                    <button
                        type="button"
                        class="tab-btn"
                        role="tab"
                        aria-selected="false"
                        aria-controls="tab-objetivos"
                        id="btn-tab-objetivos"
                        data-tab="tab-objetivos"
                    >
                        Objetivos
                    </button>
                    <button
                        type="button"
                        class="tab-btn"
                        role="tab"
                        aria-selected="false"
                        aria-controls="tab-metodologia"
                        id="btn-tab-metodologia"
                        data-tab="tab-metodologia"
                    >
                        Metodologia
                    </button>
                </div>

                <!-- ABA 1: O PROBLEMA -->
                <div class="tab-panel active" id="tab-problema" role="tabpanel" aria-labelledby="btn-tab-problema">
                    <div class="problem-main-box">
                        <p class="problem-lead-text">
                            O descarte inadequado de lixo em espaços públicos causa poluição ambiental e prejudica a natureza. A coleta manual pode exigir tempo e esforço. Por isso, nosso projeto propõe desenvolver um robô catador de lixo utilizando o LEGO SPIKE, capaz de recolher pequenos resíduos do chão e contribuir para a preservação do meio ambiente.
                        </p>
                    </div>

                    <h3 class="subsection-heading">Consequências do descarte incorreto de resíduos</h3>

                    <div class="consequences-grid">
                        <article class="consequence-card">
                            <div class="card-line-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                            </div>
                            <h4 class="consequence-title">Poluição do solo e da água</h4>
                            <p class="consequence-text">
                                Resíduos descartados no chão contaminam a terra e são levados pela chuva até rios, córregos e lençóis freáticos.
                            </p>
                        </article>

                        <article class="consequence-card">
                            <div class="card-line-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                            </div>
                            <h4 class="consequence-title">Prejuízo aos animais</h4>
                            <p class="consequence-text">
                                Pequenos plásticos, papéis e embalagens podem ser ingeridos por aves e outros animais, colocando a fauna em risco.
                            </p>
                        </article>

                        <article class="consequence-card">
                            <div class="card-line-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M16 14v6"/><path d="M8 14v6"/><path d="M12 16v6"/></svg>
                            </div>
                            <h4 class="consequence-title">Bueiros entupidos</h4>
                            <p class="consequence-text">
                                O acúmulo de lixo nas ruas e pátios bloqueia o escoamento da água da chuva, provocando alagamentos e enchentes.
                            </p>
                        </article>

                        <article class="consequence-card">
                            <div class="card-line-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                            </div>
                            <h4 class="consequence-title">Proliferação de doenças</h4>
                            <p class="consequence-text">
                                Ambientes sujos acumulam água parada e atraem vetores de doenças, prejudicando a saúde coletiva.
                            </p>
                        </article>
                    </div>
                </div>

                <!-- ABA 2: OBJETIVOS -->
                <div class="tab-panel" id="tab-objetivos" role="tabpanel" aria-labelledby="btn-tab-objetivos" hidden>
                    <div class="general-objective-banner">
                        <span class="general-obj-badge">Objetivo Geral</span>
                        <p class="general-obj-text">
                            Desenvolver um robô catador de lixo utilizando o LEGO SPIKE, com o propósito de facilitar a coleta de pequenos resíduos e contribuir para a preservação do meio ambiente.
                        </p>
                    </div>

                    <h3 class="subsection-heading">Objetivos Específicos</h3>

                    <div class="objectives-cards-grid">
                        <article class="objective-card">
                            <span class="objective-number">01</span>
                            <p class="objective-desc">Construir e programar um robô capaz de recolher pequenos resíduos.</p>
                        </article>

                        <article class="objective-card">
                            <span class="objective-number">02</span>
                            <p class="objective-desc">Utilizar motores e sensores para controlar os movimentos.</p>
                        </article>

                        <article class="objective-card">
                            <span class="objective-number">03</span>
                            <p class="objective-desc">Explorar os conhecimentos de robótica e programação.</p>
                        </article>

                        <article class="objective-card">
                            <span class="objective-number">04</span>
                            <p class="objective-desc">Incentivar o descarte correto do lixo.</p>
                        </article>

                        <article class="objective-card">
                            <span class="objective-number">05</span>
                            <p class="objective-desc">Demonstrar como a tecnologia pode ajudar a reduzir a poluição ambiental.</p>
                        </article>
                    </div>
                </div>

                <!-- ABA 3: METODOLOGIA -->
                <div class="tab-panel" id="tab-metodologia" role="tabpanel" aria-labelledby="btn-tab-metodologia" hidden>
                    <div class="methodology-text-box">
                        <p>
                            O projeto será desenvolvido em etapas, utilizando o kit LEGO SPIKE. Primeiro, será planejada a estrutura do robô. Em seguida, serão montadas as peças, os motores e os sensores necessários para sua movimentação e coleta de pequenos resíduos. Depois, será realizada a programação para controlar seus movimentos. Por fim, serão feitos testes para avaliar seu funcionamento, identificar problemas e realizar melhorias, buscando tornar a coleta de lixo mais eficiente.
                        </p>
                    </div>

                    <div class="methodology-steps-grid">
                        <article class="method-step-card">
                            <div class="method-step-number">1</div>
                            <h4 class="method-step-title">Planejamento</h4>
                            <p class="method-step-desc">Definição da estrutura do robô, escolha das peças e desenho do mecanismo da pá coletora e do recipiente.</p>
                        </article>

                        <article class="method-step-card">
                            <div class="method-step-number">2</div>
                            <h4 class="method-step-title">Montagem</h4>
                            <p class="method-step-desc">Construção física com as peças LEGO SPIKE, acoplando rodas, eixos, motores, pá coletora e sensor de distância.</p>
                        </article>

                        <article class="method-step-card">
                            <div class="method-step-number">3</div>
                            <h4 class="method-step-title">Programação</h4>
                            <p class="method-step-desc">Criação dos comandos lógicos para controlar os movimentos pelo controle de Xbox e ler o sensor de distância.</p>
                        </article>

                        <article class="method-step-card">
                            <div class="method-step-number">4</div>
                            <h4 class="method-step-title">Testes e melhorias</h4>
                            <p class="method-step-desc">Avaliação prática recolhendo pequenos resíduos, identificação de ajustes e aperfeiçoamento da eficiência.</p>
                        </article>
                    </div>
                </div>
            </div>

            <!-- ==============================================================
                 SANFONAS FECHADAS POR PADRÃO:
                 Materiais, Disciplinas, Fundamentação, Desafios e Impactos
                 ============================================================== -->
            <div class="accordions-wrapper">
                <h3 class="subsection-heading">Mais detalhes do projeto</h3>

                <!-- Sanfona 1: Materiais Utilizados -->
                <div class="accordion-item" id="materiais">
                    <button type="button" class="accordion-trigger" aria-expanded="false">
                        <span class="accordion-title-wrap">
                            <svg class="icon-line" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                            <span>Materiais Utilizados</span>
                        </span>
                        <svg class="accordion-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="accordion-content" hidden>
                        <div class="materials-grid">
                            <div class="material-card"><h4 class="material-name">Kit LEGO Education SPIKE</h4></div>
                            <div class="material-card"><h4 class="material-name">Hub programável</h4></div>
                            <div class="material-card"><h4 class="material-name">Motores para movimentação e coleta do lixo</h4></div>
                            <div class="material-card"><h4 class="material-name">Sensor de distância</h4></div>
                            <div class="material-card"><h4 class="material-name">Rodas, eixos e peças de montagem LEGO</h4></div>
                            <div class="material-card"><h4 class="material-name">Peças para construir a pá coletora</h4></div>
                            <div class="material-card"><h4 class="material-name">Recipiente para armazenar os resíduos</h4></div>
                            <div class="material-card"><h4 class="material-name">Celular ou computador para programar e controlar o robô</h4></div>
                            <div class="material-card"><h4 class="material-name">Controle de Xbox</h4></div>
                            <div class="material-card"><h4 class="material-name">Pequenos resíduos para realizar os testes</h4></div>
                        </div>
                    </div>
                </div>

                <!-- Sanfona 2: Disciplinas Relacionadas -->
                <div class="accordion-item" id="disciplinas">
                    <button type="button" class="accordion-trigger" aria-expanded="false">
                        <span class="accordion-title-wrap">
                            <svg class="icon-line" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            <span>Disciplinas Relacionadas</span>
                        </span>
                        <svg class="accordion-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="accordion-content" hidden>
                        <div class="disciplines-grid">
                            <article class="discipline-card">
                                <h4 class="discipline-title">Robótica</h4>
                                <p class="discipline-desc">Montagem e funcionamento do robô com LEGO SPIKE.</p>
                            </article>
                            <article class="discipline-card">
                                <h4 class="discipline-title">Matemática</h4>
                                <p class="discipline-desc">Medidas, cálculos e proporções na construção.</p>
                            </article>
                            <article class="discipline-card">
                                <h4 class="discipline-title">Física</h4>
                                <p class="discipline-desc">Movimento, força e funcionamento dos motores.</p>
                            </article>
                            <article class="discipline-card">
                                <h4 class="discipline-title">Programação</h4>
                                <p class="discipline-desc">Criação dos comandos para controlar o robô.</p>
                            </article>
                            <article class="discipline-card">
                                <h4 class="discipline-title">Ciências</h4>
                                <p class="discipline-desc">Estudo dos resíduos e seus impactos ambientais.</p>
                            </article>
                            <article class="discipline-card">
                                <h4 class="discipline-title">Geografia</h4>
                                <p class="discipline-desc">Análise da poluição e dos problemas ambientais.</p>
                            </article>
                            <article class="discipline-card">
                                <h4 class="discipline-title">Educação Ambiental</h4>
                                <p class="discipline-desc">Conscientização sobre o descarte correto do lixo.</p>
                            </article>
                        </div>
                    </div>
                </div>

                <!-- Sanfona 3: Fundamentação Teórica -->
                <div class="accordion-item" id="fundamentacao">
                    <button type="button" class="accordion-trigger" aria-expanded="false">
                        <span class="accordion-title-wrap">
                            <svg class="icon-line" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <span>Fundamentação Teórica</span>
                        </span>
                        <svg class="accordion-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="accordion-content" hidden>
                        <div class="concepts-trio-grid">
                            <article class="concept-card">
                                <span class="concept-badge">Conceito 1</span>
                                <h4 class="concept-title">Robótica</h4>
                                <p class="concept-text">
                                    Área que reúne mecânica, eletrônica e programação para projetar máquinas capazes de executar tarefas práticas e interagir com o ambiente.
                                </p>
                            </article>
                            <article class="concept-card">
                                <span class="concept-badge">Conceito 2</span>
                                <h4 class="concept-title">Automação</h4>
                                <p class="concept-text">
                                    Uso de sistemas programados, motores e sensores para realizar ações com maior precisão e auxiliar as pessoas em atividades repetitivas ou que exigem esforço.
                                </p>
                            </article>
                            <article class="concept-card">
                                <span class="concept-badge">Conceito 3</span>
                                <h4 class="concept-title">Sustentabilidade</h4>
                                <p class="concept-text">
                                    Compromisso de cuidar dos recursos naturais e reduzir a poluição hoje para garantir um meio ambiente equilibrado e saudável para as próximas gerações.
                                </p>
                            </article>
                        </div>

                        <h4 class="subsection-heading">Autores de referência</h4>

                        <div class="authors-grid">
                            <article class="author-card">
                                <div class="author-body">
                                    <span class="author-role">Ética e Tecnologia</span>
                                    <h5 class="author-name">Isaac Asimov</h5>
                                    <p class="author-desc">
                                        Popularizou a robótica e ajuda a pensar no uso responsável da tecnologia a favor do bem-estar humano e coletivo.
                                    </p>
                                </div>
                            </article>
                            <article class="author-card">
                                <div class="author-body">
                                    <span class="author-role">Construcionismo</span>
                                    <h5 class="author-name">Seymour Papert</h5>
                                    <p class="author-desc">
                                        Criador da teoria do construcionismo: defende que o estudante aprende melhor quando coloca a mão na massa e constrói projetos significativos.
                                    </p>
                                </div>
                            </article>
                            <article class="author-card">
                                <div class="author-body">
                                    <span class="author-role">Educação Transformadora</span>
                                    <h5 class="author-name">Paulo Freire</h5>
                                    <p class="author-desc">
                                        Defende a educação crítica e a conscientização, incentivando os estudantes a refletir sobre a realidade ao seu redor e buscar soluções reais.
                                    </p>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>

                <!-- Sanfona 4: Desafios e Limitações -->
                <div class="accordion-item" id="desafios">
                    <button type="button" class="accordion-trigger" aria-expanded="false">
                        <span class="accordion-title-wrap">
                            <svg class="icon-line" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <span>Desafios e Limitações</span>
                        </span>
                        <svg class="accordion-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="accordion-content" hidden>
                        <div class="challenges-grid">
                            <div class="challenge-item">
                                <div>
                                    <h4>Identificação de resíduos</h4>
                                    <p>Dificuldade em diferenciar automaticamente tipos variados de lixo no chão apenas com sensores básicos.</p>
                                </div>
                            </div>
                            <div class="challenge-item">
                                <div>
                                    <h4>Desvio de obstáculos</h4>
                                    <p>Terrenos irregulares, degraus ou objetos muito baixos exigem atenção constante durante a condução e leitura do sensor.</p>
                                </div>
                            </div>
                            <div class="challenge-item">
                                <div>
                                    <h4>Autonomia da bateria e consumo</h4>
                                    <p>O uso contínuo dos motores de locomoção, da pá coletora e do Bluetooth consome energia e limita o tempo de operação.</p>
                                </div>
                            </div>
                            <div class="challenge-item">
                                <div>
                                    <h4>Custos dos equipamentos</h4>
                                    <p>Kits de robótica educacional e controles sem fio possuem custo elevado para aplicação em larga escala.</p>
                                </div>
                            </div>
                            <div class="challenge-item">
                                <div>
                                    <h4>Limite de tamanho e peso</h4>
                                    <p>Por ser um protótipo compacto construído em LEGO SPIKE, o robô consegue recolher apenas resíduos pequenos e leves.</p>
                                </div>
                            </div>
                        </div>

                        <div class="challenges-important-note">
                            <p>
                                <strong>Importante:</strong> O RoboClean é uma ferramenta <strong>complementar e educativa</strong>. Ele não substitui o trabalho indispensável dos profissionais da limpeza urbana, mas valoriza essa atividade e incentiva todos a não jogarem lixo no chão.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sanfona 5: Impactos Sociais -->
                <div class="accordion-item" id="impactos">
                    <button type="button" class="accordion-trigger" aria-expanded="false">
                        <span class="accordion-title-wrap">
                            <svg class="icon-line" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span>Impactos Sociais</span>
                        </span>
                        <svg class="accordion-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="accordion-content" hidden>
                        <div class="social-impact-panel">
                            <div class="social-impact-text-col">
                                <p class="social-impact-paragraph">
                                    O robô catador de lixo pode contribuir para a conscientização ambiental, incentivando o descarte correto dos resíduos e a preservação dos espaços públicos. Além disso, o projeto estimula o interesse pela tecnologia, pela robótica e pela programação, desenvolvendo a criatividade e o trabalho em equipe. A iniciativa também demonstra como a inovação pode ajudar a sociedade a enfrentar problemas ambientais e melhorar a qualidade de vida da população.
                                </p>
                            </div>

                            <div class="social-impact-pillars">
                                <div class="impact-pillar-card">
                                    <strong>Conscientização Ambiental</strong>
                                    <small>Cuidado com os espaços públicos</small>
                                </div>
                                <div class="impact-pillar-card">
                                    <strong>Trabalho em Equipe</strong>
                                    <small>Criatividade e cooperação escolar</small>
                                </div>
                                <div class="impact-pillar-card">
                                    <strong>Inovação Cidadã</strong>
                                    <small>Tecnologia aplicada ao bem comum</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================================
         3. COMO O ROBOCLEAN FUNCIONA (PASSO A PASSO + DIAGRAMA XBOX + FLUXOGRAMA)
         ====================================================================== -->
    <section class="site-section" id="como-funciona">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Como o RoboClean Funciona</h2>
                <p class="section-subtitle">Entenda passo a passo a estrutura do robô, o controle via Xbox e o fluxo de comunicação.</p>
            </div>

            <!-- Linha do Tempo / Etapas de Funcionamento -->
            <div class="how-it-works-steps">
                <article class="step-work-card">
                    <div class="step-work-badge">Passo 1</div>
                    <h3 class="step-work-title">Estrutura LEGO SPIKE</h3>
                    <p class="step-work-text">
                        O robô é montado com o kit LEGO SPIKE, contando com motores para movimentação e coleta, sensor de distância para detectar obstáculos, pá coletora e recipiente para armazenar os resíduos.
                    </p>
                </article>

                <article class="step-work-card">
                    <div class="step-work-badge">Passo 2</div>
                    <h3 class="step-work-title">Hub Programável ("Cérebro")</h3>
                    <p class="step-work-text">
                        O hub programável funciona como o "cérebro" central do RoboClean, processando os comandos e se comunicando sem fio via conexão Bluetooth.
                    </p>
                </article>

                <article class="step-work-card">
                    <div class="step-work-badge">Passo 3</div>
                    <h3 class="step-work-title">Controle de Xbox</h3>
                    <p class="step-work-text">
                        O robô é pilotado por um controle de Xbox: os direcionais e o analógico movem o robô (frente, trás, esquerda e direita) e os botões acionam a pá coletora.
                    </p>
                </article>

                <article class="step-work-card">
                    <div class="step-work-badge">Passo 4</div>
                    <h3 class="step-work-title">Sensor de Distância</h3>
                    <p class="step-work-text">
                        O sensor de distância identifica paredes ou objetos à frente e ajuda a evitar obstáculos durante a locomoção pelo ambiente.
                    </p>
                </article>

                <article class="step-work-card">
                    <div class="step-work-badge">Passo 5</div>
                    <h3 class="step-work-title">Coleta e Armazenamento</h3>
                    <p class="step-work-text">
                        O resíduo é recolhido do chão pela pá coletora motorizada e guardado com segurança no recipiente acoplado ao robô.
                    </p>
                </article>
            </div>

            <!-- Diagrama Ilustrativo do Controle de Xbox -->
            <div class="xbox-diagram-panel">
                <div class="xbox-diagram-header">
                    <h3>Diagrama Ilustrativo do Controle de Xbox</h3>
                    <p class="xbox-disclaimer">
                        <strong>Nota:</strong> O mapeamento abaixo é um exemplo ilustrativo de como os comandos podem ser distribuídos e pode ser ajustado na programação do projeto.
                    </p>
                </div>

                <div class="xbox-interactive-layout">
                    <!-- Lista de Comandos à Esquerda -->
                    <div class="xbox-legend-col">
                        <div class="xbox-legend-item" data-target="stick-left">
                            <span class="legend-key">Analógico Esquerdo / Direcionais</span>
                            <p>Movimenta o RoboClean para <strong>Frente, Trás, Esquerda e Direita</strong>.</p>
                        </div>

                        <div class="xbox-legend-item" data-target="triggers">
                            <span class="legend-key">Gatilhos LT / RT</span>
                            <p>Controla a velocidade de deslocamento dos motores das rodas.</p>
                        </div>
                    </div>

                    <!-- SVG Limpo em Linha do Controle de Xbox no Centro -->
                    <div class="xbox-svg-wrapper">
                        <svg class="xbox-controller-svg" viewBox="0 0 500 320" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Diagrama do Controle de Xbox com mapeamento de botões do RoboClean">
                            <!-- Corpo do Controle -->
                            <path d="M125 65 H375 C425 65 462 115 452 185 L435 262 C426 294 392 302 368 275 L322 225 H178 L132 275 C108 302 74 294 65 262 L48 185 C38 115 75 65 125 65 Z" fill="#F9FAFB" stroke="#9CA3AF" stroke-width="3"/>

                            <!-- Gatilhos Superiores (LT / RT) -->
                            <g class="xbox-part" id="svg-triggers">
                                <path d="M120 62 C120 40 165 40 175 62" fill="#E5E7EB" stroke="#6B7280" stroke-width="2"/>
                                <text x="147" y="34" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-weight="700" font-size="13" fill="#374151">LT</text>

                                <path d="M325 62 C335 40 380 40 380 62" fill="#E5E7EB" stroke="#6B7280" stroke-width="2"/>
                                <text x="353" y="34" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-weight="700" font-size="13" fill="#374151">RT</text>
                            </g>

                            <!-- Botão Central Xbox -->
                            <circle cx="250" cy="102" r="14" fill="#FFFFFF" stroke="#16A34A" stroke-width="2.5"/>

                            <!-- Analógico Esquerdo + D-Pad (Movimentação) -->
                            <g class="xbox-part" id="svg-stick-left">
                                <circle cx="145" cy="132" r="28" fill="#FFFFFF" stroke="#16A34A" stroke-width="3"/>
                                <circle cx="145" cy="132" r="12" fill="#DCFCE7"/>

                                <!-- D-Pad -->
                                <rect x="178" y="172" width="18" height="48" rx="3" fill="#E5E7EB" stroke="#6B7280" stroke-width="1.5"/>
                                <rect x="163" y="187" width="48" height="18" rx="3" fill="#E5E7EB" stroke="#6B7280" stroke-width="1.5"/>
                            </g>

                            <!-- Botões A, B, X, Y (Pá Coletora) -->
                            <g class="xbox-part" id="svg-buttons-abxy">
                                <circle cx="355" cy="104" r="14" fill="#FFFFFF" stroke="#374151" stroke-width="2"/>
                                <text x="355" y="109" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-weight="700" font-size="12" fill="#111827">Y</text>

                                <circle cx="355" cy="160" r="14" fill="#16A34A" stroke="#16A34A" stroke-width="2"/>
                                <text x="355" y="165" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-weight="700" font-size="12" fill="#FFFFFF">A</text>

                                <circle cx="327" cy="132" r="14" fill="#FFFFFF" stroke="#374151" stroke-width="2"/>
                                <text x="327" y="137" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-weight="700" font-size="12" fill="#111827">X</text>

                                <circle cx="383" cy="132" r="14" fill="#FFFFFF" stroke="#374151" stroke-width="2"/>
                                <text x="383" y="137" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-weight="700" font-size="12" fill="#111827">B</text>
                            </g>

                            <!-- Analógico Direito -->
                            <circle cx="305" cy="192" r="22" fill="#FFFFFF" stroke="#9CA3AF" stroke-width="2"/>
                            <circle cx="305" cy="192" r="10" fill="#E5E7EB"/>
                        </svg>
                    </div>

                    <!-- Lista de Comandos à Direita -->
                    <div class="xbox-legend-col">
                        <div class="xbox-legend-item" data-target="buttons-abxy">
                            <span class="legend-key">Botão A / Botão Y</span>
                            <p>Aciona o motor da <strong>pá coletora</strong> (abaixar para recolher o resíduo e levantar para guardar no recipiente).</p>
                        </div>

                        <div class="xbox-legend-item" data-target="buttons-abxy">
                            <span class="legend-key">Botão B / Botão X</span>
                            <p>Parada rápida de segurança e ativação da leitura de alerta do <strong>sensor de distância</strong>.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fluxograma de Comunicação -->
            <div class="flowchart-container">
                <h3 class="flowchart-heading">Fluxograma de Operação do Sistema</h3>
                <div class="flowchart-track" role="list" aria-label="Fluxograma de funcionamento do RoboClean">
                    <div class="flow-node" role="listitem">
                        <strong>1. Controle Xbox</strong>
                        <small>Envia comandos do operador</small>
                    </div>

                    <div class="flow-arrow" aria-hidden="true">&rarr;</div>

                    <div class="flow-node" role="listitem">
                        <strong>2. Computador / Celular</strong>
                        <small>Recebe e transmite via Bluetooth</small>
                    </div>

                    <div class="flow-arrow" aria-hidden="true">&rarr;</div>

                    <div class="flow-node flow-node-highlight" role="listitem">
                        <strong>3. Hub SPIKE</strong>
                        <small>"Cérebro" programável do robô</small>
                    </div>

                    <div class="flow-arrow" aria-hidden="true">&rarr;</div>

                    <div class="flow-node" role="listitem">
                        <strong>4. Motores e Sensores</strong>
                        <small>Locomoção e desvio de obstáculos</small>
                    </div>

                    <div class="flow-arrow" aria-hidden="true">&rarr;</div>

                    <div class="flow-node flow-node-eco" role="listitem">
                        <strong>5. Coleta do Lixo</strong>
                        <small>Pá recolhe para o recipiente</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================================
         4. EQUIPE (DO BANCO DE DADOS MYSQL)
         ====================================================================== -->
    <section class="site-section section-alt" id="equipe">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Equipe</h2>
                <p class="section-subtitle">Estudantes responsáveis pelo planejamento, montagem, programação e apresentação na feira <?= e(EVENTO_NOME) ?>.</p>
            </div>

            <div class="team-grid">
                <?php foreach ($membrosEquipe as $membro): ?>
                    <?php
                    $fotoMembro = !empty($membro['foto']) ? $membro['foto'] : 'assets/img/membro_placeholder.svg';
                    $versaoFoto = (int) @filemtime(__DIR__ . '/' . $fotoMembro);
                    $srcFotoMembro = $fotoMembro . ($versaoFoto > 0 ? '?v=' . $versaoFoto : '');
                    ?>
                    <article class="team-card">
                        <div class="team-photo-wrap">
                            <img
                                src="<?= e($srcFotoMembro) ?>"
                                alt="Foto de <?= e($membro['nome']) ?>"
                                class="team-photo"
                                loading="lazy"
                                onerror="this.onerror=null;this.src='assets/img/membro_placeholder.svg';"
                            >
                        </div>
                        <div class="team-card-body">
                            <span class="team-member-tag">Equipe RoboClean</span>
                            <h3 class="team-member-name"><?= e($membro['nome']) ?></h3>
                            <p class="team-member-role"><?= e($membro['funcao']) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ======================================================================
         5. GALERIA (DO BANCO DE DADOS MYSQL)
         ====================================================================== -->
    <section class="site-section" id="galeria">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Galeria</h2>
                <p class="section-subtitle">Fotos e vídeos da construção, programação e testes do RoboClean (clique nas imagens para ampliar).</p>
            </div>

            <div class="gallery-grid">
                <?php foreach ($itensGaleria as $item): ?>
                    <figure class="gallery-card">
                        <?php if (($item['tipo'] ?? 'foto') === 'video'): ?>
                            <div class="gallery-media-wrap">
                                <video class="gallery-video" controls preload="metadata">
                                    <source src="<?= e($item['arquivo']) ?>" type="video/mp4">
                                    Seu navegador não suporta a reprodução de vídeo.
                                </video>
                                <span class="gallery-type-badge">Vídeo</span>
                            </div>
                        <?php else: ?>
                            <button
                                type="button"
                                class="gallery-media-wrap gallery-zoom-trigger"
                                data-src="<?= e($item['arquivo']) ?>"
                                data-caption="<?= e($item['titulo']) ?>"
                                aria-label="Ampliar imagem: <?= e($item['titulo']) ?>"
                            >
                                <img
                                    src="<?= e($item['arquivo']) ?>"
                                    alt="<?= e($item['titulo']) ?>"
                                    class="gallery-img"
                                    loading="lazy"
                                    onerror="this.onerror=null;this.src='assets/img/galeria_prototipo.svg';"
                                >
                                <span class="gallery-type-badge">Ampliar</span>
                            </button>
                        <?php endif; ?>

                        <figcaption class="gallery-caption">
                            <strong><?= e($item['titulo']) ?></strong>
                        </figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ======================================================================
         6. MURAL DE MENSAGENS (DO BANCO DE DADOS MYSQL)
         ====================================================================== -->
    <section class="site-section section-alt" id="mural">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Mural de Mensagens</h2>
                <p class="section-subtitle">Visitou nossa bancada na feira <strong><?= e(EVENTO_NOME) ?></strong>? Deixe seu recado, dúvida ou incentivo para a equipe.</p>
            </div>

            <div class="mural-layout">
                <!-- Coluna 1: Formulário de Envio -->
                <div class="mural-form-card">
                    <h3 class="mural-form-title">Deixe sua mensagem</h3>
                    <p class="mural-form-note">
                        As mensagens enviadas passam por aprovação no painel administrativo antes de aparecerem no mural.
                    </p>

                    <?php if ($feedbackMural): ?>
                        <div class="alert-box alert-<?= e($feedbackMural['tipo']) ?>" role="alert">
                            <?= e($feedbackMural['texto']) ?>
                        </div>
                    <?php endif; ?>

                    <form action="mensagens.php" method="POST" class="mural-form" id="muralForm">
                        <input type="hidden" name="csrf_token" value="<?= e(gerarCsrfToken()) ?>">

                        <div class="form-group">
                            <label for="campoNome">Seu Nome ou Turma *</label>
                            <input
                                type="text"
                                id="campoNome"
                                name="nome"
                                required
                                minlength="2"
                                maxlength="<?= (int) MSG_MAX_NOME ?>"
                                placeholder="Ex.: Maria (9º Ano) ou Prof. Carlos"
                                <?= !$db_disponivel ? 'disabled' : '' ?>
                            >
                        </div>

                        <div class="form-group">
                            <div class="label-with-counter">
                                <label for="campoTexto">Sua Mensagem *</label>
                                <small id="contadorCaracteres">0 / <?= (int) MSG_MAX_TEXTO ?></small>
                            </div>
                            <textarea
                                id="campoTexto"
                                name="texto"
                                rows="4"
                                required
                                minlength="3"
                                maxlength="<?= (int) MSG_MAX_TEXTO ?>"
                                placeholder="Escreva o que achou do RoboClean..."
                                <?= !$db_disponivel ? 'disabled' : '' ?>
                            ></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full" <?= !$db_disponivel ? 'disabled' : '' ?>>
                            Enviar Mensagem
                        </button>
                    </form>
                </div>

                <!-- Coluna 2: Mensagens Aprovadas -->
                <div class="mural-messages-col">
                    <h3 class="mural-list-title">Recados Aprovados (<?= count($mensagensAprovadas) ?>)</h3>

                    <?php if (empty($mensagensAprovadas)): ?>
                        <div class="mural-empty-box">
                            <p>Seja o primeiro visitante da feira <strong><?= e(EVENTO_NOME) ?></strong> a deixar uma mensagem para o RoboClean.</p>
                        </div>
                    <?php else: ?>
                        <div class="mural-messages-list">
                            <?php foreach ($mensagensAprovadas as $msg): ?>
                                <article class="mural-message-item">
                                    <div class="mural-msg-header">
                                        <span class="mural-author-avatar" aria-hidden="true">
                                            <?= e(mb_strtoupper(mb_substr($msg['nome'], 0, 1, 'UTF-8'), 'UTF-8')) ?>
                                        </span>
                                        <div class="mural-author-meta">
                                            <strong class="mural-author-name"><?= e($msg['nome']) ?></strong>
                                            <time class="mural-msg-date" datetime="<?= e($msg['data']) ?>">
                                                <?= e(date('d/m/Y \à\s H:i', strtotime($msg['data']))) ?>
                                            </time>
                                        </div>
                                    </div>
                                    <p class="mural-msg-body"><?= nl2br(e($msg['texto'])) ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ======================================================================
         7. CONCLUSÃO
         ====================================================================== -->
    <section class="conclusion-section" id="conclusao">
        <div class="container">
            <div class="conclusion-banner">
                <h2 class="conclusion-quote">
                    “Mais do que construir uma máquina, o <span>RoboClean</span> mostra que a robótica pode criar soluções para problemas reais.”
                </h2>
                <p class="conclusion-sub">
                    Obrigado por visitar nosso projeto na feira <strong><?= e(EVENTO_NOME) ?> <?= e(ANO_PROJETO) ?></strong>.
                </p>
            </div>
        </div>
    </section>

</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
