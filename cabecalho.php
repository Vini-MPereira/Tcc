<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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

        /* CORES DO SITE */

        :root {
            --azul-principal: #4F8A9A;
            --azul-escuro: #315F6B;
            --azul-claro: #EAF3F5;
            --texto: #26363B;
            --texto-secundario: #66777C;
            --fundo: #F7FAFB;
        }


        /* FUNDO GERAL */

        body {
            background-color: var(--fundo);
            color: var(--texto);
        }


        /* =========================================
           CABEÇALHO FIXO
        ========================================== */

        header {
            position: sticky;
            top: 0;
            z-index: 1030;
        }


        .navbar {
            background-color: #FFFFFF !important;
            min-height: 90px;
        }


        /* =========================================
           ÁREA DA LOGO
        ========================================== */

        .navbar-brand {
            text-decoration: none;
            display: flex;
            align-items: center;
        }


        /* LOGO GRANDE */

        .logo-limpalar {
            width: 100px;
            height: 80px;
            object-fit: contain;
            object-position: center;
        }


        /* NOME LIMPALAR */

        .nome-site {
            color: var(--azul-escuro);
            font-size: 1.35rem;
            line-height: 1.1;
        }


        /* SLOGAN */

        .slogan {
            color: var(--texto-secundario);
            font-size: 0.85rem;
        }


        /* =========================================
           LINKS DO MENU
        ========================================== */

        .menu-link {
            color: var(--texto) !important;

            padding: 10px 16px !important;

            border-radius: 8px;

            transition: all 0.3s ease;
        }


        /* EFEITO AO PASSAR O MOUSE */

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

            color: #FFFFFF;

            transform: translateY(-2px);

            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.10);
        }


        /* =========================================
           BOTÃO CADASTRAR
        ========================================== */

        .btn-cadastrar {
            background-color: var(--azul-principal);

            color: #FFFFFF;

            border: 1px solid var(--azul-principal);

            border-radius: 8px;

            transition: all 0.3s ease;
        }


        .btn-cadastrar:hover {
            background-color: var(--azul-escuro);

            border-color: var(--azul-escuro);

            color: #FFFFFF;

            transform: translateY(-2px);

            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.10);
        }


        /* =========================================
           BOTÃO MENU MOBILE
        ========================================== */

        .navbar-toggler {
            border-color: #D5E3E6;
        }


        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(79, 138, 154, 0.20);
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


        <div
            class="container-fluid px-4">


            <!-- =====================================
                 LOGO + NOME + SLOGAN
            ====================================== -->

            <a
                class="navbar-brand"
                href="index.php">


                <!-- LOGO -->

                <img
                    src="img/logo.png"
                    alt="Logo LimpaLar"
                    class="logo-limpalar">


                <!-- NOME E SLOGAN -->

                <div
                    class="d-none d-md-block ms-2">


                    <span
                        class="d-block fw-bold nome-site">

                        LimpaLar

                    </span>


                    <small
                        class="slogan">

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


                <span
                    class="navbar-toggler-icon">

                </span>


            </button>


            <!-- =====================================
                 MENU
            ====================================== -->

            <div
                class="collapse navbar-collapse"
                id="menuPrincipal">


                <!-- LINKS PRINCIPAIS -->

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


                </ul>


                <!-- =====================================
                     ÁREA DE LOGIN
                ====================================== -->

                <div
                    class="d-flex gap-2 area-login">


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


                </div>


            </div>


        </div>

    </nav>

</header>


<!-- =========================================
     AQUI COMEÇA O CONTEÚDO DA PÁGINA
========================================== -->

<main>