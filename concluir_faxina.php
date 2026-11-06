
<?php
// =====================================================
// MINHAS FAXINAS - LIMPA LAR
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// VERIFICA SE O USUÁRIO ESTÁ LOGADO
// =====================================================

if (!isset($_SESSION["ID_USUARIO"])) {
    header("Location: entrar.php");
    exit;
}

$id_usuario = (int) $_SESSION["ID_USUARIO"];

if ($id_usuario < 1) {
    header("Location: entrar.php");
    exit;
}

require_once __DIR__ . "/conexao.php";

date_default_timezone_set("America/Sao_Paulo");

// =====================================================
// FUNÇÕES DA TELA
// =====================================================

function escapar_faxina($texto)
{
    return htmlspecialchars(
        (string) ($texto ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function data_faxina($data)
{
    if (empty($data)) {
        return "Não informada";
    }

    $data_formatada = strtotime($data);

    return $data_formatada
        ? date("d/m/Y", $data_formatada)
        : "Não informada";
}

// =====================================================
// TOKEN DE SEGURANÇA DOS FORMULÁRIOS
// =====================================================

if (empty($_SESSION["token_faxinas"])) {
    $_SESSION["token_faxinas"] = bin2hex(random_bytes(32));
}

$token_faxinas = $_SESSION["token_faxinas"];

// =====================================================
// PROCESSA ACEITAR, RECUSAR, CANCELAR E CONCLUIR
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_contratacao = filter_input(
        INPUT_POST,
        "id_contratacao",
        FILTER_VALIDATE_INT
    );

    $acao = $_POST["acao"] ?? "";
    $token_enviado = $_POST["token_faxinas"] ?? "";

    $mensagem = "Não foi possível atualizar a solicitação.";
    $tipo_mensagem = "danger";
    $transacao_aberta = false;

    try {

        if (
            !is_string($token_enviado) ||
            !hash_equals($token_faxinas, $token_enviado)
        ) {
            throw new Exception(
                "Formulário inválido. Atualize a página e tente novamente."
            );
        }

        if (!$id_contratacao || $id_contratacao < 1) {
            throw new Exception("Solicitação inválida.");
        }

        if (!in_array(
            $acao,
            ["aceitar", "recusar", "cancelar", "concluir"],
            true
        )) {
            throw new Exception("Ação inválida.");
        }

        $hoje = date("Y-m-d");
        $agora = date("H:i:s");

        // =================================================
        // ACEITAR SOLICITAÇÃO
        // =================================================

        if ($acao === "aceitar") {

            mysqli_begin_transaction($conexao);
            $transacao_aberta = true;

            // Impede duas aceitações simultâneas.
            $sqlTrava = "
                SELECT ID_USUARIO
                FROM usuario
                WHERE ID_USUARIO = ?
                FOR UPDATE
            ";

            $stmtTrava = mysqli_prepare($conexao, $sqlTrava);

            mysqli_stmt_bind_param(
                $stmtTrava,
                "i",
                $id_usuario
            );

            mysqli_stmt_execute($stmtTrava);

            $existe_usuario = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmtTrava)
            );

            mysqli_stmt_close($stmtTrava);

            if (!$existe_usuario) {
                throw new Exception("Usuário não encontrado.");
            }

            // Somente o profissional recebe o pedido.
            $sqlSolicitacao = "
                SELECT
                    DATA_SERVICO,
                    HORA_SERVICO,
                    STATUS
                FROM contratacao
                WHERE ID_CONTRATACAO = ?
                  AND ID_PROFISSIONAL = ?
                FOR UPDATE
            ";

            $stmtSolicitacao = mysqli_prepare(
                $conexao,
                $sqlSolicitacao
            );

            mysqli_stmt_bind_param(
                $stmtSolicitacao,
                "ii",
                $id_contratacao,
                $id_usuario
            );

            mysqli_stmt_execute($stmtSolicitacao);

            $solicitacao = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmtSolicitacao)
            );

            mysqli_stmt_close($stmtSolicitacao);

            if (
                !$solicitacao ||
                $solicitacao["STATUS"] !== "SOLICITADA"
            ) {
                throw new Exception(
                    "A solicitação não está mais disponível para aceitação."
                );
            }

            if (empty($solicitacao["HORA_SERVICO"])) {
                throw new Exception(
                    "Essa solicitação não possui horário definido."
                );
            }

            if (
                $solicitacao["DATA_SERVICO"] < $hoje ||
                (
                    $solicitacao["DATA_SERVICO"] === $hoje &&
                    $solicitacao["HORA_SERVICO"] <= $agora
                )
            ) {
                throw new Exception(
                    "Não é possível aceitar um horário que já passou."
                );
            }

            // Verifica conflito com outro serviço aceito.
            $sqlConflito = "
                SELECT ID_CONTRATACAO
                FROM contratacao
                WHERE ID_PROFISSIONAL = ?
                  AND DATA_SERVICO = ?
                  AND HORA_SERVICO = ?
                  AND STATUS = 'ACEITA'
                  AND ID_CONTRATACAO <> ?
                LIMIT 1
            ";

            $stmtConflito = mysqli_prepare(
                $conexao,
                $sqlConflito
            );

            mysqli_stmt_bind_param(
                $stmtConflito,
                "issi",
                $id_usuario,
                $solicitacao["DATA_SERVICO"],
                $solicitacao["HORA_SERVICO"],
                $id_contratacao
            );

            mysqli_stmt_execute($stmtConflito);

            $conflito = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmtConflito)
            );

            mysqli_stmt_close($stmtConflito);

            if ($conflito) {
                throw new Exception(
                    "Você já aceitou outra faxina nesse dia e horário."
                );
            }

            $sqlAtualizar = "
                UPDATE contratacao
                SET STATUS = 'ACEITA'
                WHERE ID_CONTRATACAO = ?
                  AND ID_PROFISSIONAL = ?
                  AND STATUS = 'SOLICITADA'
            ";

            $stmtAtualizar = mysqli_prepare(
                $conexao,
                $sqlAtualizar
            );

            mysqli_stmt_bind_param(
                $stmtAtualizar,
                "ii",
                $id_contratacao,
                $id_usuario
            );

            mysqli_stmt_execute($stmtAtualizar);

            $alteradas = mysqli_stmt_affected_rows(
                $stmtAtualizar
            );

            mysqli_stmt_close($stmtAtualizar);

            if ($alteradas !== 1) {
                throw new Exception(
                    "Não foi possível aceitar esta solicitação."
                );
            }

            mysqli_commit($conexao);
            $transacao_aberta = false;

            $mensagem = "Solicitação aceita! Agora os telefones estão disponíveis para combinar os detalhes e o valor.";
            $tipo_mensagem = "success";

        // =================================================
        // RECUSAR SOLICITAÇÃO
        // =================================================

        } elseif ($acao === "recusar") {

            $sqlAtualizar = "
                UPDATE contratacao
                SET STATUS = 'RECUSADA'
                WHERE ID_CONTRATACAO = ?
                  AND ID_PROFISSIONAL = ?
                  AND STATUS = 'SOLICITADA'
            ";

            $stmtAtualizar = mysqli_prepare(
                $conexao,
                $sqlAtualizar
            );

            mysqli_stmt_bind_param(
                $stmtAtualizar,
                "ii",
                $id_contratacao,
                $id_usuario
            );

            mysqli_stmt_execute($stmtAtualizar);

            $alteradas = mysqli_stmt_affected_rows(
                $stmtAtualizar
            );

            mysqli_stmt_close($stmtAtualizar);

            if ($alteradas !== 1) {
                throw new Exception(
                    "Só é possível recusar uma solicitação pendente recebida por você."
                );
            }

            $mensagem = "Solicitação recusada.";
            $tipo_mensagem = "success";

        // =================================================
        // CANCELAR SOLICITAÇÃO
        // =================================================

        } elseif ($acao === "cancelar") {

            $sqlAtualizar = "
                UPDATE contratacao
                SET STATUS = 'CANCELADA'
                WHERE ID_CONTRATACAO = ?
                  AND (
                      ID_CONTRATANTE = ?
                      OR ID_PROFISSIONAL = ?
                  )
                  AND STATUS IN ('SOLICITADA', 'ACEITA')
            ";

            $stmtAtualizar = mysqli_prepare(
                $conexao,
                $sqlAtualizar
            );

            mysqli_stmt_bind_param(
                $stmtAtualizar,
                "iii",
                $id_contratacao,
                $id_usuario,
                $id_usuario
            );

            mysqli_stmt_execute($stmtAtualizar);

            $alteradas = mysqli_stmt_affected_rows(
                $stmtAtualizar
            );

            mysqli_stmt_close($stmtAtualizar);

            if ($alteradas !== 1) {
                throw new Exception(
                    "Essa solicitação não pode ser cancelada por você."
                );
            }

            $mensagem = "Solicitação cancelada.";
            $tipo_mensagem = "success";

        // =================================================
        // FINALIZAR FAXINA
        // =================================================

        } elseif ($acao === "concluir") {

            // Somente o profissional pode finalizar.
            // A faxina precisa estar ACEITA.
            // Não depende mais do horário agendado.

            $sqlAtualizar = "
                UPDATE contratacao
                SET STATUS = 'CONCLUIDA'
                WHERE ID_CONTRATACAO = ?
                  AND ID_PROFISSIONAL = ?
                  AND STATUS = 'ACEITA'
            ";

            $stmtAtualizar = mysqli_prepare(
                $conexao,
                $sqlAtualizar
            );

            mysqli_stmt_bind_param(
                $stmtAtualizar,
                "ii",
                $id_contratacao,
                $id_usuario
            );

            mysqli_stmt_execute($stmtAtualizar);

            $alteradas = mysqli_stmt_affected_rows(
                $stmtAtualizar
            );

            mysqli_stmt_close($stmtAtualizar);

            if ($alteradas !== 1) {
                throw new Exception(
                    "Apenas o profissional pode concluir uma faxina aceita."
                );
            }

            $mensagem = "Faxina marcada como concluída! O cliente já pode avaliar seu serviço.";
            $tipo_mensagem = "success";
        }

    } catch (Throwable $erro) {

        if ($transacao_aberta) {
            mysqli_rollback($conexao);
        }

        if ($erro instanceof mysqli_sql_exception) {

            error_log(
                "LimpaLar - minhas faxinas: " .
                $erro->getMessage()
            );

            $mensagem = "Ocorreu um erro no banco. Confira a estrutura da tabela CONTRATACAO.";

        } else {

            $mensagem = $erro->getMessage();
        }

        $tipo_mensagem = "danger";
    }

    // =================================================
    // EVITA REPETIR A AÇÃO AO APERTAR F5
    // =================================================

    $_SESSION["mensagem_faxinas"] = $mensagem;
    $_SESSION["tipo_mensagem_faxinas"] = $tipo_mensagem;

    $aba_retorno = in_array(
        $acao,
        ["aceitar", "recusar", "concluir"],
        true
    )
        ? "recebi"
        : ($_POST["aba_faxinas"] ?? "contratei");

    $aba_retorno = $aba_retorno === "recebi"
        ? "recebi"
        : "contratei";

    header(
        "Location: concluir_faxina.php?aba=" .
        $aba_retorno
    );

    exit;
}

// =====================================================
// CONSULTA AS FAXINAS DO USUÁRIO
// =====================================================

$faxinas_contratadas = [];
$faxinas_recebidas = [];
$erro_consulta = "";

try {

    $sql = "
        SELECT
            C.ID_CONTRATACAO,
            C.ID_CONTRATANTE,
            C.ID_PROFISSIONAL,
            C.DATA_SERVICO,
            C.HORA_SERVICO,
            C.ENDERECO_SERVICO,
            C.NUMERO_SERVICO,
            C.BAIRRO_SERVICO,
            C.CIDADE_SERVICO,
            C.OBSERVACAO,
            C.STATUS,

            UC.NOME AS NOME_CLIENTE,
            UC.TELEFONE AS TELEFONE_CLIENTE,

            UP.NOME AS NOME_PROFISSIONAL,
            UP.TELEFONE AS TELEFONE_PROFISSIONAL,

            EXISTS (
                SELECT 1
                FROM avaliacao V
                WHERE V.ID_CONTRATACAO = C.ID_CONTRATACAO
                  AND V.ID_AVALIADOR = ?
            ) AS JA_AVALIOU

        FROM contratacao C

        INNER JOIN usuario UC
            ON UC.ID_USUARIO = C.ID_CONTRATANTE

        INNER JOIN usuario UP
            ON UP.ID_USUARIO = C.ID_PROFISSIONAL

        WHERE
            C.ID_CONTRATANTE = ?
            OR C.ID_PROFISSIONAL = ?

        ORDER BY
            C.DATA_SERVICO DESC,
            C.HORA_SERVICO DESC
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iii",
        $id_usuario,
        $id_usuario,
        $id_usuario
    );

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    while ($faxina = mysqli_fetch_assoc($resultado)) {

        if (
            (int) $faxina["ID_CONTRATANTE"] === $id_usuario
        ) {
            $faxinas_contratadas[] = $faxina;
        }

        if (
            (int) $faxina["ID_PROFISSIONAL"] === $id_usuario
        ) {
            $faxinas_recebidas[] = $faxina;
        }
    }

    mysqli_stmt_close($stmt);

} catch (Throwable $erro) {

    error_log(
        "LimpaLar - listar faxinas: " .
        $erro->getMessage()
    );

    $erro_consulta =
        "Não foi possível carregar suas faxinas. Confira as tabelas no phpMyAdmin.";
}

// =====================================================
// FUNÇÃO DOS BOTÕES DE AÇÃO
// =====================================================

function botao_acao_faxina(
    $id_contratacao,
    $acao,
    $texto,
    $classe,
    $token,
    $aba = "contratei"
) {
    ?>

    <form
        method="POST"
        action="concluir_faxina.php"
        class="d-inline-block">

        <input
            type="hidden"
            name="token_faxinas"
            value="<?= escapar_faxina($token); ?>">

        <input
            type="hidden"
            name="id_contratacao"
            value="<?= (int) $id_contratacao; ?>">

        <input
            type="hidden"
            name="acao"
            value="<?= escapar_faxina($acao); ?>">

        <input
            type="hidden"
            name="aba_faxinas"
            value="<?= escapar_faxina($aba); ?>">

        <button
            type="submit"
            class="btn btn-sm <?= escapar_faxina($classe); ?>"

            <?php if (
                in_array(
                    $acao,
                    ["recusar", "cancelar"],
                    true
                )
            ): ?>

                onclick="return confirm('Confirma esta ação?');"

            <?php endif; ?>>

            <?= escapar_faxina($texto); ?>

        </button>

    </form>

    <?php
}

// =====================================================
// FUNÇÃO PARA EXIBIR OS CARDS DAS FAXINAS
// =====================================================

function mostrar_faxinas($faxinas, $papel, $token)
{
    if (empty($faxinas)) {
        ?>

        <div class="alert alert-light border rounded-4 mb-0">
            Nenhuma faxina encontrada nesta seção.
        </div>

        <?php
        return;
    }

    $classes_status = [
        "SOLICITADA" => "text-bg-warning",
        "ACEITA" => "text-bg-success",
        "RECUSADA" => "text-bg-danger",
        "CANCELADA" => "text-bg-secondary",
        "CONCLUIDA" => "text-bg-primary"
    ];

    $nomes_status = [
        "SOLICITADA" => "Aguardando resposta",
        "ACEITA" => "Aceita",
        "RECUSADA" => "Recusada",
        "CANCELADA" => "Cancelada",
        "CONCLUIDA" => "Concluída"
    ];

    foreach ($faxinas as $faxina) {

        $cliente = $papel === "cliente";
        $status = $faxina["STATUS"];

        $nome_outra_pessoa = $cliente
            ? $faxina["NOME_PROFISSIONAL"]
            : $faxina["NOME_CLIENTE"];

        $telefone_outra_pessoa = $cliente
            ? $faxina["TELEFONE_PROFISSIONAL"]
            : $faxina["TELEFONE_CLIENTE"];

        $mostrar_telefone = in_array(
            $status,
            ["ACEITA", "CONCLUIDA"],
            true
        );

        $telefone_limpo = preg_replace(
            "/\D/",
            "",
            (string) $telefone_outra_pessoa
        );

        // Código do Brasil para o WhatsApp.
        $telefone_whatsapp = strlen($telefone_limpo) === 11
            ? "55" . $telefone_limpo
            : $telefone_limpo;

        // Botão só para profissional com faxina ACEITA.
        $pode_concluir =
            !$cliente &&
            $status === "ACEITA";

        ?>

        <article class="card border-0 shadow-sm rounded-4 mb-3">

            <div class="card-body p-4">

                <!-- =====================================
                     CABEÇALHO DO CARD
                ====================================== -->

                <div
                    class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">

                    <div>

                        <h3
                            class="h5 fw-bold mb-1"
                            style="color:#126E68;">

                            <?= escapar_faxina(
                                $nome_outra_pessoa
                            ); ?>

                        </h3>

                        <span class="small text-secondary">

                            <?= $cliente
                                ? "Profissional contratado"
                                : "Cliente solicitante"; ?>

                        </span>

                    </div>

                    <span
                        class="badge rounded-pill <?= escapar_faxina(
                            $classes_status[$status] ??
                            'text-bg-secondary'
                        ); ?>">

                        <?= escapar_faxina(
                            $nomes_status[$status] ?? $status
                        ); ?>

                    </span>

                </div>

                <!-- =====================================
                     DATA, HORÁRIO E ENDEREÇO
                ====================================== -->

                <div class="row g-3 small">

                    <div class="col-12 col-md-6">

                        <strong>📅 Data:</strong>

                        <?= escapar_faxina(
                            data_faxina($faxina["DATA_SERVICO"])
                        ); ?>

                    </div>

                    <div class="col-12 col-md-6">

                        <strong>🕒 Horário:</strong>

                        <?= escapar_faxina(
                            $faxina["HORA_SERVICO"]
                                ? substr(
                                    $faxina["HORA_SERVICO"],
                                    0,
                                    5
                                )
                                : "A combinar"
                        ); ?>

                    </div>

                    <div class="col-12">

                        <strong>📍 Endereço da faxina:</strong>

                        <?= escapar_faxina(
                            $faxina["ENDERECO_SERVICO"] ?:
                            "Não informado"
                        ); ?>

                        <?= $faxina["NUMERO_SERVICO"]
                            ? ", " . escapar_faxina(
                                $faxina["NUMERO_SERVICO"]
                            )
                            : ""; ?>

                        <?= $faxina["BAIRRO_SERVICO"]
                            ? " - " . escapar_faxina(
                                $faxina["BAIRRO_SERVICO"]
                            )
                            : ""; ?>

                        <?= $faxina["CIDADE_SERVICO"]
                            ? " - " . escapar_faxina(
                                $faxina["CIDADE_SERVICO"]
                            )
                            : ""; ?>

                    </div>

                    <?php if (!empty($faxina["OBSERVACAO"])): ?>

                        <div class="col-12">

                            <strong>📝 Observação:</strong>

                            <span
                                style="white-space:pre-wrap;overflow-wrap:anywhere;">

                                <?= escapar_faxina(
                                    $faxina["OBSERVACAO"]
                                ); ?>

                            </span>

                        </div>

                    <?php endif; ?>

                </div>

                <!-- =====================================
                     TELEFONE APÓS ACEITAÇÃO
                ====================================== -->

                <?php if ($mostrar_telefone): ?>

                    <div
                        class="alert mt-3 mb-0"
                        style="background:#E7F5F1;color:#243438;">

                        <strong>📱 Contato:</strong>

                        <?php if (!empty($telefone_outra_pessoa)): ?>

                            <?= escapar_faxina(
                                $telefone_outra_pessoa
                            ); ?>

                            <?php if (
                                preg_match(
                                    '/^55\d{10,11}$/',
                                    $telefone_whatsapp
                                )
                            ): ?>

                                <a
                                    class="btn btn-sm btn-outline-success ms-2"
                                    href="https://wa.me/<?= escapar_faxina($telefone_whatsapp); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer">

                                    Conversar no WhatsApp

                                </a>

                            <?php endif; ?>

                        <?php else: ?>

                            Telefone não cadastrado.

                        <?php endif; ?>

                        <div class="small mt-1">

                            Combinem os detalhes e o valor diretamente
                            entre vocês. O LimpaLar não recebe pagamentos.

                        </div>

                    </div>

                <?php elseif ($status === "SOLICITADA"): ?>

                    <p class="small text-secondary mt-3 mb-0">

                        O telefone será liberado quando a
                        solicitação for aceita.

                    </p>

                <?php endif; ?>

                <!-- =====================================
                     BOTÕES DE AÇÃO
                ====================================== -->

                <div
                    class="d-flex flex-wrap gap-2 mt-3 align-items-center">

                    <!-- ACEITAR E RECUSAR -->

                    <?php if (
                        !$cliente &&
                        $status === "SOLICITADA"
                    ): ?>

                        <?php
                        botao_acao_faxina(
                            $faxina["ID_CONTRATACAO"],
                            "aceitar",
                            "✓ Aceitar",
                            "btn-success",
                            $token,
                            "recebi"
                        );
                        ?>

                        <?php
                        botao_acao_faxina(
                            $faxina["ID_CONTRATACAO"],
                            "recusar",
                            "✕ Recusar",
                            "btn-outline-danger",
                            $token,
                            "recebi"
                        );
                        ?>

                    <?php endif; ?>

                    <!-- CANCELAR -->

                    <?php if (
                        in_array(
                            $status,
                            ["SOLICITADA", "ACEITA"],
                            true
                        )
                    ): ?>

                        <?php
                        botao_acao_faxina(
                            $faxina["ID_CONTRATACAO"],
                            "cancelar",
                            "Cancelar",
                            "btn-outline-secondary",
                            $token,
                            $cliente ? "contratei" : "recebi"
                        );
                        ?>

                    <?php endif; ?>

                    <!-- =================================
                         BOTÃO FAXINA FINALIZADA
                    ================================== -->

                    <?php if ($pode_concluir): ?>

                        <button
                            type="button"
                            class="btn btn-sm btn-success fw-semibold"
                            data-bs-toggle="modal"
                            data-bs-target="#modalFinalizarFaxina"
                            data-id-contratacao="<?= (int) $faxina["ID_CONTRATACAO"]; ?>">

                            ✅ Faxina finalizada

                        </button>

                    <?php endif; ?>

                    <!-- AVALIAÇÃO DO CLIENTE -->

                    <?php if (
                        $cliente &&
                        $status === "CONCLUIDA" &&
                        !(int) $faxina["JA_AVALIOU"]
                    ): ?>

                        <a
                            href="avaliacoes.php"
                            class="btn btn-sm btn-outline-warning">

                            ★ Avaliar profissional

                        </a>

                    <?php elseif (
                        $cliente &&
                        $status === "CONCLUIDA"
                    ): ?>

                        <span class="badge text-bg-light border">

                            ★ Você já avaliou esta faxina

                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </article>

        <?php
    }
}

// =====================================================
// PREPARA A ABA QUE SERÁ EXIBIDA
// =====================================================

$aba_inicial = ($_GET["aba"] ?? "") === "recebi"
    ? "recebi"
    : "contratei";

$mensagem_faxinas =
    $_SESSION["mensagem_faxinas"] ?? "";

$tipo_mensagem_faxinas =
    $_SESSION["tipo_mensagem_faxinas"] ?? "";

unset(
    $_SESSION["mensagem_faxinas"],
    $_SESSION["tipo_mensagem_faxinas"]
);

include __DIR__ . "/cabecalho.php";

?>

<!-- =====================================================
     TELA MINHAS FAXINAS
====================================================== -->

<section class="container py-5">

    <div class="text-center mb-4">

        <h1
            class="fw-bold"
            style="color:#126E68;">

            🧹 Minhas faxinas

        </h1>

        <p style="color:#617477;">

            Acompanhe solicitações, aceite serviços e
            combine os detalhes diretamente com a outra pessoa.

        </p>

    </div>

    <!-- MENSAGENS -->

    <?php if ($mensagem_faxinas !== ""): ?>

        <div
            class="alert alert-<?= escapar_faxina($tipo_mensagem_faxinas); ?> alert-dismissible fade show"
            role="alert">

            <?= escapar_faxina($mensagem_faxinas); ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Fechar">
            </button>

        </div>

    <?php endif; ?>

    <?php if ($erro_consulta !== ""): ?>

        <div class="alert alert-danger" role="alert">

            <?= escapar_faxina($erro_consulta); ?>

        </div>

    <?php else: ?>

        <!-- =========================================
             ABAS DAS FAXINAS
        ========================================== -->

        <ul
            class="nav nav-pills justify-content-center gap-2 mb-4"
            id="abasFaxinas"
            role="tablist">

            <li class="nav-item" role="presentation">

                <button
                    class="nav-link <?= $aba_inicial === 'contratei' ? 'active' : ''; ?>"
                    id="aba-contratei"
                    data-bs-toggle="pill"
                    data-bs-target="#faxinas-contratei"
                    type="button"
                    role="tab"
                    aria-controls="faxinas-contratei"
                    aria-selected="<?= $aba_inicial === 'contratei' ? 'true' : 'false'; ?>">

                    Faxinas que contratei

                    <span class="badge text-bg-light ms-1">

                        <?= count($faxinas_contratadas); ?>

                    </span>

                </button>

            </li>

            <li class="nav-item" role="presentation">

                <button
                    class="nav-link <?= $aba_inicial === 'recebi' ? 'active' : ''; ?>"
                    id="aba-recebi"
                    data-bs-toggle="pill"
                    data-bs-target="#faxinas-recebi"
                    type="button"
                    role="tab"
                    aria-controls="faxinas-recebi"
                    aria-selected="<?= $aba_inicial === 'recebi' ? 'true' : 'false'; ?>">

                    Faxinas que vou realizar

                    <span class="badge text-bg-light ms-1">

                        <?= count($faxinas_recebidas); ?>

                    </span>

                </button>

            </li>

        </ul>

        <!-- ESTILO DAS ABAS -->

        <style>

            #abasFaxinas .nav-link {
                color: #126E68;
                border: 1px solid #CFE6DF;
            }

            #abasFaxinas .nav-link.active {
                background: #18A999;
                border-color: #18A999;
                color: white;
            }

        </style>

        <div
            class="tab-content"
            id="conteudoFaxinas">

            <!-- FAXINAS CONTRATADAS -->

            <div
                class="tab-pane fade <?= $aba_inicial === 'contratei' ? 'show active' : ''; ?>"
                id="faxinas-contratei"
                role="tabpanel"
                aria-labelledby="aba-contratei"
                tabindex="0">

                <?php
                mostrar_faxinas(
                    $faxinas_contratadas,
                    "cliente",
                    $token_faxinas
                );
                ?>

            </div>

            <!-- FAXINAS RECEBIDAS -->

            <div
                class="tab-pane fade <?= $aba_inicial === 'recebi' ? 'show active' : ''; ?>"
                id="faxinas-recebi"
                role="tabpanel"
                aria-labelledby="aba-recebi"
                tabindex="0">

                <?php
                mostrar_faxinas(
                    $faxinas_recebidas,
                    "profissional",
                    $token_faxinas
                );
                ?>

            </div>

        </div>

    <?php endif; ?>

</section>

<!-- =====================================================
     MODAL: CONFIRMAR FAXINA FINALIZADA
====================================================== -->

<div
    class="modal fade"
    id="modalFinalizarFaxina"
    tabindex="-1"
    aria-labelledby="tituloFinalizarFaxina"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div
            class="modal-content border-0 shadow rounded-4">

            <div class="modal-header border-0 pb-0">

                <h2
                    class="modal-title h5 fw-bold"
                    id="tituloFinalizarFaxina"
                    style="color:#126E68;">

                    ✅ Confirmar finalização

                </h2>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar">
                </button>

            </div>

            <div class="modal-body pt-3">

                <p class="mb-2 fw-semibold">

                    Você tem certeza de que realmente
                    finalizou esta faxina?

                </p>

                <p class="small text-secondary mb-0">

                    Ao confirmar, o serviço será encerrado e
                    o contratante poderá avaliar seu atendimento
                    de 1 a 5 estrelas.

                    Essa ação não pode ser desfeita.

                </p>

            </div>

            <div class="modal-footer border-0 pt-0">

                <!-- NÃO FINALIZAR -->

                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    data-bs-dismiss="modal">

                    Não, voltar

                </button>

                <!-- CONFIRMAR FINALIZAÇÃO -->

                <form
                    method="POST"
                    action="concluir_faxina.php"
                    class="m-0">

                    <input
                        type="hidden"
                        name="token_faxinas"
                        value="<?= escapar_faxina($token_faxinas); ?>">

                    <input
                        type="hidden"
                        name="id_contratacao"
                        id="idContratacaoFinalizar"
                        value="">

                    <input
                        type="hidden"
                        name="acao"
                        value="concluir">

                    <input
                        type="hidden"
                        name="aba_faxinas"
                        value="recebi">

                    <button
                        type="submit"
                        class="btn btn-success fw-semibold">

                        Sim, finalizar faxina

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<!-- =====================================================
     JAVASCRIPT DO MODAL DE FINALIZAÇÃO
====================================================== -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const modal = document.getElementById(
            'modalFinalizarFaxina'
        );

        const campoId = document.getElementById(
            'idContratacaoFinalizar'
        );

        if (!modal || !campoId) {
            return;
        }

        modal.addEventListener(
            'show.bs.modal',
            function (evento) {

                const botao = evento.relatedTarget;

                campoId.value = botao
                    ? (
                        botao.getAttribute(
                            'data-id-contratacao'
                        ) || ''
                    )
                    : '';

            }
        );

    }
);

</script>

</main>

<?php include __DIR__ . "/rodape.php"; ?>
