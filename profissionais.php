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

                <a href="entrar.php" class="btn btn-primary w-100">

                    👤 Ver profissional

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

<?php include "rodape.php"; ?>