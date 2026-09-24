<?php

session_start();


// =====================================================
// ROTA PROTEGIDA
// =====================================================

if (!isset($_SESSION["ID_USUARIO"])) {

    header("Location: entrar.php");
    exit;

}


$id_usuario = $_SESSION["ID_USUARIO"];
$nome_usuario = $_SESSION["NOME_USUARIO"];

?>

<?php include "cabecalho.php"; ?>


<main>

    <section class="container py-5">

        <!-- CABEÇALHO -->

        <div class="text-center mb-5">

            <h1
                class="fw-bold"
                style="color: #315F6B;">

                Olá, <?php echo htmlspecialchars($nome_usuario); ?>!

            </h1>

            <p class="text-secondary">

                Bem-vindo ao seu painel do LimpaLar.

            </p>

        </div>


        <!-- OPÇÕES -->

        <div class="row g-4 justify-content-center">


            <!-- CADASTRAR ANÚNCIO -->

            <div class="col-12 col-md-6 col-lg-5">

                <div class="card border-0 shadow-sm rounded-4 h-100">

                    <div class="card-body p-4 text-center">

                        <div
                            class="mb-3"
                            style="font-size: 45px;">

                            🧹

                        </div>

                        <h3
                            class="fw-bold"
                            style="color: #315F6B;">

                            Oferecer meu serviço

                        </h3>

                        <p class="text-secondary">

                            Cadastre um anúncio e ofereça seus
                            serviços para pessoas que estão procurando
                            profissionais.

                        </p>

                        <a
                            href="cadastrar_anuncio.php"
                            class="btn btn-lg text-white px-4"
                            style="background-color: #3F7C8C;">

                            Criar anúncio

                        </a>

                    </div>

                </div>

            </div>


            <!-- LISTAGEM DE SERVIÇOS -->

            <div class="col-12 col-md-6 col-lg-5">

                <div class="card border-0 shadow-sm rounded-4 h-100">

                    <div class="card-body p-4 text-center">

                        <div
                            class="mb-3"
                            style="font-size: 45px;">

                            🔎

                        </div>

                        <h3
                            class="fw-bold"
                            style="color: #315F6B;">

                            Procurar serviços

                        </h3>

                        <p class="text-secondary">

                            Encontre profissionais e veja os serviços
                            disponíveis na sua região.

                        </p>

                        <a
                            href="ofertas.php"
                            class="btn btn-lg text-white px-4"
                            style="background-color: #3F7C8C;">

                            Ver ofertas

                        </a>

                    </div>

                </div>

            </div>


        </div>


        <!-- OUTRAS OPÇÕES -->

        <div class="text-center mt-5">

            <a
                href="sair.php"
                class="btn btn-outline-secondary">

                Sair da conta

            </a>

        </div>


    </section>

</main>


<?php include "rodape.php"; ?>
