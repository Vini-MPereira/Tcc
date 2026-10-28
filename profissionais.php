<?php

session_start();

include "conexao.php";

include "bairros_bauru.php";

if (isset($_GET["acao"]) && $_GET["acao"] === "buscar_bairro") {

    header("Content-Type: application/json; charset=UTF-8");

    $termo = trim($_GET["termo"] ?? "");

    echo json_encode(
        procurar_bairros($termo),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// CONSULTAR A DISPONIBILIDADE DO PROFISSIONAL
if (
    isset($_GET["acao"]) &&
    $_GET["acao"] === "disponibilidade"
) {
    header("Content-Type: application/json; charset=UTF-8");

    $idUsuario = filter_input(
        INPUT_GET,
        "id_usuario",
        FILTER_VALIDATE_INT
    );

    if (!$idUsuario || $idUsuario < 1) {
        http_response_code(400);

        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Profissional inválido.",
            "horarios" => []
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    $sqlAgenda = "
        SELECT DIA_SEMANA, HORA_INICIO, HORA_FIM
        FROM DISPONIBILIDADE
        WHERE ID_USUARIO = ?
          AND ATIVO = 1
        ORDER BY HORA_INICIO
    ";

    $stmtAgenda = mysqli_prepare($conexao, $sqlAgenda);

    if (!$stmtAgenda) {
        http_response_code(500);

        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Erro ao consultar a agenda.",
            "horarios" => []
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    mysqli_stmt_bind_param($stmtAgenda, "i", $idUsuario);
    mysqli_stmt_execute($stmtAgenda);

    $resultadoAgenda = mysqli_stmt_get_result($stmtAgenda);
    $horarios = [];

    while ($linha = mysqli_fetch_assoc($resultadoAgenda)) {
        $horarios[] = $linha;
    }

    mysqli_stmt_close($stmtAgenda);

    echo json_encode([
        "sucesso" => true,
        "horarios" => $horarios
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

function escapar($texto)

{

    return htmlspecialchars($texto ?? "", ENT_QUOTES, "UTF-8");

}

$busca_bairro = trim($_GET["bairro"] ?? "");

$bairro_oficial = resolver_bairro($_GET["bairro_oficial"] ?? "");

$cep_pesquisa = preg_replace("/[^0-9]/", "", $_GET["cep"] ?? "");

$sql = "

    SELECT

        A.ID_ANUNCIO,

        A.ID_USUARIO,

        A.TITULO,

        A.DESCRICAO,

        A.PRECO,

        A.TIPO_PRECO,

        A.FOTO,

        A.CEP,

        A.CIDADE,

        A.BAIRRO,

        A.STATUS,

        A.DATA_CADASTRO,

        U.NOME,

        U.FOTO AS FOTO_USUARIO,

        U.TELEFONE

    FROM ANUNCIO A

    INNER JOIN USUARIO U ON U.ID_USUARIO = A.ID_USUARIO

    WHERE A.STATUS = 'ATIVO'

      AND U.ATIVO = 1

    ORDER BY A.DATA_CADASTRO DESC

";

$resultado = mysqli_query($conexao, $sql);

$anuncios = [];

if ($resultado) {

    while ($linha = mysqli_fetch_assoc($resultado)) {

        $anuncios[] = $linha;

    }

}

$exatos = [];

$outros = [];

if ($bairro_oficial !== "") {

    $alvo = normalizar_bairro($bairro_oficial);

    foreach ($anuncios as $anuncio) {

        $encontrou = false;

        foreach (explode(",", $anuncio["BAIRRO"] ?? "") as $bairro) {

            $canonico = resolver_bairro(trim($bairro));

            if ($canonico !== "") {

                $valor_comparar = $canonico;

            } else {

                $valor_comparar = trim($bairro);

            }

            if (normalizar_bairro($valor_comparar) === $alvo) {

                $encontrou = true;

                break;

            }

        }

        if ($encontrou) {

            $exatos[] = $anuncio;

        } else {

            $outros[] = $anuncio;

        }

    }

} else {

    $outros = $anuncios;

}

function mostrar_card($anuncio)

{

    $foto = trim($anuncio["FOTO"] ?? "");

    $descricao = $anuncio["DESCRICAO"] ?? "";

    $bairros = array_filter(array_map("trim", explode(",", $anuncio["BAIRRO"] ?? "")));

    ?>

    <div class="col-md-6 col-lg-4">

        <div class="card-profissional">

            <?php if ($foto !== "" && file_exists($foto)): ?>

                <img src="<?= escapar($foto); ?>" class="foto-profissional" alt="Foto do profissional">

            <?php else: ?>

                <div class="sem-foto">🧹</div>

            <?php endif; ?>

            <div class="p-4">

                <h5 class="nome-profissional mb-2">

                    <?= escapar($anuncio["NOME"]); ?>

                </h5>

                <span class="badge text-bg-light">🧹 Faxina</span>

                <p class="descricao-profissional mt-3">

                    <?= nl2br(escapar($descricao)); ?>

                </p>

                <div class="mb-3">

                    <?php foreach ($bairros as $bairro): ?>

                        <span class="bairro-tag">

                            📍 <?= escapar(nome_exibicao_bairro($bairro)); ?>

                        </span>

                    <?php endforeach; ?>

                </div>

                <?php if ($anuncio["PRECO"] !== null && $anuncio["PRECO"] !== ""): ?>

                    <div class="preco mb-3">

                        A partir de R$ <?= number_format((float)$anuncio["PRECO"], 2, ",", "."); ?>

                    </div>

                <?php endif; ?>

                

<button
    type="button"
    class="btn btn-primary w-100"
    data-bs-toggle="modal"
    data-bs-target="#modalSolicitar"
    data-id-usuario="<?= (int) $anuncio['ID_USUARIO']; ?>"
    data-nome="<?= escapar($anuncio['NOME']); ?>"
    data-descricao="<?= escapar($anuncio['DESCRICAO'] ?? ''); ?>"
    data-bairro="<?= escapar($anuncio['BAIRRO'] ?? ''); ?>"
    data-preco="<?= $anuncio['PRECO'] !== null && $anuncio['PRECO'] !== ''
        ? 'R$ ' . number_format((float) $anuncio['PRECO'], 2, ',', '.')
        : 'A combinar'; ?>"
    data-foto="<?= escapar(
        !empty($anuncio['FOTO_USUARIO'])
        ? $anuncio['FOTO_USUARIO']
        : ($anuncio['FOTO'] ?? '')
    ); ?>"
                >
                    👤 Ver profissional
</button>
                    

                </a>

            </div>

        </div>

    </div>

    <?php

}



?>

<?php include "cabecalho.php"; ?>

<style>

.titulo-pagina{color:#126E68;font-weight:700}

.subtitulo-pagina{color:#617477}

.filtro-box{background:#E7F5F1;border-radius:18px;padding:25px;border:1px solid #D5E5E1;margin-bottom:30px}

.campo-label{font-weight:600;color:#126E68}

.campo-bairro{position:relative}

.campo-bairro input{border-radius:10px;padding:13px}

.sugestoes-bairro{position:absolute;left:0;right:0;top:100%;background:#fff;border:1px solid #D5E5E1;border-radius:10px;margin-top:5px;z-index:1000;max-height:300px;overflow-y:auto;display:none;box-shadow:0 5px 20px rgba(0,0,0,.12)}

.item-sugestao{width:100%;background:#fff;border:0;text-align:left;padding:12px 15px;cursor:pointer}

.item-sugestao:hover{background:#E7F5F1}

.nome-curto{color:#126E68;font-weight:700}

.nome-oficial{color:#617477;font-size:13px}

.aviso-resultados{background:#fff;border-left:5px solid #18A999;padding:15px 18px;border-radius:10px;margin-bottom:25px}

.aviso-sem-resultado{background:#fff8e8;border-left:5px solid #F2B84B;padding:18px;border-radius:10px;margin-bottom:25px}

.card-profissional{border:0;border-radius:18px;overflow:hidden;background:#fff;box-shadow:0 5px 20px rgba(0,0,0,.07);height:100%}

.foto-profissional,.sem-foto{width:100%;height:190px}

.foto-profissional{object-fit:cover}

.sem-foto{background:#E7F5F1;display:flex;justify-content:center;align-items:center;font-size:60px}

.nome-profissional{color:#126E68;font-weight:700}

.descricao-profissional{color:#617477;min-height:55px}

.bairro-tag{display:inline-block;background:#E7F5F1;color:#126E68;border-radius:20px;padding:5px 10px;margin:3px;font-size:13px}

.preco{color:#126E68;font-weight:700;font-size:18px}

</style>

<div class="container py-5">

    <div class="text-center mb-4">

        <h1 class="titulo-pagina">🧹 Encontre um profissional</h1>

        <p class="subtitulo-pagina">

            Digite o bairro do jeito que você costuma falar.

        </p>

    </div>

    <div class="filtro-box">

        <h4 class="mb-4">📍 Onde você precisa da faxina?</h4>

        <form method="GET" action="profissionais.php" id="formPesquisa">

            <div class="row g-3">

                <div class="col-lg-8">

                    <label class="campo-label mb-2">🏘️ Digite seu bairro</label>

                    <div class="campo-bairro">

                        <input

                            type="text"

                            class="form-control"

                            id="campoBairro"

                            name="bairro"

                            value="<?= escapar($busca_bairro); ?>"

                            placeholder="Ex.: Geisel, Mary Dota, Pagani..."

                            autocomplete="off"

                        >

                        <input

                            type="hidden"

                            name="bairro_oficial"

                            id="bairroOficial"

                            value="<?= escapar($bairro_oficial); ?>"

                        >

                        <div class="sugestoes-bairro" id="sugestoesBairro"></div>

                    </div>

                    <small class="text-muted">

                        Você pode escrever o nome popular. O LimpaLar usa o nome oficial para procurar.

                    </small>

                </div>

                <div class="col-lg-4">

                    <label class="campo-label mb-2">📮 Ou procure pelo CEP</label>

                    <div class="input-group">

                        <input

                            type="text"

                            id="cepPesquisa"

                            class="form-control"

                            maxlength="9"

                            placeholder="00000-000"

                            value="<?= escapar($cep_pesquisa); ?>"

                        >

                        <button type="button" class="btn btn-primary" id="btnCep">Buscar</button>

                    </div>

                    <small id="statusCep" class="text-muted">

                        O CEP encontra o bairro automaticamente.

                    </small>

                </div>

            </div>

            <div class="mt-4">

                <button type="submit" class="btn btn-primary">

                    🔎 Procurar profissionais

                </button>

                <a href="profissionais.php" class="btn btn-outline-secondary">

                    Limpar

                </a>

            </div>

        </form>

    </div>

    <?php if ($bairro_oficial !== ""): ?>

        <?php if (count($exatos) > 0): ?>

            <div class="aviso-resultados">

                <strong>

                    📍 Profissionais que atendem <?= escapar($busca_bairro); ?>

                </strong>

                <div class="mt-1">

                    Encontramos <?= count($exatos); ?> profissional(is).

                </div>

            </div>

            <div class="row g-4">

                <?php foreach ($exatos as $anuncio) { mostrar_card($anuncio); } ?>

            </div>

        <?php else: ?>

            <div class="aviso-sem-resultado">

                <h5>😕 Nenhum profissional encontrado nesse bairro.</h5>

                <p class="mb-0">

                    Não encontramos alguém atendendo exatamente

                    <strong><?= escapar($busca_bairro); ?></strong>.

                    <br>

                    Abaixo estão outros profissionais cadastrados no LimpaLar.

                </p>

            </div>

            <?php if (count($outros) > 0): ?>

                <div class="row g-4">

                    <?php foreach ($outros as $anuncio) { mostrar_card($anuncio); } ?>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    <?php else: ?>

        <h3 class="titulo-pagina mb-4">👇 Profissionais disponíveis</h3>

        <?php if (count($anuncios) > 0): ?>

            <div class="row g-4">

                <?php foreach ($anuncios as $anuncio) { mostrar_card($anuncio); } ?>

            </div>

        <?php else: ?>

            <div class="alert alert-light border">

                Ainda não existem serviços de faxina publicados.

            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

<script>

const campoBairro = document.getElementById("campoBairro");

const bairroOficial = document.getElementById("bairroOficial");

const sugestoes = document.getElementById("sugestoesBairro");

const formPesquisa = document.getElementById("formPesquisa");

let timerBairro = null;

function carregarSugestoes() {

    const texto = campoBairro.value.trim();

    bairroOficial.value = "";

    clearTimeout(timerBairro);

    if (texto.length < 2) {

        sugestoes.innerHTML = "";

        sugestoes.style.display = "none";

        return;

    }

    timerBairro = setTimeout(async () => {

        try {

            const resposta = await fetch(

                "profissionais.php?acao=buscar_bairro&termo=" +

                encodeURIComponent(texto)

            );

            const dados = await resposta.json();

            sugestoes.innerHTML = "";

            if (!dados.length) {

                sugestoes.innerHTML =

                    '<div class="p-3 text-muted">Nenhum bairro encontrado.</div>';

                sugestoes.style.display = "block";

                return;

            }

            dados.forEach((bairro) => {

                const botao = document.createElement("button");

                botao.type = "button";

                botao.className = "item-sugestao";

                botao.innerHTML =

                    '<div class="nome-curto">📍 ' +

                    escapeHtml(bairro.nome) +

                    '</div>' +

                    '<div class="nome-oficial">' +

                    escapeHtml(bairro.oficial) +

                    '</div>';

                botao.addEventListener("click", () => {

                    campoBairro.value = bairro.nome;

                    bairroOficial.value = bairro.oficial;

                    sugestoes.innerHTML = "";

                    sugestoes.style.display = "none";

                });

                sugestoes.appendChild(botao);

            });

            sugestoes.style.display = "block";

        } catch (erro) {

            sugestoes.style.display = "none";

        }

    }, 180);

}

function escapeHtml(texto) {

    const div = document.createElement("div");

    div.textContent = texto;

    return div.innerHTML;

}

campoBairro.addEventListener("input", carregarSugestoes);

document.addEventListener("click", (evento) => {

    if (!evento.target.closest(".campo-bairro")) {

        sugestoes.style.display = "none";

    }

});

formPesquisa.addEventListener("submit", (evento) => {

    if (campoBairro.value.trim() !== "" && bairroOficial.value.trim() === "") {

        evento.preventDefault();

        alert("Escolha um bairro na sugestão para confirmar o nome oficial.");

        campoBairro.focus();

    }

});

const cepPesquisa = document.getElementById("cepPesquisa");

const btnCep = document.getElementById("btnCep");

const statusCep = document.getElementById("statusCep");

cepPesquisa.addEventListener("input", () => {

    let valor = cepPesquisa.value.replace(/\D/g, "").slice(0, 8);

    if (valor.length > 5) {

        valor = valor.substring(0, 5) + "-" + valor.substring(5);

    }

    cepPesquisa.value = valor;

});

btnCep.addEventListener("click", async () => {

    const cep = cepPesquisa.value.replace(/\D/g, "");

    if (cep.length !== 8) {

        statusCep.textContent = "Digite um CEP válido com 8 números.";

        return;

    }

    statusCep.textContent = "Consultando CEP...";

    try {

        const resposta = await fetch(

            "https://viacep.com.br/ws/" + cep + "/json/"

        );

        const dados = await resposta.json();

        if (dados.erro) {

            statusCep.textContent = "CEP não encontrado.";

            return;

        }

        if (

            (dados.localidade || "").toLowerCase() !== "bauru" ||

            (dados.uf || "").toUpperCase() !== "SP"

        ) {

            statusCep.textContent = "Esse CEP não é de Bauru/SP.";

            return;

        }

        campoBairro.value = dados.bairro || "";

        bairroOficial.value = "";

        if (dados.bairro) {

            const respostaBairro = await fetch(

                "profissionais.php?acao=buscar_bairro&termo=" +

                encodeURIComponent(dados.bairro)

            );

            const bairros = await respostaBairro.json();

            if (bairros.length > 0) {

                bairroOficial.value = bairros[0].oficial;

                campoBairro.value = bairros[0].nome;

                statusCep.textContent =

                    "Bairro encontrado: " + bairros[0].oficial;

            } else {

                statusCep.textContent =

                    "O CEP encontrou '" + dados.bairro +

                    "', mas esse bairro não foi localizado na lista.";

            }

        }

    } catch (erro) {

        statusCep.textContent = "Não foi possível consultar o CEP agora.";

    }

});

</script>


<!-- MODAL: PERFIL E AGENDA DO PROFISSIONAL -->
<div class="modal fade" id="modalSolicitar" tabindex="-1"
     aria-labelledby="tituloModalProfissional" aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content limpa-modal">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="tituloModalProfissional">
                        Conheça o profissional
                    </h5>
                    <small class="text-muted">
                        Confira as informações e os horários disponíveis.
                    </small>
                </div>

                <button type="button" class="btn-close"
                        data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                <div class="row g-4">

                    <!-- LADO ESQUERDO: FOTO E PERFIL -->
                    <div class="col-12 col-md-4">
                        <div class="perfil-profissional">
                            <img id="modalFotoProfissional"
                                 src=""
                                 alt="Foto do profissional"
                                 class="foto-profissional-modal"
                                 hidden>

                            <div id="modalFotoPlaceholder"
                                 class="foto-placeholder">
                                <span>👤</span>
                            </div>

                            <h4 id="modalNomeProfissional" class="mt-3">
                                Profissional
                            </h4>

                            <p class="text-muted mb-2">
                                <span>📍</span>
                                <span id="modalBairroProfissional">
                                    Local não informado
                                </span>
                            </p>

                            <div class="preco-profissional">
                                <small>Valor anunciado</small>
                                <h4 id="modalPrecoProfissional">
                                    A combinar
                                </h4>
                            </div>
                        </div>
                    </div>

                    <!-- CENTRO: DESCRIÇÃO -->
                    <div class="col-12 col-md-4">
                        <h5 class="titulo-secao-modal">Sobre o profissional</h5>

                        <p id="modalDescricaoProfissional"
                           class="descricao-profissional">
                            Nenhuma descrição informada.
                        </p>

                        <div class="aviso-agenda">
                            <strong>Antes de solicitar</strong>
                            <p class="mb-0">
                                Selecione uma data e um horário disponíveis.
                                A solicitação deverá ser confirmada pelo sistema.
                            </p>
                        </div>
                    </div>

                    <!-- LADO DIREITO: CALENDÁRIO E HORÁRIOS -->
                    <div class="col-12 col-md-4">
                        <h5 class="titulo-secao-modal">Agenda</h5>

                        <label for="dataServico" class="form-label">
                            Escolha o dia
                        </label>

                        <input type="date"
                               id="dataServico"
                               class="form-control"
                               min="<?= date('Y-m-d'); ?>">

                        <label for="horarioServico" class="form-label mt-3">
                            Horário disponível
                        </label>

                        <select id="horarioServico" class="form-select">
                            <option value="">Escolha primeiro uma data</option>
                        </select>

                        <div id="mensagemAgenda"
                             class="small text-muted mt-3"
                             aria-live="polite">
                            Selecione uma data para consultar os horários.
                        </div>

                        <button type="button"
                                id="btnSolicitarServico"
                                class="btn btn-primary w-100 mt-4">
                            Solicitar serviço
                        </button>

                        <p class="small text-muted mt-2 mb-0">
                            A escolha do horário não garante a reserva até a
                            solicitação ser registrada e confirmada.
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .limpa-modal {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
    }

    .limpa-modal .modal-header {
        background: #E7F5F1;
        border-bottom: 1px solid #D5E5E1;
        padding: 22px;
    }

    .limpa-modal .modal-body {
        padding: 24px;
    }

    .perfil-profissional {
        text-align: center;
        padding: 20px 14px;
        background: #F4F9F7;
        border-radius: 14px;
        height: 100%;
    }

    .foto-profissional-modal {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #18A999;
    }

    .foto-placeholder {
        width: 150px;
        height: 150px;
        margin: auto;
        border-radius: 50%;
        background: #D5E5E1;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 55px;
    }

    .foto-placeholder[hidden],
    .foto-profissional-modal[hidden] {
        display: none !important;
    }

    .preco-profissional {
        margin-top: 22px;
        padding: 14px;
        background: white;
        border-radius: 12px;
    }

    .preco-profissional small {
        color: #617477;
    }

    .preco-profissional h4 {
        color: #126E68;
        font-weight: 700;
        margin: 5px 0 0;
    }

    .titulo-secao-modal {
        color: #126E68;
        font-weight: 700;
        margin-bottom: 16px;
    }

    .descricao-profissional {
        color: #243438;
        line-height: 1.7;
        white-space: pre-line;
        overflow-wrap: anywhere;
    }

    .aviso-agenda {
        padding: 14px;
        border-radius: 10px;
        background: #E7F5F1;
        color: #243438;
        font-size: 14px;
    }

    .aviso-agenda strong {
        display: block;
        color: #126E68;
        margin-bottom: 6px;
    }

    #dataServico,
    #horarioServico {
        min-height: 45px;
    }

    #btnSolicitarServico {
        background: #18A999;
        border-color: #18A999;
        padding: 12px;
        font-weight: 600;
    }

    #btnSolicitarServico:hover {
        background: #0F857C;
        border-color: #0F857C;
    }

    @media (max-width: 767px) {
        .limpa-modal .modal-body {
            padding: 16px;
        }

        .foto-profissional-modal,
        .foto-placeholder {
            width: 120px;
            height: 120px;
        }
    }
</style>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalSolicitar');

    if (!modal) return;

    const foto = document.getElementById('modalFotoProfissional');
    const placeholder = document.getElementById('modalFotoPlaceholder');
    const nome = document.getElementById('modalNomeProfissional');
    const descricao = document.getElementById('modalDescricaoProfissional');
    const bairro = document.getElementById('modalBairroProfissional');
    const preco = document.getElementById('modalPrecoProfissional');
    const data = document.getElementById('dataServico');
    const horario = document.getElementById('horarioServico');
    const mensagem = document.getElementById('mensagemAgenda');
    const botaoSolicitar = document.getElementById('btnSolicitarServico');

    let idUsuarioAtual = null;
    let agendaAtual = [];

    const diasSemana = {
        DOMINGO: 0,
        SEGUNDA: 1,
        TERCA: 2,
        QUARTA: 3,
        QUINTA: 4,
        SEXTA: 5,
        SABADO: 6
    };

    function limparHorarios(texto) {
        horario.innerHTML = '';

        const opcao = document.createElement('option');
        opcao.value = '';
        opcao.textContent = texto;

        horario.appendChild(opcao);
    }

    function dataLocalISO() {
        const agora = new Date();
        const ano = agora.getFullYear();
        const mes = String(agora.getMonth() + 1).padStart(2, '0');
        const dia = String(agora.getDate()).padStart(2, '0');

        return `${ano}-${mes}-${dia}`;
    }

    function diaSemanaDaData(valor) {
        const partes = valor.split('-').map(Number);
        const dataEscolhida = new Date(
            partes[0],
            partes[1] - 1,
            partes[2]
        );

        return dataEscolhida.getDay();
    }

    function montarOpcoesDeHorario() {
        limparHorarios('Selecione um horário');

        const dataEscolhida = data.value;

        if (!dataEscolhida) {
            mensagem.textContent = 'Selecione uma data para consultar os horários.';
            return;
        }

        if (dataEscolhida < dataLocalISO()) {
            mensagem.textContent = 'Escolha uma data de hoje em diante.';
            limparHorarios('Data inválida');
            return;
        }

        const dia = diaSemanaDaData(dataEscolhida);

        const disponibilidadeDoDia = agendaAtual.filter(function (item) {
            return diasSemana[item.DIA_SEMANA] === dia;
        });

        if (disponibilidadeDoDia.length === 0) {
            mensagem.textContent =
                'Este profissional não informou disponibilidade para esse dia.';

            limparHorarios('Sem horários disponíveis');
            return;
        }

        let quantidade = 0;

        disponibilidadeDoDia.forEach(function (item) {
            const inicio = item.HORA_INICIO.substring(0, 5);
            const fim = item.HORA_FIM.substring(0, 5);

            const partesInicio = inicio.split(':').map(Number);
            const partesFim = fim.split(':').map(Number);

            let minutos = partesInicio[0] * 60 + partesInicio[1];
            const fimMinutos = partesFim[0] * 60 + partesFim[1];

            // Exibe opções em intervalos de 1 hora.
            while (minutos < fimMinutos) {
                const hora = String(Math.floor(minutos / 60)).padStart(2, '0');
                const minuto = String(minutos % 60).padStart(2, '0');
                const valor = `${hora}:${minuto}`;

                const opcao = document.createElement('option');
                opcao.value = valor;
                opcao.textContent = valor;

                horario.appendChild(opcao);
                quantidade++;

                minutos += 60;
            }
        });

        if (quantidade === 0) {
            mensagem.textContent =
                'Não há horários disponíveis para essa data.';
            limparHorarios('Sem horários disponíveis');
            return;
        }

        mensagem.textContent = 'Escolha um dos horários apresentados.';
    }

    modal.addEventListener('show.bs.modal', async function (evento) {
        const botao = evento.relatedTarget;

        if (!botao) return;

        idUsuarioAtual = botao.dataset.idUsuario;
        agendaAtual = [];

        nome.textContent = botao.dataset.nome || 'Profissional';
        descricao.textContent =
            botao.dataset.descricao || 'Nenhuma descrição informada.';
        bairro.textContent =
            botao.dataset.bairro || 'Local não informado';
        preco.textContent = botao.dataset.preco || 'A combinar';

        const caminhoFoto = botao.dataset.foto || '';

        if (caminhoFoto) {
            foto.src = caminhoFoto;
            foto.hidden = false;
            placeholder.hidden = true;

            foto.onerror = function () {
                foto.hidden = true;
                placeholder.hidden = false;
            };
        } else {
            foto.removeAttribute('src');
            foto.hidden = true;
            placeholder.hidden = false;
        }

        data.value = '';
        data.min = dataLocalISO();

        limparHorarios('Escolha primeiro uma data');
        mensagem.textContent = 'Carregando disponibilidade...';

        try {
            const resposta = await fetch(
                'profissionais.php?acao=disponibilidade&id_usuario=' +
                encodeURIComponent(idUsuarioAtual),
                {
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            const resultado = await resposta.json();

            if (!resposta.ok || !resultado.sucesso) {
                throw new Error(
                    resultado.mensagem || 'Erro ao consultar agenda.'
                );
            }

            agendaAtual = resultado.horarios || [];

            if (agendaAtual.length === 0) {
                mensagem.textContent =
                    'Este profissional ainda não cadastrou seus horários.';
            } else {
                mensagem.textContent =
                    'Disponibilidade carregada. Escolha uma data.';
            }
        } catch (erro) {
            agendaAtual = [];
            mensagem.textContent =
                'Não foi possível carregar a agenda. Tente novamente.';
            console.error(erro);
        }
    });

    data.addEventListener('change', montarOpcoesDeHorario);

    botaoSolicitar.addEventListener('click', function () {
        if (!idUsuarioAtual) {
            alert('Não foi possível identificar o profissional.');
            return;
        }

        if (!data.value || !horario.value) {
            alert('Escolha uma data e um horário disponíveis.');
            return;
        }

        if (data.value < dataLocalISO()) {
            alert('Escolha uma data de hoje em diante.');
            return;
        }

        alert(
            'Você selecionou o dia ' +
            data.value.split('-').reverse().join('/') +
            ' às ' + horario.value + '.\n\n' +
            "⚠️ AVISO DO ADMINISTRADOR\n\n" +
    "ADM VINI AINDA PRECISA CONECTAR A AGENDA COM O BANCO DE DADOS.\n\n" +
    "O AGENDAMENTO AINDA NÃO ESTÁ FUNCIONANDO.\n\n" +
    "ATT: ADM VINI"
        );
    });
});
</script>


<?php include "rodape.php"; ?>