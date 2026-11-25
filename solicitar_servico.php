
<?php
// LIMPA LAR - SOLICITAÇÃO POR PERÍODOS

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

date_default_timezone_set('America/Sao_Paulo');

// RESPOSTA JSON

function responder_periodo(
    bool $sucesso,
    string $mensagem,
    int $codigo = 200
): void {

    if (ob_get_level() > 0) {
        ob_clean();
    }

    http_response_code($codigo);

    echo json_encode([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// VALIDAÇÃO DA REQUISIÇÃO

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_periodo(false, 'Método não permitido.', 405);
}

if (empty($_SESSION['ID_USUARIO'])) {
    responder_periodo(false, 'Faça login para continuar.', 401);
}

if (
    stripos(
        $_SERVER['CONTENT_TYPE'] ?? '',
        'application/json'
    ) === false
) {
    responder_periodo(false, 'Envie os dados em JSON.', 415);
}

$entrada = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($entrada)) {
    responder_periodo(false, 'Dados inválidos.', 400);
}

// DADOS RECEBIDOS

$id_cliente = (int)$_SESSION['ID_USUARIO'];

$id_anuncio = filter_var(
    $entrada['id_anuncio'] ?? null,
    FILTER_VALIDATE_INT
);

$data = (string)($entrada['data_servico'] ?? '');

$periodo = (string)(
    $entrada['periodo_servico'] ?? ''
);

$endereco = trim((string)(
    $entrada['endereco_servico'] ?? ''
));

$numero = trim((string)(
    $entrada['numero_servico'] ?? ''
));

$bairro = trim((string)(
    $entrada['bairro_servico'] ?? ''
));

$observacao = trim((string)(
    $entrada['observacao'] ?? ''
));

// PERÍODOS FIXOS

$periodos = [
    'MANHA' => ['06:00', '12:00'],
    'TARDE' => ['13:00', '17:00']
];

if (!$id_anuncio || !isset($periodos[$periodo])) {
    responder_periodo(
        false,
        'Selecione um anúncio e um período.',
        422
    );
}

if ($endereco === '' || $numero === '') {
    responder_periodo(
        false,
        'Informe o endereço e o número.',
        422
    );
}

if (
    strlen($endereco) > 255 ||
    strlen($numero) > 20 ||
    strlen($bairro) > 100 ||
    strlen($observacao) > 1000
) {
    responder_periodo(
        false,
        'Algum campo é muito longo.',
        422
    );
}

// VALIDAR DATA

$fuso = new DateTimeZone('America/Sao_Paulo');

$dia = DateTimeImmutable::createFromFormat(
    '!Y-m-d',
    $data,
    $fuso
);

if (!$dia || $dia->format('Y-m-d') !== $data) {
    responder_periodo(
        false,
        'Escolha uma data válida.',
        422
    );
}

[$inicio, $fim] = $periodos[$periodo];

$inicioReal = DateTimeImmutable::createFromFormat(
    '!Y-m-d H:i',
    "$data $inicio",
    $fuso
);

if (
    !$inicioReal ||
    $inicioReal <= new DateTimeImmutable('now', $fuso)
) {
    responder_periodo(
        false,
        'Esse período já começou ou passou.',
        422
    );
}

// CONEXÃO E VERIFICAÇÕES

$transacaoAberta = false;

try {

    require_once __DIR__ . '/conexao.php';

    // EXIGIR AVALIAÇÃO PENDENTE

    $sql = "
        SELECT C.ID_CONTRATACAO
        FROM CONTRATACAO C
        WHERE C.ID_CONTRATANTE = ?
          AND C.STATUS = 'CONCLUIDA'
          AND NOT EXISTS (
              SELECT 1
              FROM AVALIACAO V
              WHERE V.ID_CONTRATACAO = C.ID_CONTRATACAO
          )
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $id_cliente
    );

    mysqli_stmt_execute($stmt);

    $pendente = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if ($pendente) {
        responder_periodo(
            false,
            'Avalie sua faxina concluída antes de solicitar outra.',
            409
        );
    }

    // BUSCAR ANÚNCIO ATIVO

    $sql = "
        SELECT A.ID_USUARIO
        FROM ANUNCIO A
        INNER JOIN USUARIO U
            ON U.ID_USUARIO = A.ID_USUARIO
        WHERE A.ID_ANUNCIO = ?
          AND A.STATUS = 'ATIVO'
          AND U.ATIVO = 1
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $id_anuncio
    );

    mysqli_stmt_execute($stmt);

    $anuncio = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (!$anuncio) {
        responder_periodo(
            false,
            'Anúncio indisponível.',
            404
        );
    }

    $id_profissional = (int)$anuncio['ID_USUARIO'];

    if ($id_profissional === $id_cliente) {
        responder_periodo(
            false,
            'Você não pode contratar seu próprio anúncio.',
            403
        );
    }

    // INICIAR TRANSAÇÃO

    mysqli_begin_transaction($conexao);
    $transacaoAberta = true;

    // TRAVAR PROFISSIONAL

    $sql = "
        SELECT ID_USUARIO
        FROM USUARIO
        WHERE ID_USUARIO = ?
          AND ATIVO = 1
        FOR UPDATE
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $id_profissional
    );

    mysqli_stmt_execute($stmt);

    $ativo = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (!$ativo) {
        throw new DomainException(
            'Profissional indisponível.'
        );
    }

    // IMPEDIR DUAS RESERVAS NO MESMO PERÍODO

    $sql = "
        SELECT ID_CONTRATACAO
        FROM CONTRATACAO
        WHERE ID_PROFISSIONAL = ?
          AND DATA_SERVICO = ?
          AND HORA_SERVICO >= ?
          AND HORA_SERVICO < ?
          AND STATUS IN (
              'SOLICITADA',
              'ACEITA'
          )
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        'isss',
        $id_profissional,
        $data,
        $inicio,
        $fim
    );

    mysqli_stmt_execute($stmt);

    $ocupado = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if ($ocupado) {
        throw new DomainException(
            'Esse período já possui uma solicitação. Escolha outro.'
        );
    }

    // CADASTRAR SOLICITAÇÃO

    // O valor 0.00 é só compatibilidade com
    // bancos antigos. O preço será combinado.

    $sql = "
        INSERT INTO CONTRATACAO (
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
        'iiissssss',
        $id_anuncio,
        $id_cliente,
        $id_profissional,
        $data,
        $inicio,
        $endereco,
        $numero,
        $bairro,
        $observacao
    );

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    mysqli_commit($conexao);
    $transacaoAberta = false;

    responder_periodo(
        true,
        'Solicitação enviada! Aguarde a confirmação do profissional.'
    );

} catch (Throwable $e) {

    if ($transacaoAberta) {
        mysqli_rollback($conexao);
    }

    if ($e instanceof DomainException) {
        responder_periodo(
            false,
            $e->getMessage(),
            409
        );
    }

    error_log(
        'LimpaLar - solicitar período: ' .
        $e->getMessage()
    );

    responder_periodo(
        false,
        'Erro ao registrar. Verifique o banco de dados.',
        500
    );
}
