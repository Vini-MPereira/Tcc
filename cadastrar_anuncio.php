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
// CONEXÃO COM O BANCO
// =====================================================

include "conexao.php";


// =====================================================
// DADOS DO USUÁRIO
// =====================================================

$id_usuario = $_SESSION["ID_USUARIO"];
$nome_usuario = $_SESSION["NOME_USUARIO"];


// =====================================================
// MENSAGENS
// =====================================================

$mensagem = "";
$tipo_mensagem = "";


// =====================================================
// BAIRROS DE BAURU
// =====================================================

$bairros_bauru = [

    "Altos da Cidade",
    "Alto Paraíso",
    "Bela Vista",
    "Centro",
    "Chácaras Cardoso",
    "Chácaras Cornélia",
    "Colina Verde",
    "Distrito Industrial",
    "Estoril",
    "Ferradura Mirim",
    "Higienópolis",

    "Jardim América",
    "Jardim Aeroporto",
    "Jardim Bela Vista",
    "Jardim Brasil",
    "Jardim Carolina",
    "Jardim Colonial",
    "Jardim da Grama",
    "Jardim Dona Sarah",
    "Jardim Eldorado",
    "Jardim Estoril",
    "Jardim Eugênia",
    "Jardim Ferraz",
    "Jardim Gaivota",
    "Jardim Giunta",
    "Jardim Imperial",
    "Jardim Infante Dom Henrique",
    "Jardim Jussara",
    "Jardim Marabá",
    "Jardim Marambá",
    "Jardim Marilu",
    "Jardim Mary",
    "Jardim Nicéia",
    "Jardim Olímpico",
    "Jardim Ouro Verde",
    "Jardim Pagani",
    "Jardim Panorama",
    "Jardim Planalto",
    "Jardim Redentor",
    "Jardim Samambaia",
    "Jardim Samburá",
    "Jardim Santos Dumont",
    "Jardim Shangri-lá",
    "Jardim Solange",
    "Jardim Terra Branca",
    "Jardim Vânia Maria",
    "Jardim Vitória",
    "Jardim Vista Alegre",

    "Leão XIII",
    "Mary Dota",
    "Nova Esperança",
    "Núcleo Geisel",
    "Núcleo Habitacional Joaquim Guilherme",
    "Núcleo José Regino",
    "Núcleo Luís Edmundo Coube",
    "Núcleo Octávio Rasi",
    "Núcleo Pastor Arlindo Lopes Viana",

    "Parque Bauru",
    "Parque das Nações",
    "Parque das Orquídeas",
    "Parque dos Flamboyants",
    "Parque Santa Cândida",
    "Parque Santa Edwirges",
    "Parque Santa Terezinha",
    "Parque Viaduto",

    "Residencial Andorinhas",
    "Residencial Granja Cecília",
    "Residencial Lago Sul",
    "Residencial Nova Bauru",
    "Residencial Odete",
    "Residencial Sabiás",
    "Residencial Tavano",
    "Residencial Villa Dumont",

    "Santa Cândida",
    "Santa Edwirges",
    "Vale do Igapó",

    "Vila Aeroporto",
    "Vila Antártica",
    "Vila Aviação",
    "Vila Bela",
    "Vila Cardia",
    "Vila Cidade Universitária",
    "Vila Coralina",
    "Vila Dutra",
    "Vila Falcão",
    "Vila Giunta",
    "Vila Industrial",
    "Vila Independência",
    "Vila Ipiranga",
    "Vila Jardim Guadalajara",
    "Vila Mesquita",
    "Vila Nipônica",
    "Vila Nova Cidade Universitária",
    "Vila Pacífico",
    "Vila Paraíso",
    "Vila Perroca",
    "Vila Santa Clara",
    "Vila Santa Tereza",
    "Vila Santista",
    "Vila São Francisco",
    "Vila Souto",
    "Vila Universitária",
    "Vila Zilo"

];


// Ordena alfabeticamente
sort($bairros_bauru);


// =====================================================
// BAIRROS SELECIONADOS
// =====================================================

$bairros_selecionados = [];


// =====================================================
// ENVIO DO FORMULÁRIO
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    // =================================================
    // RECEBE OS DADOS
    // =================================================

    $descricao = trim(
        $_POST["descricao"] ?? ""
    );


    $preco = trim(
        $_POST["preco"] ?? ""
    );


    $cep = trim(
        $_POST["cep"] ?? ""
    );


    $bairros_post = $_POST["bairros"] ?? [];


    // =================================================
    // GARANTE QUE BAIRROS SEJAM UM ARRAY
    // =================================================

    if (!is_array($bairros_post)) {

        $bairros_post = [];

    }


    // =================================================
    // VALIDA OS BAIRROS
    // =================================================

    foreach ($bairros_post as $bairro_item) {

        $bairro_item = trim($bairro_item);


        if (
            !empty($bairro_item)
            &&
            in_array(
                $bairro_item,
                $bairros_bauru
            )
        ) {

            $bairros_selecionados[] =
                $bairro_item;

        }

    }


    // Remove bairros repetidos

    $bairros_selecionados =
        array_unique(
            $bairros_selecionados
        );


    // =================================================
    // VALIDAÇÃO
    // =================================================

    if (empty($descricao)) {

        $mensagem =
            "Conte um pouco sobre sua faxina.";

        $tipo_mensagem =
            "danger";

    }

    elseif (empty($preco)) {

        $mensagem =
            "Informe um valor aproximado para sua faxina.";

        $tipo_mensagem =
            "danger";

    }

    elseif (empty($bairros_selecionados)) {

        $mensagem =
            "Selecione pelo menos um bairro onde você atende.";

        $tipo_mensagem =
            "danger";

    }

    else {


        // =================================================
        // BUSCA A CATEGORIA FAXINA
        // =================================================

        $sql_categoria = "
            SELECT ID_CATEGORIA
            FROM CATEGORIA
            WHERE NOME = 'Faxina'
            LIMIT 1
        ";


        $resultado_categoria =
            mysqli_query(
                $conexao,
                $sql_categoria
            );


        // =================================================
        // SE DEU ERRO NA CONSULTA
        // =================================================

        if (!$resultado_categoria) {

            $mensagem =
                "Não foi possível publicar seu serviço. Tente novamente.";

            $tipo_mensagem =
                "danger";

        }

        else {


            $categoria =
                mysqli_fetch_assoc(
                    $resultado_categoria
                );


            // =================================================
            // SE A CATEGORIA NÃO EXISTIR, CRIA AUTOMATICAMENTE
            // =================================================

            if (!$categoria) {


                $sql_criar_categoria = "
                    INSERT INTO CATEGORIA
                    (
                        NOME,
                        DESCRICAO,
                        ATIVO
                    )
                    VALUES
                    (
                        'Faxina',
                        'Serviços de limpeza residencial.',
                        TRUE
                    )
                ";


                $categoria_criada =
                    mysqli_query(
                        $conexao,
                        $sql_criar_categoria
                    );


                if ($categoria_criada) {

                    $id_categoria =
                        mysqli_insert_id(
                            $conexao
                        );

                }

                else {

                    $id_categoria =
                        0;

                }

            }

            else {

                $id_categoria =
                    $categoria["ID_CATEGORIA"];

            }


            // =================================================
            // VERIFICA SE CONSEGUIU A CATEGORIA
            // =================================================

            if ($id_categoria <= 0) {

                $mensagem =
                    "Não foi possível publicar seu serviço. Tente novamente.";

                $tipo_mensagem =
                    "danger";

            }

            else {


                // =================================================
                // TÍTULO AUTOMÁTICO
                // =================================================

                $titulo =
                    "Faxina - " . $nome_usuario;


                // =================================================
                // FORMATA O VALOR
                // =================================================

                $preco =
                    str_replace(
                        ".",
                        "",
                        $preco
                    );


                $preco =
                    str_replace(
                        ",",
                        ".",
                        $preco
                    );


                // =================================================
                // JUNTA OS BAIRROS
                // =================================================

                $bairro =
                    implode(
                        ", ",
                        $bairros_selecionados
                    );


                // =================================================
                // CADASTRA O ANÚNCIO
                // =================================================

                $sql = "
                    INSERT INTO ANUNCIO
                    (
                        ID_USUARIO,
                        ID_CATEGORIA,
                        TITULO,
                        DESCRICAO,
                        PRECO,
                        CEP,
                        ESTADO,
                        CIDADE,
                        BAIRRO,
                        STATUS
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'SP',
                        'Bauru',
                        ?,
                        'ATIVO'
                    )
                ";


                $stmt =
                    mysqli_prepare(
                        $conexao,
                        $sql
                    );


                // =================================================
                // VERIFICA PREPARAÇÃO
                // =================================================

                if ($stmt) {


                    mysqli_stmt_bind_param(
                        $stmt,
                        "iissdss",
                        $id_usuario,
                        $id_categoria,
                        $titulo,
                        $descricao,
                        $preco,
                        $cep,
                        $bairro
                    );


                    // =============================================
                    // EXECUTA
                    // =============================================

                    if (
                        mysqli_stmt_execute(
                            $stmt
                        )
                    ) {


                        $mensagem =
                            "Seu serviço foi publicado com sucesso!";


                        $tipo_mensagem =
                            "success";


                        // Limpa formulário

                        $descricao =
                            "";

                        $preco =
                            "";

                        $cep =
                            "";

                        $bairros_selecionados =
                            [];

                    }

                    else {


                        $mensagem =
                            "Não foi possível publicar seu serviço.";

                        $tipo_mensagem =
                            "danger";

                    }


                    mysqli_stmt_close(
                        $stmt
                    );

                }

                else {


                    $mensagem =
                        "Não foi possível preparar seu serviço.";

                    $tipo_mensagem =
                        "danger";

                }

            }

        }

    }

}

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

            🧹 Oferecer meu serviço

        </h1>


        <p
            style="color: #617477;">

            Cadastre sua faxina e informe onde você atende
            em Bauru.

        </p>

    </div>



    <!-- =================================================
         MENSAGEM
    ================================================== -->

    <?php if (!empty($mensagem)) { ?>

        <div
            class="alert alert-<?php echo $tipo_mensagem; ?> text-center">

            <?php
            echo htmlspecialchars(
                $mensagem
            );
            ?>

        </div>

    <?php } ?>



    <!-- =================================================
         FORMULÁRIO
    ================================================== -->

    <div class="row justify-content-center">

        <div class="col-12 col-lg-8">

            <div
                class="card border-0 shadow-sm rounded-4">

                <div class="card-body p-4 p-md-5">


                    <!-- =================================================
                         NOME DO PROFISSIONAL
                    ================================================== -->

                    <div
                        class="text-center mb-5">

                        <div
                            class="fw-bold fs-4"
                            style="color: #126E68;">

                            <?php
                            echo htmlspecialchars(
                                $nome_usuario
                            );
                            ?>

                        </div>


                        <div
                            class="small mt-1"
                            style="color: #617477;">

                            Serviço de faxina residencial

                        </div>

                    </div>



                    <form
                        method="POST"
                        id="formAnuncio">


                        <!-- =================================================
                             DESCRIÇÃO
                        ================================================== -->

                        <div class="mb-5">

                            <label
                                for="descricao"
                                class="form-label fw-bold fs-5"
                                style="color: #126E68;">

                                Fale sobre sua faxina

                            </label>


                            <textarea
                                class="form-control"
                                id="descricao"
                                name="descricao"
                                rows="6"
                                maxlength="1000"
                                placeholder="Conte como é o seu serviço, quais ambientes você costuma limpar e o que normalmente está incluso na faxina..."
                                required><?php

                                echo htmlspecialchars(
                                    $descricao ?? ""
                                );

                                ?></textarea>


                            <div
                                class="form-text mt-2">

                                Conte para o cliente como é o seu trabalho.

                            </div>

                        </div>



                        <!-- =================================================
                             VALOR
                        ================================================== -->

                        <div class="mb-5">

                            <label
                                for="preco"
                                class="form-label fw-bold fs-5"
                                style="color: #126E68;">

                                Valor aproximado

                            </label>


                            <div class="input-group">

                                <span
                                    class="input-group-text">

                                    R$

                                </span>


                                <input
                                    type="text"
                                    class="form-control"
                                    id="preco"
                                    name="preco"
                                    placeholder="150,00"
                                    value="<?php

                                    echo htmlspecialchars(
                                        $preco ?? ""
                                    );

                                    ?>"
                                    required>

                            </div>


                            <div
                                class="form-text mt-2">

                                O valor é apenas uma estimativa.
                                O preço final será combinado diretamente
                                com o cliente de acordo com o tamanho
                                da casa e o serviço necessário.

                            </div>

                        </div>



                        <!-- =================================================
                             ÁREA DE ATENDIMENTO
                        ================================================== -->

                        <div class="mb-5">

                            <h3
                                class="fw-bold mb-3"
                                style="color: #126E68;">

                                📍 Onde você atende?

                            </h3>


                            <p
                                class="mb-4"
                                style="color: #617477;">

                                Escolha os bairros de Bauru onde você
                                realiza suas faxinas.

                            </p>



                            <!-- =================================================
                                 CEP
                            ================================================== -->

                            <div class="mb-4">

                                <label
                                    for="cep"
                                    class="form-label">

                                    Encontrar bairro pelo CEP

                                </label>


                                <div class="input-group">

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="cep"
                                        name="cep"
                                        maxlength="9"
                                        placeholder="17000-000">


                                    <button
                                        type="button"
                                        class="btn text-white"
                                        id="buscarCep"
                                        style="
                                            background-color: #18A999;
                                        ">

                                        Buscar

                                    </button>

                                </div>


                                <div
                                    id="resultadoCep"
                                    class="form-text mt-2">

                                    Digite um CEP de Bauru para encontrar
                                    o bairro automaticamente.

                                </div>

                            </div>



                            <!-- =================================================
                                 BAIRRO ENCONTRADO
                            ================================================== -->

                            <div
                                id="bairroCepContainer"
                                class="mb-4"
                                style="display: none;">

                                <div
                                    class="p-3 rounded-3"
                                    style="
                                        background-color: #E7F5F1;
                                    ">

                                    <div
                                        class="small mb-1"
                                        style="color: #617477;">

                                        Bairro encontrado

                                    </div>


                                    <div
                                        id="bairroEncontrado"
                                        class="fw-bold"
                                        style="color: #126E68;">

                                    </div>


                                    <button
                                        type="button"
                                        class="btn btn-sm mt-2"
                                        id="adicionarBairroCep"
                                        style="
                                            background-color: #18A999;
                                            color: white;
                                        ">

                                        + Adicionar bairro

                                    </button>

                                </div>

                            </div>



                            <!-- =================================================
                                 PESQUISA
                            ================================================== -->

                            <div class="mb-3">

                                <label
                                    for="pesquisaBairro"
                                    class="form-label">

                                    Ou escolha o bairro

                                </label>


                                <input
                                    type="text"
                                    class="form-control"
                                    id="pesquisaBairro"
                                    placeholder="Digite o nome do bairro...">

                            </div>



                            <!-- =================================================
                                 LISTA DE BAIRROS
                            ================================================== -->

                            <div
                                id="listaBairros"
                                class="border rounded-3"
                                style="
                                    max-height: 280px;
                                    overflow-y: auto;
                                ">


                                <?php

                                foreach (
                                    $bairros_bauru
                                    as $bairro_item
                                ) {

                                ?>

                                    <label
                                        class="bairro-item d-flex align-items-center gap-2 p-3 border-bottom"
                                        style="cursor: pointer;">

                                        <input
                                            type="checkbox"
                                            class="form-check-input bairro-checkbox"
                                            name="bairros[]"
                                            value="<?php

                                            echo htmlspecialchars(
                                                $bairro_item
                                            );

                                            ?>"
                                            <?php

                                            if (
                                                in_array(
                                                    $bairro_item,
                                                    $bairros_selecionados
                                                )
                                            ) {

                                                echo "checked";

                                            }

                                            ?>
                                        >


                                        <span>

                                            <?php

                                            echo htmlspecialchars(
                                                $bairro_item
                                            );

                                            ?>

                                        </span>

                                    </label>

                                <?php

                                }

                                ?>


                            </div>



                            <!-- =================================================
                                 SELECIONADOS
                            ================================================== -->

                            <div class="mt-4">

                                <div
                                    class="fw-bold mb-2"
                                    style="color: #126E68;">

                                    Bairros selecionados

                                </div>


                                <div
                                    id="bairrosSelecionados"
                                    class="d-flex flex-wrap gap-2">

                                </div>


                                <div
                                    id="nenhumBairro"
                                    class="small text-secondary">

                                    Nenhum bairro selecionado.

                                </div>

                            </div>

                        </div>



                        <!-- =================================================
                             BAURU
                        ================================================== -->

                        <div
                            class="p-3 rounded-3 mb-5"
                            style="
                                background-color: #E7F5F1;
                                color: #126E68;
                            ">

                            <strong>

                                📍 Bauru - SP

                            </strong>


                            <div
                                class="small mt-1">

                                O LimpaLar atende atualmente somente
                                a cidade de Bauru.

                            </div>

                        </div>



                        <!-- =================================================
                             BOTÕES
                        ================================================== -->

                        <div
                            class="d-flex flex-column flex-md-row justify-content-center gap-3">


                            <a
                                href="painel.php"
                                class="btn btn-outline-secondary btn-lg px-4">

                                Voltar

                            </a>


                            <button
                                type="submit"
                                class="btn btn-lg text-white px-5"
                                style="
                                    background-color: #18A999;
                                    border-color: #18A999;
                                ">

                                🧹 Publicar meu serviço

                            </button>


                        </div>


                    </form>

                </div>

            </div>

        </div>

    </div>

</section>

</main>



<!-- =====================================================
     JAVASCRIPT
====================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        // =================================================
        // ELEMENTOS
        // =================================================

        const pesquisaBairro =
            document.getElementById(
                "pesquisaBairro"
            );


        const listaBairros =
            document.querySelectorAll(
                ".bairro-item"
            );


        const checkboxes =
            document.querySelectorAll(
                ".bairro-checkbox"
            );


        const bairrosSelecionados =
            document.getElementById(
                "bairrosSelecionados"
            );


        const nenhumBairro =
            document.getElementById(
                "nenhumBairro"
            );


        const cepInput =
            document.getElementById(
                "cep"
            );


        const buscarCep =
            document.getElementById(
                "buscarCep"
            );


        const resultadoCep =
            document.getElementById(
                "resultadoCep"
            );


        const bairroCepContainer =
            document.getElementById(
                "bairroCepContainer"
            );


        const bairroEncontrado =
            document.getElementById(
                "bairroEncontrado"
            );


        const adicionarBairroCep =
            document.getElementById(
                "adicionarBairroCep"
            );



        // =================================================
        // PESQUISA DE BAIRRO
        // =================================================

        pesquisaBairro.addEventListener(
            "input",
            function () {


                const pesquisa =
                    this.value
                    .toLowerCase()
                    .trim();


                listaBairros.forEach(
                    function (item) {


                        const nome =
                            item.innerText
                            .toLowerCase();


                        if (
                            nome.includes(
                                pesquisa
                            )
                        ) {

                            item.style.display =
                                "flex";

                        }

                        else {

                            item.style.display =
                                "none";

                        }

                    }
                );

            }
        );



        // =================================================
        // ATUALIZA BAIRROS SELECIONADOS
        // =================================================

        function atualizarBairrosSelecionados() {


            bairrosSelecionados.innerHTML =
                "";


            let quantidade = 0;


            checkboxes.forEach(
                function (checkbox) {


                    if (checkbox.checked) {


                        quantidade++;


                        const etiqueta =
                            document.createElement(
                                "span"
                            );


                        etiqueta.className =
                            "badge rounded-pill px-3 py-2";


                        etiqueta.style.backgroundColor =
                            "#E7F5F1";


                        etiqueta.style.color =
                            "#126E68";


                        etiqueta.innerHTML =
                            checkbox.value
                            + " &times;";


                        etiqueta.style.cursor =
                            "pointer";


                        etiqueta.title =
                            "Clique para remover";


                        etiqueta.addEventListener(
                            "click",
                            function () {


                                checkbox.checked =
                                    false;


                                atualizarBairrosSelecionados();

                            }
                        );


                        bairrosSelecionados.appendChild(
                            etiqueta
                        );

                    }

                }
            );


            if (quantidade === 0) {

                nenhumBairro.style.display =
                    "block";

            }

            else {

                nenhumBairro.style.display =
                    "none";

            }

        }



        // =================================================
        // CHECKBOX
        // =================================================

        checkboxes.forEach(
            function (checkbox) {


                checkbox.addEventListener(
                    "change",
                    atualizarBairrosSelecionados
                );

            }
        );



        // =================================================
        // FORMATA CEP
        // =================================================

        cepInput.addEventListener(
            "input",
            function () {


                let valor =
                    this.value.replace(
                        /\D/g,
                        ""
                    );


                if (valor.length > 5) {


                    valor =
                        valor.substring(
                            0,
                            5
                        )
                        + "-"
                        +
                        valor.substring(
                            5,
                            8
                        );

                }


                this.value =
                    valor;

            }
        );



        // =================================================
        // BUSCAR CEP
        // =================================================

        buscarCep.addEventListener(
            "click",
            async function () {


                const cep =
                    cepInput.value.replace(
                        /\D/g,
                        ""
                    );


                if (cep.length !== 8) {


                    resultadoCep.innerText =
                        "Digite um CEP válido.";

                    return;

                }


                resultadoCep.innerText =
                    "Buscando bairro...";


                try {


                    const resposta =
                        await fetch(
                            "https://viacep.com.br/ws/"
                            + cep
                            + "/json/"
                        );


                    const dados =
                        await resposta.json();


                    // =================================
                    // CEP NÃO EXISTE
                    // =================================

                    if (dados.erro) {


                        resultadoCep.innerText =
                            "CEP não encontrado.";

                        bairroCepContainer.style.display =
                            "none";

                        return;

                    }



                    // =================================
                    // CONFERE BAURU
                    // =================================

                    const cidade =
                        (
                            dados.localidade
                            || ""
                        )
                        .toLowerCase();


                    const uf =
                        (
                            dados.uf
                            || ""
                        )
                        .toUpperCase();


                    if (
                        cidade !== "bauru"
                        ||
                        uf !== "SP"
                    ) {


                        resultadoCep.innerText =
                            "Esse CEP não pertence a Bauru - SP.";

                        bairroCepContainer.style.display =
                            "none";

                        return;

                    }



                    // =================================
                    // SEM BAIRRO
                    // =================================

                    if (!dados.bairro) {


                        resultadoCep.innerText =
                            "Esse CEP não retornou um bairro.";

                        bairroCepContainer.style.display =
                            "none";

                        return;

                    }



                    // =================================
                    // MOSTRA BAIRRO
                    // =================================

                    bairroEncontrado.innerText =
                        dados.bairro;


                    bairroCepContainer.style.display =
                        "block";


                    resultadoCep.innerText =
                        "Bairro encontrado.";

                }


                catch (erro) {


                    resultadoCep.innerText =
                        "Não foi possível consultar o CEP.";

                }

            }
        );



        // =================================================
        // ADICIONAR BAIRRO ENCONTRADO
        // =================================================

        adicionarBairroCep.addEventListener(
            "click",
            function () {


                const nomeBairro =
                    bairroEncontrado.innerText
                    .trim();


                let encontrou =
                    false;


                checkboxes.forEach(
                    function (checkbox) {


                        if (
                            checkbox.value
                            .toLowerCase()
                            ===
                            nomeBairro
                            .toLowerCase()
                        ) {


                            checkbox.checked =
                                true;


                            encontrou =
                                true;

                        }

                    }
                );


                if (encontrou) {


                    atualizarBairrosSelecionados();


                    resultadoCep.innerText =
                        "Bairro adicionado à sua área de atendimento.";

                }

                else {


                    resultadoCep.innerText =
                        "Esse bairro não está disponível na lista.";

                }

            }
        );



        // =================================================
        // INICIA
        // =================================================

        atualizarBairrosSelecionados();

    }
);

</script>



<?php include "rodape.php"; ?>