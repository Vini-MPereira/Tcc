
<?php
// =====================================================
// LIMPA LAR - CABEÇALHO COMPARTILHADO
// =====================================================
// Este arquivo abre <main>.
// Cada página fecha </main> antes do rodape.php.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario_logado = !empty($_SESSION['ID_USUARIO']);
$pagina_atual = basename($_SERVER['SCRIPT_NAME'] ?? '');
$nome_usuario = trim((string) ($_SESSION['NOME_USUARIO'] ?? ''));

$primeiro_nome = $nome_usuario !== ''
    ? (preg_split('/\s+/u', $nome_usuario)[0] ?? 'Minha conta')
    : 'Minha conta';

$qtd_solicitacoes = 0;
$qtd_avaliacoes = 0;
$notificacoes_disponiveis = true;

// =====================================================
// CONTAR NOTIFICAÇÕES
// =====================================================

if ($usuario_logado) {
    try {
        require_once __DIR__ . '/conexao.php';

        $id_usuario = (int) $_SESSION['ID_USUARIO'];

        $sql = "
            SELECT
                (
                    SELECT COUNT(*)
                    FROM CONTRATACAO
                    WHERE ID_PROFISSIONAL = ?
                      AND STATUS = 'SOLICITADA'
                ) AS solicitacoes,
                (
                    SELECT COUNT(*)
                    FROM CONTRATACAO C
                    WHERE C.ID_CONTRATANTE = ?
                      AND C.STATUS = 'CONCLUIDA'
                      AND NOT EXISTS (
                          SELECT 1
                          FROM AVALIACAO A
                          WHERE A.ID_CONTRATACAO = C.ID_CONTRATACAO
                      )
                ) AS avaliacoes
        ";

        $stmt = mysqli_prepare($conexao, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            'ii',
            $id_usuario,
            $id_usuario
        );

        mysqli_stmt_execute($stmt);

        $contadores = mysqli_fetch_assoc(
            mysqli_stmt_get_result($stmt)
        );

        mysqli_stmt_close($stmt);

        $qtd_solicitacoes =
            (int) ($contadores['solicitacoes'] ?? 0);

        $qtd_avaliacoes =
            (int) ($contadores['avaliacoes'] ?? 0);

        if (
            $qtd_avaliacoes > 0 &&
            $pagina_atual !== 'avaliacoes.php'
        ) {
            if (!headers_sent()) {
                header('Location: avaliacoes.php');
                exit;
            }
        }

    } catch (Throwable $erro) {
        $notificacoes_disponiveis = false;

        error_log(
            'LimpaLar - cabeçalho: ' .
            $erro->getMessage()
        );
    }
}

$total_notificacoes = $qtd_solicitacoes + $qtd_avaliacoes;

function escapar_cabecalho($valor)
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>LimpaLar</title>

    <link
        rel="stylesheet"
        href="bootstrap/css/bootstrap.css"
    >

    <style>
        :root {
            --azul-principal: #18A999;
            --azul-escuro: #164E63;
            --azul-claro: #E7F6F3;
            --texto: #243438;
            --texto-secundario: #617477;
            --fundo: #F4F9F7;

            --ll-verde-escuro: #126E68;
            --ll-verde: #18A999;
            --ll-verde-claro: #E7F5F1;
            --ll-texto: #243438;
            --ll-muted: #617477;
        }

        body {
            background: #F4F9F7;
            color: var(--ll-texto);
        }

        .limpalar-header {
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .limpalar-header .navbar {
            background: #FBFDFC;
            min-height: 78px;
        }

        .limpalar-header .navbar-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .limpalar-header .logo-limpalar {
            width: 76px;
            height: 62px;
            object-fit: contain;
        }

        .limpalar-header .nome-site {
            color: var(--ll-verde-escuro);
            font-size: 1.3rem;
            line-height: 1.15;
        }

        .limpalar-header .slogan {
            color: var(--ll-muted);
            font-size: .76rem;
        }

        .limpalar-header .menu-link {
            color: var(--ll-texto);
            border-radius: 9px;
            padding: 10px 12px !important;
        }

        .limpalar-header .menu-link:hover,
        .limpalar-header .menu-link.active {
            color: var(--ll-verde-escuro);
            background: var(--ll-verde-claro);
        }

        .limpalar-header .menu-link.active {
            font-weight: 700;
        }

        .limpalar-header .btn-primario {
            background: var(--ll-verde);
            color: #fff;
            border: 1px solid var(--ll-verde);
        }

        .limpalar-header .btn-primario:hover {
            background: var(--ll-verde-escuro);
            color: #fff;
            border-color: var(--ll-verde-escuro);
        }

        .limpalar-header .btn-contorno {
            color: var(--ll-verde-escuro);
            border: 1px solid var(--ll-verde);
        }

        .limpalar-header .btn-contorno:hover {
            background: var(--ll-verde-claro);
            color: var(--ll-verde-escuro);
        }

        .limpalar-header .btn-sino {
            width: 42px;
            height: 42px;
            border: 1px solid #D7E9E4;
            background: #fff;
            color: var(--ll-verde-escuro);
            border-radius: 12px;
        }

        .limpalar-header .btn-sino:hover {
            background: var(--ll-verde-claro);
        }

        .limpalar-header .sino-badge {
            font-size: .67rem;
            min-width: 20px;
        }

        .limpalar-header .menu-notificacoes {
            width: 330px;
            max-width: 90vw;
        }

        .limpalar-header .menu-notificacoes .dropdown-item {
            white-space: normal;
        }

        .limpalar-header .botao-conta {
            color: var(--ll-verde-escuro);
            border: 1px solid #D7E9E4;
            background: #fff;
            border-radius: 12px;
        }

        .limpalar-header .botao-conta:hover {
            background: var(--ll-verde-claro);
        }

        @media (max-width: 1199.98px) {
            .limpalar-header .logo-limpalar {
                width: 60px;
                height: 54px;
            }

            .limpalar-header .navbar {
                min-height: 70px;
            }

            .limpalar-header .navbar-collapse {
                padding: 12px 0;
            }

            .limpalar-header .menu-link {
                margin: 2px 0;
            }
        }

        @media (max-width: 575.98px) {
            .limpalar-header .slogan {
                display: none;
            }

            .limpalar-header .nome-site {
                font-size: 1.05rem;
            }

            .limpalar-header .logo-limpalar {
                width: 42px;
                height: 42px;
            }

            .limpalar-header .navbar-brand {
                min-width: 0;
                margin-right: 4px !important;
            }

            .limpalar-header .navbar-brand > span {
                min-width: 0;
            }

            .limpalar-header .btn-sino {
                width: 38px;
                height: 38px;
                padding: 5px;
            }

            .limpalar-header .navbar-toggler {
                padding: 5px 8px;
            }

            .limpalar-header .botao-conta {
                padding-left: 9px;
                padding-right: 9px;
            }

            /* Menu do sininho centralizado */
            .limpalar-header .menu-notificacoes {
                position: fixed !important;
                inset: 72px 12px auto 12px !important;
                transform: none !important;
                width: auto !important;
                max-width: none !important;
                box-sizing: border-box;
                max-height: calc(100dvh - 90px);
                overflow-y: auto;
                overflow-x: hidden;
                border-radius: 16px;
                z-index: 1050;
            }

            .limpalar-header .menu-notificacoes .dropdown-item {
                white-space: normal;
                overflow-wrap: anywhere;
                max-width: 100%;
            }

            .limpalar-header .botao-conta + .dropdown-menu {
                max-width: calc(100vw - 24px);
            }

            /* Notificação flutuante */
            .toast-container {
                left: 12px !important;
                right: 12px !important;
                bottom: 12px !important;
                width: auto !important;
                max-width: calc(100vw - 24px) !important;
                padding: 0 !important;
            }

            .toast-container .toast {
                width: 100%;
                max-width: 100%;
                overflow-wrap: anywhere;
            }

            .aviso-painel {
                max-width: 100%;
                min-width: 0;
                text-align: center;
                justify-content: center !important;
            }

            .aviso-painel > div {
                flex: 1 1 100%;
                min-width: 0;
                overflow-wrap: anywhere;
            }

            .aviso-painel .btn {
                max-width: 100%;
                white-space: normal;
            }

            main .card {
                max-width: 100%;
                min-width: 0;
            }
        }
    </style>
</head>

<body>

<header class="limpalar-header">
    <nav
        class="navbar navbar-expand-xl border-bottom shadow-sm"
        aria-label="Navegação principal"
    >
        <div class="container-fluid px-3 px-lg-4">

            <!-- LOGO -->
            <a class="navbar-brand me-2" href="index.php">
                <img
                    src="img/logo.png"
                    alt="Logo LimpaLar"
                    class="logo-limpalar"
                >

                <span>
                    <span class="d-block fw-bold nome-site">
                        LimpaLar
                    </span>
                    <small class="slogan">
                        Cuidado que transforma seu lar
                    </small>
                </span>
            </a>

            <!-- SININHO, CONTA E MENU MOBILE -->
            <div
                class="d-flex align-items-center gap-2 ms-auto order-xl-3 ms-xl-3"
            >

                <?php if ($usuario_logado): ?>

                    <!-- NOTIFICAÇÕES -->
                    <div class="dropdown">
                        <button
                            class="btn btn-sino position-relative"
                            type="button"
                            id="botaoNotificacoes"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Notificações"
                        >
                            <span aria-hidden="true">🔔</span>

                            <span
                                id="badgeNotificacoes"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger sino-badge <?= $total_notificacoes === 0 ? 'd-none' : ''; ?>"
                            >
                                <?= $total_notificacoes; ?>
                            </span>
                        </button>

                        <ul
                            class="dropdown-menu dropdown-menu-end menu-notificacoes p-2 shadow border-0"
                            aria-labelledby="botaoNotificacoes"
                        >
                            <li>
                                <h6
                                    class="dropdown-header fw-bold"
                                    style="color:#126E68;"
                                >
                                    Suas notificações
                                </h6>
                            </li>

                            <li
                                id="itemNovaSolicitacao"
                                class="<?= $qtd_solicitacoes === 0 ? 'd-none' : ''; ?>"
                            >
                                <a
                                    class="dropdown-item rounded-3 py-2"
                                    href="concluir_faxina.php?aba=recebi"
                                >
                                    <strong>
                                        🧹 Solicitações recebidas
                                    </strong>

                                    <small
                                        class="d-block text-secondary"
                                        id="textoNovasSolicitacoes"
                                    >
                                        <?= $qtd_solicitacoes; ?> aguardando resposta
                                    </small>
                                </a>
                            </li>

                            <li
                                id="itemAvaliacaoPendente"
                                class="<?= $qtd_avaliacoes === 0 ? 'd-none' : ''; ?>"
                            >
                                <a
                                    class="dropdown-item rounded-3 py-2"
                                    href="avaliacoes.php"
                                >
                                    <strong>
                                        ⭐ Avaliações pendentes
                                    </strong>

                                    <small
                                        class="d-block text-secondary"
                                        id="textoAvaliacoesPendentes"
                                    >
                                        <?= $qtd_avaliacoes; ?> aguardando sua nota
                                    </small>
                                </a>
                            </li>

                            <li
                                id="semNotificacoes"
                                class="px-3 py-2 text-secondary small <?= ($total_notificacoes > 0 || !$notificacoes_disponiveis) ? 'd-none' : ''; ?>"
                            >
                                Nenhuma pendência por enquanto. ✅
                            </li>

                            <?php if (!$notificacoes_disponiveis): ?>
                                <li
                                    id="erroNotificacoes"
                                    class="px-3 py-2 text-danger small"
                                >
                                    Não foi possível consultar as notificações.
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- CONTA -->
                    <div class="dropdown">
                        <button
                            class="btn botao-conta dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <span aria-hidden="true">👤</span>
                            <span class="d-none d-md-inline ms-1">
                                <?= escapar_cabecalho($primeiro_nome); ?>
                            </span>
                        </button>

                        <ul
                            class="dropdown-menu dropdown-menu-end shadow border-0"
                        >
                            <li>
                                <span class="dropdown-header">
                                    Olá, <?= escapar_cabecalho($primeiro_nome); ?>!
                                </span>
                            </li>

                            <li>
                                <a class="dropdown-item" href="painel.php">
                                    Meu painel
                                </a>
                            </li>

                            <li>
                                <a
                                    class="dropdown-item"
                                    href="concluir_faxina.php"
                                >
                                    Minhas faxinas
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="avaliacoes.php">
                                    Minhas avaliações
                                </a>
                            </li>

                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>
                                <a
                                    class="dropdown-item text-danger"
                                    href="sair.php"
                                >
                                    Sair da conta
                                </a>
                            </li>
                        </ul>
                    </div>

                <?php else: ?>

                    <a
                        href="entrar.php"
                        class="btn btn-contorno d-none d-sm-inline-block"
                    >
                        Entrar
                    </a>

                    <a
                        href="cadastrar.php"
                        class="btn btn-primario d-none d-sm-inline-block"
                    >
                        Cadastrar
                    </a>

                <?php endif; ?>

                <!-- MENU HAMBÚRGUER -->
                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#menuPrincipal"
                    aria-controls="menuPrincipal"
                    aria-expanded="false"
                    aria-label="Abrir menu"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

            </div>

            <!-- MENU PRINCIPAL -->
            <div
                class="collapse navbar-collapse order-xl-2"
                id="menuPrincipal"
            >
                <ul class="navbar-nav mx-auto mb-2 mb-xl-0 gap-xl-1">

                    <li class="nav-item">
                        <a
                            href="index.php"
                            class="nav-link menu-link <?= $pagina_atual === 'index.php' ? 'active' : ''; ?>"
                            <?= $pagina_atual === 'index.php' ? 'aria-current="page"' : ''; ?>
                        >
                            Início
                        </a>
                    </li>

                    <li class="nav-item">
                        <a
                            href="profissionais.php"
                            class="nav-link menu-link <?= $pagina_atual === 'profissionais.php' ? 'active' : ''; ?>"
                            <?= $pagina_atual === 'profissionais.php' ? 'aria-current="page"' : ''; ?>
                        >
                            Profissionais
                        </a>
                    </li>

                    <?php if ($usuario_logado): ?>

                        <li class="nav-item">
                            <a
                                href="painel.php"
                                class="nav-link menu-link <?= $pagina_atual === 'painel.php' ? 'active' : ''; ?>"
                                <?= $pagina_atual === 'painel.php' ? 'aria-current="page"' : ''; ?>
                            >
                                Meu painel
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                href="concluir_faxina.php"
                                class="nav-link menu-link <?= $pagina_atual === 'concluir_faxina.php' ? 'active' : ''; ?>"
                                <?= $pagina_atual === 'concluir_faxina.php' ? 'aria-current="page"' : ''; ?>
                            >
                                Minhas faxinas
                            </a>
                        </li>

                        <li class="nav-item">
                            <a
                                href="avaliacoes.php"
                                class="nav-link menu-link <?= $pagina_atual === 'avaliacoes.php' ? 'active' : ''; ?>"
                                <?= $pagina_atual === 'avaliacoes.php' ? 'aria-current="page"' : ''; ?>
                            >
                                Avaliações
                            </a>
                        </li>

                    <?php endif; ?>

                    <li class="nav-item">
                        <a
                            href="suporte.php"
                            class="nav-link menu-link <?= $pagina_atual === 'suporte.php' ? 'active' : ''; ?>"
                            <?= $pagina_atual === 'suporte.php' ? 'aria-current="page"' : ''; ?>
                        >
                            Suporte
                        </a>
                    </li>

                    <?php if (!$usuario_logado): ?>

                        <li class="nav-item d-sm-none">
                            <a
                                href="entrar.php"
                                class="nav-link menu-link"
                            >
                                Entrar
                            </a>
                        </li>

                        <li class="nav-item d-sm-none">
                            <a
                                href="cadastrar.php"
                                class="nav-link menu-link"
                            >
                                Cadastrar
                            </a>
                        </li>

                    <?php endif; ?>

                </ul>
            </div>

        </div>
    </nav>
</header>

<!-- =====================================================
     NOTIFICAÇÕES AUTOMÁTICAS
====================================================== -->

<?php if ($usuario_logado): ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const paginaAtual = <?= json_encode(
        $pagina_atual,
        JSON_HEX_TAG | JSON_HEX_AMP |
        JSON_HEX_APOS | JSON_HEX_QUOT
    ); ?>;

    let anteriores = {
        solicitacoes: <?= (int) $qtd_solicitacoes; ?>,
        avaliacoes: <?= (int) $qtd_avaliacoes; ?>
    };

    let consultando = false;
    let tempoAviso = null;

    // ATUALIZA O SININHO E SEUS CONTADORES
    function atualizarContadores(solicitacoes, avaliacoes) {

        const total = solicitacoes + avaliacoes;

        const badge = document.getElementById(
            'badgeNotificacoes'
        );

        const itemSolicitacao = document.getElementById(
            'itemNovaSolicitacao'
        );

        const textoSolicitacao = document.getElementById(
            'textoNovasSolicitacoes'
        );

        const itemAvaliacao = document.getElementById(
            'itemAvaliacaoPendente'
        );

        const textoAvaliacao = document.getElementById(
            'textoAvaliacoesPendentes'
        );

        const vazio = document.getElementById(
            'semNotificacoes'
        );

        if (badge) {
            badge.textContent = total > 99
                ? '99+'
                : String(total);

            badge.classList.toggle(
                'd-none',
                total === 0
            );
        }

        if (itemSolicitacao) {
            itemSolicitacao.classList.toggle(
                'd-none',
                solicitacoes === 0
            );
        }

        if (textoSolicitacao) {
            textoSolicitacao.textContent =
                `${solicitacoes} aguardando resposta`;
        }

        if (itemAvaliacao) {
            itemAvaliacao.classList.toggle(
                'd-none',
                avaliacoes === 0
            );
        }

        if (textoAvaliacao) {
            textoAvaliacao.textContent =
                `${avaliacoes} aguardando sua nota`;
        }

        if (vazio) {
            vazio.classList.toggle(
                'd-none',
                total !== 0
            );
        }
    }

    // AVISO FLUTUANTE DE NOVA SOLICITAÇÃO
    function mostrarNovaSolicitacao() {

        // O painel já tem seu próprio aviso.
        if (paginaAtual === 'painel.php') {
            return;
        }

        let aviso = document.getElementById(
            'avisoNovaSolicitacaoLL'
        );

        if (!aviso) {

            aviso = document.createElement('div');

            aviso.id = 'avisoNovaSolicitacaoLL';

            aviso.className =
                'alert alert-success shadow border-0';

            aviso.setAttribute('role', 'status');

            aviso.style.cssText = `
                position: fixed;
                top: 88px;
                left: 12px;
                right: 12px;
                max-width: 440px;
                margin: 0 auto;
                z-index: 2000;
                box-sizing: border-box;
                overflow-wrap: anywhere;
                border-radius: 14px;
                text-align: center;
            `;

            aviso.innerHTML = `
                <strong>🔔 Nova solicitação de faxina!</strong>

                <div class="small mt-1 mb-2">
                    Você recebeu um novo pedido.
                </div>

                <a
                    href="concluir_faxina.php?aba=recebi"
                    class="btn btn-success btn-sm"
                >
                    Ver solicitação
                </a>
            `;

            document.body.appendChild(aviso);
        }

        aviso.classList.remove('d-none');

        clearTimeout(tempoAviso);

        tempoAviso = setTimeout(function () {
            aviso.classList.add('d-none');
        }, 8000);
    }

    // CONSULTAR NOTIFICAÇÕES NO SERVIDOR
    async function atualizarNotificacoes() {

        if (consultando) {
            return;
        }

        consultando = true;

        try {

            const resposta = await fetch(
                'painel.php?notificacoes=1',
                {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!resposta.ok) {
                return;
            }

            const dados = await resposta.json();

            if (!dados.sucesso) {
                return;
            }

            const solicitacoes = Math.max(
                0,
                Number(dados.solicitacoes) || 0
            );

            const avaliacoes = Math.max(
                0,
                Number(dados.avaliacoes) || 0
            );

            atualizarContadores(
                solicitacoes,
                avaliacoes
            );

            // MOSTRAR AVISO DE NOVO PEDIDO
            if (
                solicitacoes > anteriores.solicitacoes
            ) {
                mostrarNovaSolicitacao();
            }

            // AVALIAÇÃO OBRIGATÓRIA
            if (
                avaliacoes > 0 &&
                paginaAtual !== 'avaliacoes.php'
            ) {
                window.location.replace(
                    'avaliacoes.php'
                );
                return;
            }

            if (
                paginaAtual === 'avaliacoes.php' &&
                anteriores.avaliacoes === 0 &&
                avaliacoes > 0 &&
                !document.getElementById('formAvaliacao')
            ) {
                window.location.reload();
                return;
            }

            anteriores = {
                solicitacoes,
                avaliacoes
            };

            const erro = document.getElementById(
                'erroNotificacoes'
            );

            if (erro) {
                erro.classList.add('d-none');
            }

        } catch (erro) {

            console.warn(
                'Falha na consulta das notificações:',
                erro
            );

        } finally {

            consultando = false;
        }
    }

    // PRIMEIRA CONSULTA IMEDIATA
    atualizarNotificacoes();

    // NOVA CONSULTA A CADA 3 SEGUNDOS
    setInterval(
        atualizarNotificacoes,
        3000
    );

    // VERIFICAR AO VOLTAR PARA A ABA
    document.addEventListener(
        'visibilitychange',
        function () {
            if (!document.hidden) {
                atualizarNotificacoes();
            }
        }
    );

    window.addEventListener(
        'focus',
        atualizarNotificacoes
    );

});
</script>

<?php endif; ?>

<!-- O CABEÇALHO ABRE O CONTEÚDO PRINCIPAL -->

<main>
