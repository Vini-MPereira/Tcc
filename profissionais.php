<?php

include "cabecalho.php";


// =====================================================
// CONEXÃO COM O BANCO
// =====================================================

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "plataforma_servicos";

$conexao = mysqli_connect(
    $host,
    $usuario,
    $senha,
    $banco
);

if (!$conexao) {
    die("Erro ao conectar ao banco: " . mysqli_connect_error());
}

mysqli_set_charset($conexao, "utf8mb4");


// =====================================================
// FILTROS
// =====================================================

$busca = isset($_GET["busca"])
    ? trim($_GET["busca"])
    : "";

$categoria = isset($_GET["categoria"])
    ? intval($_GET["categoria"])
    : 0;

$cidade = isset($_GET["cidade"])
    ? trim($_GET["cidade"])
    : "";


// =====================================================
// CATEGORIAS
// =====================================================

$sqlCategorias = "
    SELECT
        ID_CATEGORIA,
        NOME

    FROM CATEGORIA

    WHERE ATIVO = 1

    ORDER BY NOME
";

$resultCategorias = mysqli_query(
    $conexao,
    $sqlCategorias
);


// =====================================================
// CONSULTA DOS ANÚNCIOS
// =====================================================

$sql = "

    SELECT

        A.ID_ANUNCIO,
        A.TITULO,
        A.DESCRICAO,
        A.PRECO,
        A.TIPO_PRECO,
        A.FOTO,
        A.ESTADO,
        A.CIDADE,
        A.BAIRRO,
        A.STATUS,
        A.DATA_CADASTRO,

        U.ID_USUARIO,
        U.NOME AS NOME_USUARIO,
        U.FOTO AS FOTO_USUARIO,
        U.TELEFONE,

        C.ID_CATEGORIA,
        C.NOME AS NOME_CATEGORIA,

        COALESCE(
            AVG(AV.NOTA),
            0
        ) AS MEDIA_AVALIACAO,

        COUNT(AV.ID_AVALIACAO)
            AS TOTAL_AVALIACOES

    FROM ANUNCIO A


    INNER JOIN USUARIO U

        ON U.ID_USUARIO = A.ID_USUARIO


    INNER JOIN CATEGORIA C

        ON C.ID_CATEGORIA = A.ID_CATEGORIA


    LEFT JOIN CONTRATACAO CT

        ON CT.ID_ANUNCIO = A.ID_ANUNCIO


    LEFT JOIN AVALIACAO AV

        ON AV.ID_CONTRATACAO = CT.ID_CONTRATACAO


    WHERE

        A.STATUS = 'ATIVO'

        AND U.ATIVO = 1
";


// =====================================================
// FILTRO DE BUSCA
// =====================================================

if ($busca !== "") {

    $buscaSegura = mysqli_real_escape_string(
        $conexao,
        $busca
    );

    $sql .= "

        AND (

            A.TITULO LIKE '%$buscaSegura%'

            OR A.DESCRICAO LIKE '%$buscaSegura%'

            OR U.NOME LIKE '%$buscaSegura%'

            OR C.NOME LIKE '%$buscaSegura%'

        )

    ";
}


// =====================================================
// FILTRO CATEGORIA
// =====================================================

if ($categoria > 0) {

    $sql .= "

        AND A.ID_CATEGORIA = $categoria

    ";
}


// =====================================================
// FILTRO CIDADE
// =====================================================

if ($cidade !== "") {

    $cidadeSegura = mysqli_real_escape_string(
        $conexao,
        $cidade
    );

    $sql .= "

        AND A.CIDADE LIKE '%$cidadeSegura%'

    ";
}


// =====================================================
// AGRUPAMENTO
// =====================================================

$sql .= "

    GROUP BY

        A.ID_ANUNCIO,
        A.TITULO,
        A.DESCRICAO,
        A.PRECO,
        A.TIPO_PRECO,
        A.FOTO,
        A.ESTADO,
        A.CIDADE,
        A.BAIRRO,
        A.STATUS,
        A.DATA_CADASTRO,

        U.ID_USUARIO,
        U.NOME,
        U.FOTO,
        U.TELEFONE,

        C.ID_CATEGORIA,
        C.NOME


    ORDER BY
        A.DATA_CADASTRO DESC

";


// =====================================================
// EXECUTA CONSULTA
// =====================================================

$resultAnuncios = mysqli_query(
    $conexao,
    $sql
);

if (!$resultAnuncios) {

    die(
        "Erro ao consultar anúncios: "
        . mysqli_error($conexao)
    );

}


// =====================================================
// FUNÇÃO PREÇO
// =====================================================

function formatarPreco($preco)
{

    if (
        $preco === null ||
        $preco === ""
    ) {

        return "A combinar";

    }

    return "R$ "
        . number_format(
            $preco,
            2,
            ",",
            "."
        );

}


// =====================================================
// TIPO DE PREÇO
// =====================================================

function tipoPreco($tipo)
{

    switch ($tipo) {

        case "HORA":
            return "por hora";

        case "DIARIA":
            return "por diária";

        case "SERVICO":
            return "por serviço";

        default:
            return "";

    }

}

?>


<main>


    <!-- =========================================
         TÍTULO
    ========================================== -->

    <section class="container py-5">

        <div class="text-center">


            <h1
                class="fw-bold"
                style="color: #315F6B;">

                Encontre um profissional

            </h1>


            <p
                class="lead text-secondary">

                Escolha um profissional para cuidar
                da sua casa.

            </p>


        </div>

    </section>



    <!-- =========================================
         FILTROS
    ========================================== -->

    <section class="container pb-4">

        <form
            method="GET"
            class="card border-0 shadow-sm rounded-4 p-3">


            <div class="row g-3">


                <!-- BUSCA -->

                <div class="col-12 col-lg-5">

                    <label
                        class="form-label fw-semibold">

                        O que você procura?

                    </label>

                    <input
                        type="text"
                        name="busca"
                        class="form-control"
                        placeholder="Ex.: limpeza residencial"
                        value="<?= htmlspecialchars($busca) ?>">

                </div>


                <!-- CATEGORIA -->

                <div class="col-12 col-sm-6 col-lg-3">

                    <label
                        class="form-label fw-semibold">

                        Categoria

                    </label>

                    <select
                        name="categoria"
                        class="form-select">


                        <option value="0">

                            Todas as categorias

                        </option>


                        <?php

                        while (
                            $cat =
                            mysqli_fetch_assoc(
                                $resultCategorias
                            )
                        ) {

                        ?>

                            <option
                                value="<?= $cat["ID_CATEGORIA"] ?>"
                                <?= (
                                    $categoria ==
                                    $cat["ID_CATEGORIA"]
                                )
                                    ? "selected"
                                    : ""
                                ?>>

                                <?= htmlspecialchars(
                                    $cat["NOME"]
                                ) ?>

                            </option>

                        <?php } ?>


                    </select>

                </div>


                <!-- CIDADE -->

                <div class="col-12 col-sm-6 col-lg-2">

                    <label
                        class="form-label fw-semibold">

                        Cidade

                    </label>

                    <input
                        type="text"
                        name="cidade"
                        class="form-control"
                        placeholder="Sua cidade"
                        value="<?= htmlspecialchars($cidade) ?>">

                </div>


                <!-- BOTÃO -->

                <div
                    class="col-12 col-lg-2 d-flex align-items-end">

                    <button
                        type="submit"
                        class="btn w-100 text-white"
                        style="background-color: #3F7C8C;">

                        Buscar

                    </button>

                </div>


            </div>


        </form>

    </section>



    <!-- =========================================
         CARDS
    ========================================== -->

    <section class="container pb-5">


        <?php if (
            mysqli_num_rows($resultAnuncios) > 0
        ) { ?>


            <div
                class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">


                <?php

                while (
                    $anuncio =
                    mysqli_fetch_assoc(
                        $resultAnuncios
                    )
                ) {

                ?>


                    <!-- =================================
                         CARD
                    ================================== -->

                    <div class="col">

                        <div
                            class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">


                            <!-- FOTO DO ANÚNCIO -->

                            <?php if (
                                !empty(
                                    $anuncio["FOTO"]
                                )
                            ) { ?>

                                <img
                                    src="<?= htmlspecialchars(
                                        $anuncio["FOTO"]
                                    ) ?>"
                                    class="card-img-top"
                                    style="
                                        height: 230px;
                                        object-fit: cover;
                                    "
                                    alt="<?= htmlspecialchars(
                                        $anuncio["TITULO"]
                                    ) ?>">

                            <?php } else { ?>

                                <img
                                    src="img/inicio.jfif"
                                    class="card-img-top"
                                    style="
                                        height: 230px;
                                        object-fit: cover;
                                    "
                                    alt="Serviço LimpaLar">

                            <?php } ?>


                            <!-- CONTEÚDO -->

                            <div
                                class="card-body d-flex flex-column">


                                <!-- CATEGORIA -->

                                <span
                                    class="badge rounded-pill align-self-start mb-2"
                                    style="
                                        background-color: #EAF3F5;
                                        color: #315F6B;
                                    ">

                                    <?= htmlspecialchars(
                                        $anuncio[
                                            "NOME_CATEGORIA"
                                        ]
                                    ) ?>

                                </span>


                                <!-- TÍTULO -->

                                <h5
                                    class="fw-bold mb-2">

                                    <?= htmlspecialchars(
                                        $anuncio["TITULO"]
                                    ) ?>

                                </h5>


                                <!-- PROFISSIONAL -->

                                <p
                                    class="mb-2 text-secondary">

                                    <strong>
                                        Profissional:
                                    </strong>

                                    <?= htmlspecialchars(
                                        $anuncio[
                                            "NOME_USUARIO"
                                        ]
                                    ) ?>

                                </p>


                                <!-- DESCRIÇÃO -->

                                <p
                                    class="text-secondary mb-3"
                                    style="
                                        display: -webkit-box;
                                        -webkit-line-clamp: 3;
                                        -webkit-box-orient: vertical;
                                        overflow: hidden;
                                    ">

                                    <?= htmlspecialchars(
                                        $anuncio[
                                            "DESCRICAO"
                                        ]
                                    ) ?>

                                </p>


                                <!-- PREÇO -->

                                <div
                                    class="mb-2">


                                    <span
                                        class="fw-bold fs-5"
                                        style="
                                            color: #315F6B;
                                        ">

                                        <?= formatarPreco(
                                            $anuncio[
                                                "PRECO"
                                            ]
                                        ) ?>

                                    </span>


                                    <?php if (
                                        !empty(
                                            $anuncio[
                                                "PRECO"
                                            ]
                                        )
                                    ) { ?>

                                        <small
                                            class="text-secondary">

                                            <?= tipoPreco(
                                                $anuncio[
                                                    "TIPO_PRECO"
                                                ]
                                            ) ?>

                                        </small>

                                    <?php } ?>


                                </div>


                                <!-- AVALIAÇÃO -->

                                <div
                                    class="mb-2">


                                    <?php

                                    $media =
                                        (float)
                                        $anuncio[
                                            "MEDIA_AVALIACAO"
                                        ];

                                    ?>


                                    <?php if (
                                        $media > 0
                                    ) { ?>

                                        <span
                                            class="text-warning fw-bold">

                                            ★
                                            <?= number_format(
                                                $media,
                                                1,
                                                ",",
                                                "."
                                            ) ?>

                                        </span>


                                        <small
                                            class="text-secondary">

                                            (
                                            <?= $anuncio[
                                                "TOTAL_AVALIACOES"
                                            ] ?>
                                            avaliações)

                                        </small>

                                    <?php } else { ?>

                                        <small
                                            class="text-secondary">

                                            Ainda sem avaliações

                                        </small>

                                    <?php } ?>


                                </div>


                                <!-- LOCALIZAÇÃO -->

                                <?php if (
                                    !empty(
                                        $anuncio["CIDADE"]
                                    )
                                ) { ?>

                                    <p
                                        class="text-secondary mb-3">

                                        📍

                                        <?= htmlspecialchars(
                                            $anuncio[
                                                "CIDADE"
                                            ]
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $anuncio[
                                                    "ESTADO"
                                                ]
                                            )
                                        ) { ?>

                                            -

                                            <?= htmlspecialchars(
                                                $anuncio[
                                                    "ESTADO"
                                                ]
                                            ) ?>

                                        <?php } ?>

                                    </p>

                                <?php } ?>


                                <!-- BOTÃO -->

                                <a
                                    href="detalhes_anuncio.php?id=<?= $anuncio["ID_ANUNCIO"] ?>"
                                    class="btn mt-auto text-white"
                                    style="
                                        background-color: #3F7C8C;
                                    ">

                                    Ver anúncio

                                </a>


                            </div>

                        </div>

                    </div>


                <?php } ?>


            </div>


        <?php } else { ?>


            <!-- =====================================
                 SEM RESULTADOS
            ====================================== -->

            <div
                class="card border-0 shadow-sm rounded-4">

                <div
                    class="card-body text-center py-5">


                    <div
                        class="display-5 mb-3">

                        🔎

                    </div>


                    <h4
                        class="fw-bold"
                        style="color: #315F6B;">

                        Nenhum profissional encontrado

                    </h4>


                    <p class="text-secondary">

                        Tente alterar os filtros
                        ou realizar uma nova busca.

                    </p>


                    <a
                        href="profissionais.php"
                        class="btn text-white"
                        style="
                            background-color: #3F7C8C;
                        ">

                        Limpar filtros

                    </a>


                </div>

            </div>


        <?php } ?>


    </section>


</main>


<?php

mysqli_close($conexao);

include "rodape.php";

?>
