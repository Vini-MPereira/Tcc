
<?php
// =====================================================
// CABEÇALHO DO LIMPA LAR
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario_logado = !empty($_SESSION["ID_USUARIO"]);
$pagina_atual = basename($_SERVER["SCRIPT_NAME"] ?? "");

// =====================================================
// AVALIAÇÃO OBRIGATÓRIA APÓS FAXINA CONCLUÍDA
// =====================================================
// O cliente deve avaliar os atendimentos concluídos
// antes de navegar pelas páginas comuns do LimpaLar.
// A própria tela de avaliações fica liberada.

if ($usuario_logado && $pagina_atual !== "avaliacoes.php") {

    try {
        require_once __DIR__ . "/conexao.php";

        $id_usuario_avaliacao = (int) $_SESSION["ID_USUARIO"];

        $sql_pendente = "
            SELECT C.ID_CONTRATACAO
            FROM CONTRATACAO C
            WHERE C.ID_CONTRATANTE = ?
              AND C.STATUS = 'CONCLUIDA'
              AND NOT EXISTS (
                  SELECT 1
                  FROM AVALIACAO A
                  WHERE A.ID_CONTRATACAO = C.ID_CONTRATACAO
              )
            LIMIT 1
        ";

        $stmt_pendente = mysqli_prepare(
            $conexao,
            $sql_pendente
        );

        mysqli_stmt_bind_param(
            $stmt_pendente,
            "i",
            $id_usuario_avaliacao
        );

        mysqli_stmt_execute($stmt_pendente);

        $avaliacao_pendente = mysqli_fetch_assoc(
            mysqli_stmt_get_result($stmt_pendente)
        );

        mysqli_stmt_close($stmt_pendente);

        if ($avaliacao_pendente && !headers_sent()) {
            header("Location: avaliacoes.php");
            exit;
        }

    } catch (Throwable $erro) {

        error_log(
            "LimpaLar - verificar avaliação: " .
            $erro->getMessage()
        );
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>LimpaLar</title>

    <!-- =========================================
         BOOTSTRAP
    ========================================== -->

    <link
        rel="stylesheet"
        href="bootstrap/css/bootstrap.css">

    <!-- =========================================
         ESTILO DO CABEÇALHO
    ========================================== -->

    <style>

        :root {
            --azul-principal: #18A999;
            --azul-escuro: #164E63;
            --azul-claro: #E7F6F3;
            --texto: #243438;
            --texto-secundario: #617477;
            --fundo: #F4F9F7;
        }

        body {
            background-color: var(--fundo);
            color: var(--texto);
        }

        header {
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .navbar {
            background-color: #FBFDFC !important;
            min-height: 90px;
        }

        .navbar-brand {
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .logo-limpalar {
            width: 100px;
            height: 80px;
            object-fit: contain;
            object-position: center;
        }

        .nome-site {
            color: var(--azul-escuro);
            font-size: 1.35rem;
            line-height: 1.1;
        }

        .slogan {
            color: var(--texto-secundario);
            font-size: 0.85rem;
        }

        .menu-link {
            color: var(--texto) !important;
            padding: 10px 16px !important;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .menu-link:hover {
            background-color: var(--azul-claro);
            color: var(--azul-principal) !important;
            transform: translateY(-2px);
        }

        /* =========================================
           BOTÃO ENTRAR
        ========================================== */

        .btn-entrar {
            color: var(--azul-principal);
            border: 1px solid var(--azul-principal);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-entrar:hover {
            background-color: var(--azul-principal);
            color: #FBFDFC;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.10);
        }

        /* =========================================
           BOTÃO CADASTRAR
        ========================================== */

        .btn-cadastrar {
            background-color: var(--azul-principal);
            color: #FBFDFC;
            border: 1px solid var(--azul-principal);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-cadastrar:hover {
            background-color: var(--azul-escuro);
            border-color: var(--azul-escuro);
            color: #FBFDFC;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.10);
        }

        /* =========================================
           BOTÃO PAINEL
        ========================================== */

        .btn-painel {
            background-color: var(--azul-principal);
            color: #FBFDFC;
            border: 1px solid var(--azul-principal);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-painel:hover {
            background-color: var(--azul-escuro);
            border-color: var(--azul-escuro);
            color: #FBFDFC;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.10);
        }

        /* =========================================
           BOTÃO SAIR
        ========================================== */

        .btn-sair {
            color: #617477;
            border: 1px solid #C9D8D5;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-sair:hover {
            background-color: #617477;
            border-color: #617477;
            color: #FBFDFC;
        }

        /* =========================================
           BOTÃO MENU MOBILE
        ========================================== */

        .navbar-toggler {
            border-color: #C9E2DE;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(24, 169, 153, 0.22);
        }

        /* =========================================
           RESPONSIVIDADE
        ========================================== */

        @media (max-width: 991px) {

            .navbar {
                min-height: 80px;
            }

            .logo-limpalar {
                width: 80px;
                height: 70px;
            }

            .menu-link {
                margin-top: 5px;
            }

            .area-login {
                margin-top: 10px;
                margin-bottom: 10px;
            }

        }

        @media (max-width: 575px) {

            .navbar {
                min-height: 75px;
            }

            .logo-limpalar {
                width: 70px;
                height: 60px;
            }

        }

    </style>

</head>

<body>

<!-- =========================================
     CABEÇALHO
========================================== -->

<header>

    <nav
        class="navbar navbar-expand-lg border-bottom shadow-sm">

        <div class="container-fluid px-4">

            <!-- =====================================
                 LOGO + NOME + SLOGAN
            ====================================== -->

            <a
                class="navbar-brand"
                href="index.php">

                <img
                    src="img/logo.png"
                    alt="Logo LimpaLar"
                    class="logo-limpalar">

                <div class="d-none d-md-block ms-2">

                    <span class="d-block fw-bold nome-site">
                        LimpaLar
                    </span>

                    <small class="slogan">
                        Cuidado que transforma seu lar
                    </small>

                </div>

            </a>

            <!-- =====================================
                 BOTÃO DO MENU NO CELULAR
            ====================================== -->

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menuPrincipal"
                aria-controls="menuPrincipal"
                aria-expanded="false"
                aria-label="Abrir menu">

                <span class="navbar-toggler-icon"></span>

            </button>

            <!-- =====================================
                 MENU
            ====================================== -->

            <div
                class="collapse navbar-collapse"
                id="menuPrincipal">

                <ul
                    class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-2">

                    <!-- INÍCIO -->

                    <li class="nav-item">

                        <a
                            class="nav-link menu-link"
                            href="index.php">

                            Início

                        </a>

                    </li>

                    <!-- PROFISSIONAIS -->

                    <li class="nav-item">

                        <a
                            class="nav-link menu-link"
                            href="profissionais.php">

                            Profissionais

                        </a>

                    </li>

                    <!-- SUPORTE -->

                    <li class="nav-item">

                        <a
                            class="nav-link menu-link"
                            href="suporte.php">

                            Suporte

                        </a>

                    </li>

                    <?php if ($usuario_logado): ?>

                        <!-- PAINEL -->

                        <li class="nav-item">

                            <a
                                class="nav-link menu-link fw-semibold"
                                href="painel.php">

                                Meu painel

                            </a>

                        </li>

                    <?php endif; ?>

                </ul>

                <!-- =====================================
                     ÁREA DE LOGIN
                ====================================== -->

                <div class="d-flex gap-2 area-login">

                    <?php if ($usuario_logado): ?>

                        <!-- PAINEL -->

                        <a
                            href="painel.php"
                            class="btn btn-painel px-4">

                            Meu painel

                        </a>

                        <!-- SAIR -->

                        <a
                            href="sair.php"
                            class="btn btn-sair px-4">

                            Sair

                        </a>

                    <?php else: ?>

                        <!-- ENTRAR -->

                        <a
                            href="entrar.php"
                            class="btn btn-entrar px-4">

                            Entrar

                        </a>

                        <!-- CADASTRAR -->

                        <a
                            href="cadastrar.php"
                            class="btn btn-cadastrar px-4">

                            Cadastrar

                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </nav>

</header>

<!-- =====================================================
     ATUALIZAÇÃO AUTOMÁTICA DA AVALIAÇÃO PENDENTE
====================================================== -->

<?php if ($usuario_logado): ?>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const paginaAtual = <?= json_encode(
        $pagina_atual,
        JSON_UNESCAPED_UNICODE
    ); ?>;

    let verificando = false;

    // =================================================
    // VERIFICA SE O CLIENTE PRECISA AVALIAR
    // =================================================

    async function verificarAvaliacaoPendente() {

        if (verificando) {
            return;
        }

        verificando = true;

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

            if (
                !dados.sucesso ||
                Number(dados.avaliacoes) < 1
            ) {
                return;
            }

            // =========================================
            // SE JÁ ESTÁ NA TELA DE AVALIAÇÕES
            // =========================================

            if (paginaAtual === 'avaliacoes.php') {

                // Atualiza a tela caso uma nova faxina
                // tenha sido concluída enquanto ela
                // estava aberta sem um formulário.

                if (
                    !document.getElementById(
                        'formAvaliacao'
                    )
                ) {

                    window.location.reload();
                }

            } else {

                // =====================================
                // DIRECIONA PARA AVALIAÇÃO OBRIGATÓRIA
                // =====================================

                window.location.replace(
                    'avaliacoes.php'
                );
            }

        } catch (erro) {

            console.warn(
                'Não foi possível consultar avaliações.',
                erro
            );

        } finally {

            verificando = false;
        }
    }

    // =================================================
    // VERIFICA A CADA 10 SEGUNDOS
    // =================================================

    setInterval(
        verificarAvaliacaoPendente,
        10000
    );

    // Verifica ao voltar para a aba do navegador.

    document.addEventListener(
        'visibilitychange',
        function () {

            if (!document.hidden) {
                verificarAvaliacaoPendente();
            }
        }
    );

});

</script>

<?php endif; ?>

<!-- =========================================
     AQUI COMEÇA O CONTEÚDO DA PÁGINA
========================================== -->

<main>
