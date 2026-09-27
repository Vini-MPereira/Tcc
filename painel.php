<?php

session_start();



// =====================================================

// VERIFICA SE O USUÁRIO ESTÁ LOGADO

// =====================================================

if (!isset($_SESSION["ID_USUARIO"])) {

    header("Location: entrar.php");

    exit;

}



// =====================================================

// DADOS DO USUÁRIO

// =====================================================

$id_usuario = $_SESSION["ID_USUARIO"];

$nome_usuario = $_SESSION["NOME_USUARIO"];

?>



<?php include "cabecalho.php"; ?>



<main>

<section class="container py-5">



    <!-- =================================================
         CABEÇALHO
    ================================================== -->

    <div class="text-center mb-5">

        <h1
            class="fw-bold"
            style="color: #126E68;">

            Olá, <?php echo htmlspecialchars($nome_usuario); ?>! 👋

        </h1>



        <p
            style="color: #617477;">

            O que você deseja fazer hoje?

        </p>

    </div>





    <!-- =================================================
         OPÇÕES DO PAINEL
    ================================================== -->

    <div class="row g-4 justify-content-center">



        <!-- =================================================
             OFERECER SERVIÇO
        ================================================== -->

        <div class="col-12 col-md-6">

            <div
                class="card h-100 border-0 shadow-sm rounded-4">

                <div
                    class="card-body p-4 p-md-5 text-center">



                    <div
                        class="fs-1 mb-3">

                        🧹

                    </div>



                    <h2
                        class="fw-bold"
                        style="color: #126E68;">

                        Oferecer meu serviço

                    </h2>



                    <p
                        class="mt-3"
                        style="color: #617477;">

                        Cadastre seu serviço de faxina
                        e encontre clientes em Bauru.

                    </p>



                    <a
                        href="cadastrar_anuncio.php"
                        class="btn btn-lg text-white px-4 mt-3"
                        style="
                            background-color: #18A999;
                            border-color: #18A999;
                        ">

                        🧹 Oferecer meu serviço

                    </a>

                </div>

            </div>

        </div>





        <!-- =================================================
             PROCURAR SERVIÇOS
        ================================================== -->

        <div class="col-12 col-md-6">

            <div
                class="card h-100 border-0 shadow-sm rounded-4">

                <div
                    class="card-body p-4 p-md-5 text-center">



                    <div
                        class="fs-1 mb-3">

                        🔎

                    </div>



                    <h2
                        class="fw-bold"
                        style="color: #126E68;">

                        Procurar serviços

                    </h2>



                    <p
                        class="mt-3"
                        style="color: #617477;">

                        Encontre profissionais de faxina
                        disponíveis em Bauru.

                    </p>



                    <a
                        href="profissionais.php"
                        class="btn btn-lg text-white px-4 mt-3"
                        style="
                            background-color: #18A999;
                            border-color: #18A999;
                        ">

                        🔎 Procurar serviços

                    </a>

                </div>

            </div>

        </div>



    </div>





    <!-- =================================================
         BOTÃO SAIR
    ================================================== -->

    <div class="text-center mt-5">

        <a
            href="sair.php"
            class="btn btn-outline-secondary px-4">

            Sair da conta

        </a>

    </div>



</section>

</main>



<?php include "rodape.php"; ?>