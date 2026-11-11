
<?php
// =====================================================
// PROFISSIONAIS - LIMPA LAR
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// FUNÇÕES
// =====================================================

function escapar($texto)
{
    return htmlspecialchars(
        (string) ($texto ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

// Sistema de estrelas.

if (is_file(__DIR__ . "/exibir_estrelas.php")) {
    require_once __DIR__ . "/exibir_estrelas.php";
}

if (!function_exists("exibir_estrelas")) {

    function exibir_estrelas($nota)
    {
        $cheias = max(
            0,
            min(5, (int) round((float) $nota))
        );

        return '<span style="color:#E5A800">' .
            str_repeat('★', $cheias) .
            str_repeat('☆', 5 - $cheias) .
            '</span>';
    }
}

// =====================================================
// CONEXÃO COM O BANCO
// =====================================================

$anuncios = [];
$erro_consulta = "";
$conexao_ok = false;

try {

    require_once __DIR__ . "/conexao.php";

    $conexao_ok = isset($conexao)
        && $conexao instanceof mysqli;

    if (!$conexao_ok) {
        throw new Exception(
            "A conexão com o MySQL não foi iniciada."
        );
    }

} catch (Throwable $erro) {

    error_log(
        "LimpaLar - conexão: " . $erro->getMessage()
    );

    $erro_consulta =
        "Não foi possível conectar ao banco de dados. " .
        "Verifique o MySQL no XAMPP.";
}

// =====================================================
// CONSULTAR DISPONIBILIDADE DO PROFISSIONAL
// =====================================================

if (($_GET["acao"] ?? "") === "disponibilidade") {

    header("Content-Type: application/json; charset=utf-8");

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
            "horarios" => [],
            "ocupados" => []
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (!$conexao_ok) {

        http_response_code(503);

        echo json_encode([
            "sucesso" => false,
            "mensagem" => $erro_consulta,
            "horarios" => [],
            "ocupados" => []
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    try {

        // Busca horários cadastrados.

        $sqlAgenda = "
            SELECT
                DIA_SEMANA,
                HORA_INICIO,
                HORA_FIM
            FROM DISPONIBILIDADE
            WHERE ID_USUARIO = ?
              AND ATIVO = 1
            ORDER BY HORA_INICIO
        ";

        $stmtAgenda = mysqli_prepare(
            $conexao,
            $sqlAgenda
        );

        mysqli_stmt_bind_param(
            $stmtAgenda,
            "i",
            $idUsuario
        );

        mysqli_stmt_execute($stmtAgenda);

        $resultadoAgenda = mysqli_stmt_get_result(
            $stmtAgenda
        );

        $horarios = mysqli_fetch_all(
            $resultadoAgenda,
            MYSQLI_ASSOC
        );

        mysqli_stmt_close($stmtAgenda);

        // Busca horários ocupados.

        $sqlOcupados = "
            SELECT
                DATE_FORMAT(
                    DATA_SERVICO,
                    '%Y-%m-%d'
                ) AS DATA_SERVICO,

                TIME_FORMAT(
                    HORA_SERVICO,
                    '%H:%i'
                ) AS HORA_SERVICO

            FROM CONTRATACAO

            WHERE ID_PROFISSIONAL = ?
              AND DATA_SERVICO >= CURDATE()
              AND STATUS IN (
                  'SOLICITADA',
                  'ACEITA'
              )
        ";

        $stmtOcupados = mysqli_prepare(
            $conexao,
            $sqlOcupados
        );

        mysqli_stmt_bind_param(
            $stmtOcupados,
            "i",
            $idUsuario
        );

        mysqli_stmt_execute($stmtOcupados);

        $resultadoOcupados = mysqli_stmt_get_result(
            $stmtOcupados
        );

        $ocupados = mysqli_fetch_all(
            $resultadoOcupados,
            MYSQLI_ASSOC
        );

        mysqli_stmt_close($stmtOcupados);

        echo json_encode([
            "sucesso" => true,
            "mensagem" => "",
            "horarios" => $horarios,
            "ocupados" => $ocupados
        ], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $erro) {

        error_log(
            "LimpaLar - agenda: " . $erro->getMessage()
        );

        http_response_code(503);

        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Não foi possível carregar a agenda deste profissional.",
            "horarios" => [],
            "ocupados" => []
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// =====================================================
// LISTAR ANÚNCIOS ATIVOS
// =====================================================

if ($conexao_ok) {

    try {

        $sql = "
            SELECT
                A.ID_ANUNCIO,
                A.ID_USUARIO,
                A.DESCRICAO,
                A.FOTO,

                U.NOME,
                U.FOTO AS FOTO_USUARIO,

                (
                    SELECT AVG(V.NOTA)
                    FROM AVALIACAO V
                    WHERE V.ID_AVALIADO = A.ID_USUARIO
                ) AS MEDIA,

                (
                    SELECT COUNT(*)
                    FROM AVALIACAO V
                    WHERE V.ID_AVALIADO = A.ID_USUARIO
                ) AS TOTAL

            FROM ANUNCIO A

            INNER JOIN USUARIO U
                ON U.ID_USUARIO = A.ID_USUARIO

            WHERE A.STATUS = ?
              AND U.ATIVO = ?

            ORDER BY A.DATA_CADASTRO DESC
        ";

        $status_ativo = "ATIVO";
        $usuario_ativo = 1;

        $stmt = mysqli_prepare(
            $conexao,
            $sql
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $status_ativo,
            $usuario_ativo
        );

        mysqli_stmt_execute($stmt);

        $resultado = mysqli_stmt_get_result($stmt);

        $anuncios = mysqli_fetch_all(
            $resultado,
            MYSQLI_ASSOC
        );

        mysqli_stmt_close($stmt);

    } catch (Throwable $erro) {

        error_log(
            "LimpaLar - anúncios: " . $erro->getMessage()
        );

        $erro_consulta =
            "Não foi possível consultar os anúncios. " .
            "Confira as tabelas ANUNCIO, USUARIO e AVALIACAO.";
    }
}

$id_usuario_logado = (int) (
    $_SESSION["ID_USUARIO"] ?? 0
);

include __DIR__ . "/cabecalho.php";
?>

<!-- =====================================================
     ESTILOS DA PÁGINA
====================================================== -->

<style>
    .prof-titulo {
        color: #126E68;
        font-weight: 700;
    }

    .prof-texto {
        color: #617477;
    }

    .prof-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        transition: transform .2s, box-shadow .2s;
    }

    .prof-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(18, 110, 104, .13) !important;
    }

    .prof-foto,
    .prof-sem-foto {
        width: 100%;
        height: 205px;
    }

    .prof-foto {
        object-fit: cover;
    }

    .prof-sem-foto {
        display: flex;
        align-items: center;
        justify-content: center;
        background: #E7F5F1;
        font-size: 65px;
    }

    .prof-descricao {
        color: #617477;
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    .prof-preco {
        color: #126E68;
        font-weight: 700;
        font-size: 17px;
    }

    .btn-limpalar {
        background: #18A999;
        border-color: #18A999;
        color: white;
    }

    .btn-limpalar:hover,
    .btn-limpalar:focus {
        background: #126E68;
        border-color: #126E68;
        color: white;
    }

    /* MODAL */

    .prof-modal .modal-header {
        background: #E7F5F1;
    }

    .prof-modal .modal-content {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
    }

    .prof-modal-foto {
        width: 100%;
        height: 220px;
        object-fit: cover;
        border-radius: 12px;
    }

    .prof-modal-placeholder {
        height: 220px;
        background: #E7F5F1;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 65px;
    }

    .prof-modal-descricao {
        white-space: pre-line;
        overflow-wrap: anywhere;
    }

    .prof-modal label {
        color: #243438;
        font-weight: 600;
    }

    .prof-modal .form-control:focus,
    .prof-modal .form-select:focus {
        border-color: #18A999;
        box-shadow: 0 0 0 .2rem rgba(24, 169, 153, .15);
    }
</style>

<!-- =====================================================
     LISTA DE PROFISSIONAIS
====================================================== -->

<section class="container py-5">

    <div class="text-center mb-5">

        <h1 class="prof-titulo">
            🧹 Profissionais de limpeza
        </h1>

        <p class="prof-texto mb-0">
            Conheça os profissionais disponíveis,
            confira as avaliações e solicite sua faxina.
        </p>

    </div>

    <?php if ($erro_consulta !== ""): ?>

        <div class="alert alert-danger" role="alert">
            <?= escapar($erro_consulta); ?>
        </div>

    <?php elseif (empty($anuncios)): ?>

        <div class="alert alert-light border text-center">
            Ainda não existem anúncios ativos de profissionais.
        </div>

    <?php else: ?>

        <!-- CARDS DOS PROFISSIONAIS -->

        <div class="row g-4">

            <?php foreach ($anuncios as $anuncio): ?>

                <?php

                // Escolhe uma foto válida.

                $foto = "";

                foreach (
                    [
                        $anuncio["FOTO_USUARIO"] ?? "",
                        $anuncio["FOTO"] ?? ""
                    ] as $caminho
                ) {

                    $caminho = trim((string) $caminho);

                    if (
                        $caminho !== "" &&
                        is_file(__DIR__ . "/" . $caminho)
                    ) {
                        $foto = $caminho;
                        break;
                    }
                }

                $foto_valida = $foto !== "";

                $descricao = (string) (
                    $anuncio["DESCRICAO"] ?? ""
                );

                $tamanho = function_exists("mb_strlen")
                    ? mb_strlen($descricao, "UTF-8")
                    : strlen($descricao);

                $descricao_curta = $tamanho > 140
                    ? (
                        function_exists("mb_substr")
                            ? mb_substr($descricao, 0, 140, "UTF-8")
                            : substr($descricao, 0, 140)
                    ) . "..."
                    : $descricao;

                $preco = "Valor a combinar diretamente";

                $media = (float) (
                    $anuncio["MEDIA"] ?? 0
                );

                $total = (int) (
                    $anuncio["TOTAL"] ?? 0
                );

                ?>

                <div class="col-12 col-md-6 col-lg-4">

                    <article class="card prof-card shadow-sm h-100">

                        <!-- FOTO -->

                        <?php if ($foto_valida): ?>

                            <img
                                src="<?= escapar($foto); ?>"
                                class="prof-foto"
                                alt="Foto de <?= escapar($anuncio["NOME"]); ?>"
                                loading="lazy">

                        <?php else: ?>

                            <div class="prof-sem-foto">
                                🧹
                            </div>

                        <?php endif; ?>

                        <div class="card-body p-4 d-flex flex-column">

                            <!-- NOME -->

                            <h2 class="h5 prof-titulo mb-3">
                                <?= escapar($anuncio["NOME"]); ?>
                            </h2>

                            <!-- DESCRIÇÃO -->

                            <p class="prof-descricao mb-3">
                                <?= nl2br(escapar($descricao_curta)); ?>
                            </p>

                            <!-- AVALIAÇÕES -->

                            <div class="mb-3">

                                <?php if ($total > 0): ?>

                                    <?= exibir_estrelas($media); ?>

                                    <span class="small prof-texto ms-1">

                                        <?= escapar(
                                            number_format(
                                                $media,
                                                1,
                                                ",",
                                                "."
                                            )
                                        ); ?>

                                        (<?= $total; ?> avaliações)

                                    </span>

                                <?php else: ?>

                                    <span class="small prof-texto">
                                        Ainda sem avaliações
                                    </span>

                                <?php endif; ?>

                            </div>

                            <!-- VALOR -->

                            <div class="prof-preco mt-auto mb-3">
                                <?= escapar($preco); ?>
                            </div>

                            <!-- VER PROFISSIONAL -->

                            <button
                                type="button"
                                class="btn btn-limpalar w-100 py-2"
                                data-bs-toggle="modal"
                                data-bs-target="#modalSolicitar"

                                data-id-anuncio="<?= (int) $anuncio["ID_ANUNCIO"]; ?>"
                                data-id-usuario="<?= (int) $anuncio["ID_USUARIO"]; ?>"

                                data-nome="<?= escapar($anuncio["NOME"]); ?>"
                                data-descricao="<?= escapar($descricao); ?>"

                                data-foto="<?= $foto_valida ? escapar($foto) : ""; ?>"

                                data-media="<?= escapar(
                                    number_format($media, 1, ".", "")
                                ); ?>"

                                data-total="<?= $total; ?>">

                                Ver profissional

                            </button>

                        </div>

                    </article>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<!-- =====================================================
     MODAL DO PROFISSIONAL
====================================================== -->

<div
    class="modal fade prof-modal"
    id="modalSolicitar"
    tabindex="-1"
    aria-labelledby="tituloModalProfissional"
    aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content">

            <!-- CABEÇALHO DO MODAL -->

            <div class="modal-header px-4 py-3">

                <h2
                    class="modal-title h5 prof-titulo"
                    id="tituloModalProfissional">

                    Conheça o profissional

                </h2>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar">
                </button>

            </div>

            <!-- CONTEÚDO -->

            <div class="modal-body p-4">

                <div class="row g-4">

                    <!-- PERFIL -->

                    <div class="col-12 col-lg-4">

                        <img
                            id="modalFotoProfissional"
                            class="prof-modal-foto mb-3"
                            alt="Foto do profissional"
                            hidden>

                        <div
                            id="modalFotoPlaceholder"
                            class="prof-modal-placeholder mb-3">

                            🧹

                        </div>

                        <h3
                            class="h4 prof-titulo"
                            id="modalNomeProfissional">

                            Profissional

                        </h3>

                        <div
                            id="modalAvaliacao"
                            class="mb-3 prof-texto">
                        </div>

                        <div
                            class="prof-preco"
                            id="modalPrecoProfissional">

                            Valor a combinar diretamente

                        </div>

                    </div>

                    <!-- DESCRIÇÃO -->

                    <div class="col-12 col-lg-4">

                        <h3 class="h5 prof-titulo">
                            Sobre o profissional
                        </h3>

                        <p
                            id="modalDescricaoProfissional"
                            class="prof-texto prof-modal-descricao">

                            Sem descrição.

                        </p>

                        <div
                            class="p-3 rounded-3"
                            style="background:#E7F5F1;color:#243438">

                            O profissional recebe sua solicitação
                            e decide se aceita.

                            Após a aceitação, o contato fica
                            disponível para combinar os detalhes
                            e o valor diretamente.

                        </div>

                    </div>

                    <!-- AGENDAMENTO -->

                    <div class="col-12 col-lg-4">

                        <h3 class="h5 prof-titulo mb-3">
                            Solicitar faxina
                        </h3>

                        <div
                            id="avisoUsuario"
                            class="alert alert-warning"
                            hidden>
                        </div>

                        <form id="formSolicitarServico">

                            <!-- DATA -->

                            <div class="mb-3">

                                <label
                                    for="dataServico"
                                    class="form-label">

                                    Data da faxina

                                </label>

                                <input
                                    type="date"
                                    id="dataServico"
                                    class="form-control"
                                    required>

                            </div>

                            <!-- HORÁRIO -->

                            <div class="mb-3">

                                <label
                                    for="horarioServico"
                                    class="form-label">

                                    Horário disponível

                                </label>

                                <select
                                    id="horarioServico"
                                    class="form-select"
                                    required>

                                    <option value="">
                                        Escolha primeiro uma data
                                    </option>

                                </select>

                                <div
                                    id="mensagemAgenda"
                                    class="small prof-texto mt-2"
                                    aria-live="polite">
                                </div>

                            </div>

                            <!-- ENDEREÇO -->

                            <div class="mb-3">

                                <label
                                    for="enderecoServico"
                                    class="form-label">

                                    Endereço da faxina

                                </label>

                                <input
                                    type="text"
                                    id="enderecoServico"
                                    class="form-control"
                                    maxlength="255"
                                    placeholder="Rua ou avenida"
                                    required>

                            </div>

                            <!-- NÚMERO E BAIRRO DO ENDEREÇO -->

                            <div class="row g-2 mb-3">

                                <div class="col-4">

                                    <label
                                        for="numeroServico"
                                        class="form-label">

                                        Número

                                    </label>

                                    <input
                                        type="text"
                                        id="numeroServico"
                                        class="form-control"
                                        maxlength="20"
                                        required>

                                </div>

                                <div class="col-8">

                                    <label
                                        for="bairroServico"
                                        class="form-label">

                                        Bairro da faxina

                                    </label>

                                    <input
                                        type="text"
                                        id="bairroServico"
                                        class="form-control"
                                        maxlength="100"
                                        placeholder="Bairro do endereço">

                                </div>

                            </div>

                            <!-- OBSERVAÇÃO -->

                            <div class="mb-3">

                                <label
                                    for="observacaoServico"
                                    class="form-label">

                                    Observação (opcional)

                                </label>

                                <textarea
                                    id="observacaoServico"
                                    class="form-control"
                                    rows="2"
                                    maxlength="1000"></textarea>

                            </div>

                            <!-- MENSAGENS -->

                            <div
                                id="mensagemSolicitacao"
                                class="mb-3"
                                aria-live="polite">
                            </div>

                            <!-- BOTÃO SOLICITAR -->

                            <button
                                type="submit"
                                id="btnSolicitarServico"
                                class="btn btn-limpalar w-100 py-2">

                                Solicitar serviço

                            </button>

                            <!-- ACOMPANHAR SOLICITAÇÃO -->

                            <a
                                href="concluir_faxina.php?aba=contratei"
                                id="verMinhasFaxinas"
                                class="btn btn-outline-success w-100 mt-2 d-none">

                                Acompanhar em Minhas faxinas

                            </a>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- =====================================================
     JAVASCRIPT - MODAL E AGENDAMENTO
====================================================== -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('modalSolicitar');

    if (!modal) {
        return;
    }

    const idUsuarioLogado = <?= $id_usuario_logado; ?>;

    // =====================================================
    // ELEMENTOS DO MODAL
    // =====================================================

    const foto = document.getElementById(
        'modalFotoProfissional'
    );

    const placeholder = document.getElementById(
        'modalFotoPlaceholder'
    );

    const nome = document.getElementById(
        'modalNomeProfissional'
    );

    const descricao = document.getElementById(
        'modalDescricaoProfissional'
    );

    const avaliacao = document.getElementById(
        'modalAvaliacao'
    );

    const data = document.getElementById(
        'dataServico'
    );

    const horario = document.getElementById(
        'horarioServico'
    );

    const mensagemAgenda = document.getElementById(
        'mensagemAgenda'
    );

    const mensagemSolicitacao = document.getElementById(
        'mensagemSolicitacao'
    );

    const avisoUsuario = document.getElementById(
        'avisoUsuario'
    );

    const formulario = document.getElementById(
        'formSolicitarServico'
    );

    const botaoSolicitar = document.getElementById(
        'btnSolicitarServico'
    );

    const acompanhar = document.getElementById(
        'verMinhasFaxinas'
    );

    // =====================================================
    // DIAS DA SEMANA
    // =====================================================

    const diasSemana = [
        'DOMINGO',
        'SEGUNDA',
        'TERCA',
        'QUARTA',
        'QUINTA',
        'SEXTA',
        'SABADO'
    ];

    let idUsuarioAtual = 0;
    let idAnuncioAtual = 0;

    let horariosDisponiveis = [];
    let horariosOcupados = [];

    let agendaCarregada = false;
    let bloqueado = false;

    let identificadorConsulta = 0;

    // Ignora consultas de modais fechados.

    modal.addEventListener(
        'hidden.bs.modal',
        function () {
            identificadorConsulta++;
        }
    );

    // =====================================================
    // DATA LOCAL
    // =====================================================

    function dataLocalISO() {

        const agora = new Date();

        const ano = agora.getFullYear();

        const mes = String(
            agora.getMonth() + 1
        ).padStart(2, '0');

        const dia = String(
            agora.getDate()
        ).padStart(2, '0');

        return `${ano}-${mes}-${dia}`;
    }

    // =====================================================
    // LIMPAR HORÁRIOS
    // =====================================================

    function limparHorarios(texto) {

        horario.replaceChildren(
            new Option(texto, '')
        );
    }

    // =====================================================
    // MOSTRAR MENSAGENS
    // =====================================================

    function mostrarMensagem(texto, tipo = 'danger') {

        mensagemSolicitacao.textContent = texto;

        mensagemSolicitacao.className =
            `alert alert-${tipo} mb-3`;
    }

    // =====================================================
    // MONTAR HORÁRIOS DISPONÍVEIS
    // =====================================================

    function horariosDaData() {

        limparHorarios('Selecione um horário');

        const valorData = data.value;

        if (!valorData || !agendaCarregada) {
            return;
        }

        if (valorData < dataLocalISO()) {

            mensagemAgenda.textContent =
                'Selecione uma data de hoje em diante.';

            return;
        }

        const partes = valorData
            .split('-')
            .map(Number);

        const diaSemana = diasSemana[
            new Date(
                partes[0],
                partes[1] - 1,
                partes[2]
            ).getDay()
        ];

        const agora = new Date();

        const minutosAgora =
            agora.getHours() * 60 +
            agora.getMinutes();

        const opcoes = new Set();

        horariosDisponiveis.forEach(item => {

            if (item.DIA_SEMANA !== diaSemana) {
                return;
            }

            const [hi, mi] = item.HORA_INICIO
                .substring(0, 5)
                .split(':')
                .map(Number);

            const [hf, mf] = item.HORA_FIM
                .substring(0, 5)
                .split(':')
                .map(Number);

            const fim = hf * 60 + mf;

            // Intervalos de uma hora.

            for (
                let minuto = hi * 60 + mi;
                minuto + 60 <= fim;
                minuto += 60
            ) {

                // Não mostra horários passados.

                if (
                    valorData === dataLocalISO() &&
                    minuto <= minutosAgora
                ) {
                    continue;
                }

                const valor =
                    String(
                        Math.floor(minuto / 60)
                    ).padStart(2, '0') +
                    ':' +
                    String(
                        minuto % 60
                    ).padStart(2, '0');

                // Não mostra horários ocupados.

                const ocupado = horariosOcupados.some(
                    itemOcupado =>

                        itemOcupado.DATA_SERVICO === valorData &&

                        String(
                            itemOcupado.HORA_SERVICO
                        ).substring(0, 5) === valor
                );

                if (!ocupado) {
                    opcoes.add(valor);
                }
            }
        });

        [...opcoes]
            .sort()
            .forEach(valor => {

                horario.add(
                    new Option(valor, valor)
                );
            });

        if (opcoes.size === 0) {

            limparHorarios('Sem horários disponíveis');

            mensagemAgenda.textContent =
                'Não há horários livres para essa data.';

        } else {

            mensagemAgenda.textContent =
                'Escolha um dos horários disponíveis.';
        }
    }

    // =====================================================
    // ABRIR MODAL E CARREGAR PROFISSIONAL
    // =====================================================

    modal.addEventListener(
        'show.bs.modal',
        async function (evento) {

            const botao = evento.relatedTarget;

            if (!botao) {
                return;
            }

            const consultaAtual = ++identificadorConsulta;

            formulario.reset();

            acompanhar.classList.add('d-none');

            mensagemSolicitacao.className = 'mb-3';
            mensagemSolicitacao.textContent = '';

            idAnuncioAtual = Number(
                botao.dataset.idAnuncio || 0
            );

            idUsuarioAtual = Number(
                botao.dataset.idUsuario || 0
            );

            horariosDisponiveis = [];
            horariosOcupados = [];

            agendaCarregada = false;

            data.min = dataLocalISO();

            limparHorarios(
                'Escolha primeiro uma data'
            );

            nome.textContent =
                botao.dataset.nome || 'Profissional';

            descricao.textContent =
                botao.dataset.descricao ||
                'Nenhuma descrição informada.';

            // =================================================
            // AVALIAÇÕES
            // =================================================

            const total = Number(
                botao.dataset.total || 0
            );

            const media = Number(
                botao.dataset.media || 0
            );

            avaliacao.textContent = total > 0
                ? '★'.repeat(Math.round(media)) +
                    '☆'.repeat(5 - Math.round(media)) +
                    '  ' +
                    media.toFixed(1).replace('.', ',') +
                    ` (${total} avaliações)`
                : 'Ainda sem avaliações';

            // =================================================
            // FOTO
            // =================================================

            const caminhoFoto =
                botao.dataset.foto || '';

            foto.hidden = !caminhoFoto;
            placeholder.hidden = !!caminhoFoto;

            if (caminhoFoto) {

                foto.src = caminhoFoto;

                foto.onerror = () => {

                    foto.hidden = true;
                    placeholder.hidden = false;
                };

            } else {

                foto.removeAttribute('src');
            }

            // =================================================
            // VERIFICAR LOGIN E PRÓPRIO ANÚNCIO
            // =================================================

            bloqueado =
                idUsuarioLogado === 0 ||
                idUsuarioLogado === idUsuarioAtual;

            avisoUsuario.hidden = !bloqueado;
            avisoUsuario.replaceChildren();

            if (idUsuarioLogado === 0) {

                avisoUsuario.append(
                    'Para solicitar uma faxina, '
                );

                const link = document.createElement('a');

                link.href = 'entrar.php';
                link.textContent = 'entre na sua conta';

                avisoUsuario.appendChild(link);
                avisoUsuario.append('.');

            } else if (bloqueado) {

                avisoUsuario.textContent =
                    'Você não pode contratar seu próprio anúncio.';
            }

            botaoSolicitar.disabled = bloqueado;

            mensagemAgenda.textContent =
                'Carregando a agenda...';

            // =================================================
            // CONSULTAR DISPONIBILIDADE
            // =================================================

            try {

                const resposta = await fetch(
                    'profissionais.php?acao=disponibilidade&id_usuario=' +
                    encodeURIComponent(idUsuarioAtual),
                    {
                        cache: 'no-store',
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                const dados = await resposta.json();

                // Ignora respostas antigas.

                if (consultaAtual !== identificadorConsulta) {
                    return;
                }

                if (!resposta.ok || !dados.sucesso) {

                    throw new Error(
                        dados.mensagem ||
                        'Não foi possível consultar a agenda.'
                    );
                }

                horariosDisponiveis =
                    dados.horarios || [];

                horariosOcupados =
                    dados.ocupados || [];

                agendaCarregada = true;

                mensagemAgenda.textContent =
                    horariosDisponiveis.length
                        ? 'Escolha uma data para visualizar os horários.'
                        : 'Este profissional ainda não cadastrou horários na agenda.';

                if (data.value) {
                    horariosDaData();
                }

            } catch (erro) {

                if (consultaAtual !== identificadorConsulta) {
                    return;
                }

                mensagemAgenda.textContent =
                    erro.message || 'Agenda indisponível.';

                limparHorarios('Agenda indisponível');
            }
        }
    );

    // =====================================================
    // ALTERAR DATA
    // =====================================================

    data.addEventListener(
        'change',
        horariosDaData
    );

    // =====================================================
    // SOLICITAR SERVIÇO
    // =====================================================

    formulario.addEventListener(
        'submit',
        async function (evento) {

            evento.preventDefault();

            if (bloqueado) {
                return;
            }

            if (
                !idAnuncioAtual ||
                !data.value ||
                !horario.value
            ) {

                mostrarMensagem(
                    'Selecione uma data e um horário disponíveis.'
                );

                return;
            }

            botaoSolicitar.disabled = true;

            botaoSolicitar.textContent =
                'Enviando solicitação...';

            try {

                const resposta = await fetch(
                    'solicitar_servico.php',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },

                        body: JSON.stringify({

                            id_anuncio: idAnuncioAtual,

                            data_servico: data.value,

                            hora_servico: horario.value,

                            endereco_servico:
                                document.getElementById(
                                    'enderecoServico'
                                ).value.trim(),

                            numero_servico:
                                document.getElementById(
                                    'numeroServico'
                                ).value.trim(),

                            bairro_servico:
                                document.getElementById(
                                    'bairroServico'
                                ).value.trim(),

                            observacao:
                                document.getElementById(
                                    'observacaoServico'
                                ).value.trim()

                        })
                    }
                );

                const tipoResposta =
                    resposta.headers.get('content-type') || '';

                if (!tipoResposta.includes('application/json')) {

                    throw new Error(
                        'O servidor não retornou uma resposta válida.'
                    );
                }

                const dados = await resposta.json();

                if (resposta.status === 401) {

                    mostrarMensagem(
                        (dados.mensagem ||
                            'Faça login para continuar.') +
                        ' Acesse entrar.php.'
                    );

                    return;
                }

                if (!resposta.ok || !dados.sucesso) {

                    throw new Error(
                        dados.mensagem ||
                        'Não foi possível solicitar o serviço.'
                    );
                }

                // Solicitação enviada com sucesso.

                mostrarMensagem(
                    dados.mensagem ||
                    'Solicitação enviada com sucesso!',
                    'success'
                );

                acompanhar.classList.remove('d-none');

                // Marca horário como ocupado na tela.

                horariosOcupados.push({
                    DATA_SERVICO: data.value,
                    HORA_SERVICO: horario.value
                });

                horariosDaData();

            } catch (erro) {

                mostrarMensagem(
                    erro.message ||
                    'Erro ao enviar a solicitação.'
                );

            } finally {

                botaoSolicitar.disabled = bloqueado;

                botaoSolicitar.textContent =
                    'Solicitar serviço';
            }
        }
    );

});
</script>

<!-- FECHA O MAIN DO CABEÇALHO -->

</main>

<?php include __DIR__ . "/rodape.php"; ?>
