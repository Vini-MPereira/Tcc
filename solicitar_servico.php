
<?php
// =====================================================
// LIMPA LAR - RECEBER SOLICITAÇÃO DE FAXINA
// =====================================================
// Recebe os dados do modal de profissionais.php
// e cadastra a solicitação no banco de dados.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

date_default_timezone_set("America/Sao_Paulo");

// =====================================================
// FUNÇÃO PARA RESPONDER AO JAVASCRIPT
// =====================================================

function responder_solicitacao($sucesso, $mensagem, $codigo = 200)
{
    http_response_code($codigo);

    echo json_encode([
        "sucesso" => (bool)$sucesso,
        "mensagem" => $mensagem
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// =====================================================
// VERIFICA O MÉTODO E O LOGIN
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    responder_solicitacao(false, "Método não permitido.", 405);
}

if (empty($_SESSION["ID_USUARIO"])) {
    responder_solicitacao(
        false,
        "Entre na sua conta para solicitar uma faxina.",
        401
    );
}

if (strpos(
    strtolower($_SERVER["CONTENT_TYPE"] ?? ""),
    "application/json"
) === false) {
    responder_solicitacao(false, "Envie os dados em JSON.", 415);
}

// =====================================================
// RECEBE OS DADOS DA SOLICITAÇÃO
// =====================================================

$dados = json_decode(
    file_get_contents("php://input"),
    true
);

if (!is_array($dados)) {
    responder_solicitacao(
        false,
        "Dados da solicitação inválidos.",
        400
    );
}

// Nunca recebe o ID do cliente pelo formulário.
$id_usuario = filter_var(
    $_SESSION["ID_USUARIO"],
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

$id_anuncio = filter_var(
    $dados["id_anuncio"] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if (!$id_usuario || !$id_anuncio) {
    responder_solicitacao(
        false,
        "Usuário ou anúncio inválido.",
        400
    );
}

// =====================================================
// FUNÇÃO PARA RECEBER CAMPOS DE TEXTO
// =====================================================

function texto_solicitacao($valor)
{
    return is_string($valor) ? trim($valor) : "";
}

$data_servico = texto_solicitacao(
    $dados["data_servico"] ?? ""
);

$hora_servico = texto_solicitacao(
    $dados["hora_servico"] ?? ""
);

$endereco_servico = texto_solicitacao(
    $dados["endereco_servico"] ?? ""
);

$numero_servico = texto_solicitacao(
    $dados["numero_servico"] ?? ""
);

$bairro_servico = texto_solicitacao(
    $dados["bairro_servico"] ?? ""
);

$observacao = texto_solicitacao(
    $dados["observacao"] ?? ""
);

// =====================================================
// VALIDA ENDEREÇO E OBSERVAÇÕES
// =====================================================

if ($endereco_servico === "" || $numero_servico === "") {
    responder_solicitacao(
        false,
        "Informe o endereço e o número da faxina.",
        422
    );
}

if (
    strlen($endereco_servico) > 255 ||
    strlen($numero_servico) > 20 ||
    strlen($bairro_servico) > 100 ||
    strlen($observacao) > 1000
) {
    responder_solicitacao(
        false,
        "Algum campo ultrapassou o tamanho permitido.",
        422
    );
}

// =====================================================
// VALIDA DATA E HORÁRIO
// =====================================================

$fuso = new DateTimeZone("America/Sao_Paulo");
$agora = new DateTimeImmutable("now", $fuso);

$data_objeto = DateTimeImmutable::createFromFormat(
    "!Y-m-d",
    $data_servico,
    $fuso
);

if (
    !$data_objeto ||
    $data_objeto->format("Y-m-d") !== $data_servico
) {
    responder_solicitacao(
        false,
        "Escolha uma data válida.",
        422
    );
}

if ($data_servico < $agora->format("Y-m-d")) {
    responder_solicitacao(
        false,
        "Escolha uma data de hoje em diante.",
        422
    );
}

if (!preg_match(
    '/^([01][0-9]|2[0-3]):[0-5][0-9]$/',
    $hora_servico
)) {
    responder_solicitacao(
        false,
        "Escolha um horário válido.",
        422
    );
}

$inicio_solicitado = DateTimeImmutable::createFromFormat(
    "!Y-m-d H:i",
    $data_servico . " " . $hora_servico,
    $fuso
);

if (!$inicio_solicitado || $inicio_solicitado <= $agora) {
    responder_solicitacao(
        false,
        "Esse horário já passou.",
        422
    );
}

// =====================================================
// IDENTIFICA O DIA DA SEMANA
// =====================================================

$dias_semana = [
    1 => "SEGUNDA",
    2 => "TERCA",
    3 => "QUARTA",
    4 => "QUINTA",
    5 => "SEXTA",
    6 => "SABADO",
    7 => "DOMINGO"
];

$dia_semana = $dias_semana[
    (int)$data_objeto->format("N")
];

$partes_hora = explode(":", $hora_servico);

$minutos_solicitados =
    (int)$partes_hora[0] * 60 +
    (int)$partes_hora[1];

// =====================================================
// CONEXÃO E CONSULTAS AO BANCO
// =====================================================

$transacao_aberta = false;

try {

    require_once __DIR__ . "/conexao.php";


    // =====================================================
    // EXIGE AVALIAÇÃO DAS FAXINAS CONCLUÍDAS
    // =====================================================

    $sqlPendencias = "
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

    $stmtPendencias = mysqli_prepare(
        $conexao,
        $sqlPendencias
    );

    mysqli_stmt_bind_param(
        $stmtPendencias,
        "i",
        $id_usuario
    );

    mysqli_stmt_execute($stmtPendencias);

    $pendencia = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmtPendencias)
    );

    mysqli_stmt_close($stmtPendencias);

    if ($pendencia) {
        responder_solicitacao(
            false,
            "Você tem uma faxina concluída aguardando avaliação. Acesse Minhas avaliações e dê sua nota de 1 a 5 estrelas antes de solicitar outro serviço.",
            409
        );
    }


    // =================================================
    // BUSCA O ANÚNCIO ATIVO
    // =================================================

    $sql = "
        SELECT ID_USUARIO
        FROM anuncio
        WHERE ID_ANUNCIO = ?
          AND STATUS = 'ATIVO'
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_anuncio
    );

    mysqli_stmt_execute($stmt);

    $anuncio = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (!$anuncio) {
        responder_solicitacao(
            false,
            "Esse anúncio não está disponível.",
            404
        );
    }

    // O profissional é descoberto pelo anúncio.
    $id_profissional = (int)$anuncio["ID_USUARIO"];

    if ($id_profissional === (int)$id_usuario) {
        responder_solicitacao(
            false,
            "Você não pode contratar seu próprio anúncio.",
            403
        );
    }

    // =================================================
    // INICIA TRANSAÇÃO
    // =================================================

    mysqli_begin_transaction($conexao);
    $transacao_aberta = true;

    // Trava o profissional enquanto verifica a agenda.
    $sql = "
        SELECT ID_USUARIO
        FROM usuario
        WHERE ID_USUARIO = ?
          AND ATIVO = 1
        FOR UPDATE
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id_profissional
    );

    mysqli_stmt_execute($stmt);

    $profissional = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (!$profissional) {
        throw new DomainException(
            "Profissional indisponível.",
            404
        );
    }

    // Confere novamente se o anúncio continua ativo.
    $sql = "
        SELECT ID_USUARIO
        FROM anuncio
        WHERE ID_ANUNCIO = ?
          AND STATUS = 'ATIVO'
        FOR UPDATE
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id_anuncio);
    mysqli_stmt_execute($stmt);

    $anuncio_atual = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (
        !$anuncio_atual ||
        (int)$anuncio_atual["ID_USUARIO"] !== $id_profissional
    ) {
        throw new DomainException(
            "Esse anúncio não está mais ativo.",
            409
        );
    }

    // Verifica se a conta do cliente está ativa.
    $sql = "
        SELECT ID_USUARIO
        FROM usuario
        WHERE ID_USUARIO = ?
          AND ATIVO = 1
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);

    $cliente = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (!$cliente) {
        throw new DomainException(
            "Sua conta não está ativa.",
            403
        );
    }

    // =================================================
    // VERIFICA A DISPONIBILIDADE
    // =================================================

    $sql = "
        SELECT HORA_INICIO, HORA_FIM
        FROM disponibilidade
        WHERE ID_USUARIO = ?
          AND DIA_SEMANA = ?
          AND ATIVO = 1
        FOR UPDATE
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "is",
        $id_profissional,
        $dia_semana
    );

    mysqli_stmt_execute($stmt);

    $resultado_agenda = mysqli_stmt_get_result($stmt);

    $horario_permitido = false;

    while ($faixa = mysqli_fetch_assoc($resultado_agenda)) {

        $inicio = explode(
            ":",
            substr($faixa["HORA_INICIO"], 0, 5)
        );

        $fim = explode(
            ":",
            substr($faixa["HORA_FIM"], 0, 5)
        );

        $minutos_inicio =
            (int)$inicio[0] * 60 + (int)$inicio[1];

        $minutos_fim =
            (int)$fim[0] * 60 + (int)$fim[1];

        // Horários de hora em hora.
        if (
            $minutos_solicitados >= $minutos_inicio &&
            $minutos_solicitados + 60 <= $minutos_fim &&
            ($minutos_solicitados - $minutos_inicio) % 60 === 0
        ) {
            $horario_permitido = true;
            break;
        }
    }

    mysqli_stmt_close($stmt);

    if (!$horario_permitido) {
        throw new DomainException(
            "O profissional não atende nesse dia e horário.",
            422
        );
    }

    // =================================================
    // VERIFICA SE O HORÁRIO JÁ FOI ACEITO
    // =================================================

    $sql = "
        SELECT ID_CONTRATACAO
        FROM contratacao
        WHERE ID_PROFISSIONAL = ?
          AND DATA_SERVICO = ?
          AND HORA_SERVICO = ?
          AND STATUS = 'ACEITA'
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iss",
        $id_profissional,
        $data_servico,
        $hora_servico
    );

    mysqli_stmt_execute($stmt);

    $ocupado = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if ($ocupado) {
        throw new DomainException(
            "Esse horário já foi aceito pelo profissional.",
            409
        );
    }

    // =================================================
    // IMPEDE SOLICITAÇÃO DUPLICADA DO MESMO CLIENTE
    // =================================================

    $sql = "
        SELECT ID_CONTRATACAO
        FROM contratacao
        WHERE ID_PROFISSIONAL = ?
          AND ID_CONTRATANTE = ?
          AND DATA_SERVICO = ?
          AND HORA_SERVICO = ?
          AND STATUS IN ('SOLICITADA', 'ACEITA')
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iiss",
        $id_profissional,
        $id_usuario,
        $data_servico,
        $hora_servico
    );

    mysqli_stmt_execute($stmt);

    $duplicada = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if ($duplicada) {
        throw new DomainException(
            "Você já solicitou esse horário.",
            409
        );
    }

    // =================================================
    // CADASTRA A SOLICITAÇÃO
    // =================================================
    // O valor 0.00 é apenas compatibilidade com o
    // banco antigo, que exige VALOR NOT NULL.
    // O preço real é combinado fora do LimpaLar.

    $sql = "
        INSERT INTO contratacao (
            ID_ANUNCIO,
            ID_CONTRATANTE,
            ID_PROFISSIONAL,
            DATA_SERVICO,
            HORA_SERVICO,
            ENDERECO_SERVICO,
            NUMERO_SERVICO,
            BAIRRO_SERVICO,
            CIDADE_SERVICO,
            VALOR,
            STATUS,
            OBSERVACAO
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?,
            'Bauru',
            0.00,
            'SOLICITADA',
            ?
        )
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iiissssss",
        $id_anuncio,
        $id_usuario,
        $id_profissional,
        $data_servico,
        $hora_servico,
        $endereco_servico,
        $numero_servico,
        $bairro_servico,
        $observacao
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    // =================================================
    // CONFIRMA O CADASTRO
    // =================================================

    mysqli_commit($conexao);
    $transacao_aberta = false;

    responder_solicitacao(
        true,
        "Solicitação enviada! Aguarde o profissional aceitar ou recusar. Acompanhe em Minhas faxinas."
    );

} catch (Throwable $erro) {

    if ($transacao_aberta && isset($conexao)) {
        mysqli_rollback($conexao);
    }

    if ($erro instanceof DomainException) {
        responder_solicitacao(
            false,
            $erro->getMessage(),
            $erro->getCode()
        );
    }

    error_log(
        "LimpaLar - solicitar_servico: " .
        $erro->getMessage()
    );

    responder_solicitacao(
        false,
        "Não foi possível salvar sua solicitação. Confira o banco e tente novamente.",
        500
    );
}
