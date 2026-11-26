
<?php
// =====================================================
// LIMPA LAR - SOLICITAR SERVIÇO
// Manhã: 06h–12h | Tarde: 13h–17h
// Limite para solicitar para hoje: 14h.
// Solicitações canceladas ou recusadas liberam a vaga.
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function resposta_servico(
    bool $sucesso,
    string $mensagem,
    int $codigo = 200
): void {
    http_response_code($codigo);

    echo json_encode(
        [
            'sucesso' => $sucesso,
            'mensagem' => $mensagem
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

function texto_servico($valor): string
{
    return is_string($valor) ? trim($valor) : '';
}

// =====================================================
// VERIFICAÇÃO DE LOGIN
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    resposta_servico(false, 'Método não permitido.', 405);
}

$id_cliente = filter_var(
    $_SESSION['ID_USUARIO'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$id_cliente) {
    resposta_servico(
        false,
        'Entre na sua conta para solicitar uma faxina.',
        401
    );
}

if (
    stripos(
        $_SERVER['CONTENT_TYPE'] ?? '',
        'application/json'
    ) === false
) {
    resposta_servico(false, 'Formato de envio inválido.', 415);
}

$dados = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($dados)) {
    resposta_servico(false, 'Dados inválidos.', 400);
}

// =====================================================
// RECEBER DADOS DO FORMULÁRIO
// =====================================================

$id_anuncio = filter_var(
    $dados['id_anuncio'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$id_anuncio) {
    resposta_servico(false, 'Anúncio inválido.', 400);
}

$data_servico = texto_servico(
    $dados['data_servico'] ?? ''
);

$periodo = strtoupper(
    texto_servico($dados['periodo_servico'] ?? '')
);

// Compatibilidade com a versão que envia hora_servico.
if ($periodo === '') {

    $hora_recebida = texto_servico(
        $dados['hora_servico'] ?? ''
    );

    if (
        in_array(
            $hora_recebida,
            ['06:00', '06:00:00'],
            true
        )
    ) {
        $periodo = 'MANHA';

    } elseif (
        in_array(
            $hora_recebida,
            ['13:00', '13:00:00'],
            true
        )
    ) {
        $periodo = 'TARDE';
    }
}

if (!in_array($periodo, ['MANHA', 'TARDE'], true)) {
    resposta_servico(false, 'Escolha Manhã ou Tarde.', 422);
}

$hora_servico = $periodo === 'MANHA'
    ? '06:00:00'
    : '13:00:00';

$inicio_periodo = $hora_servico;

$fim_periodo = $periodo === 'MANHA'
    ? '12:00:00'
    : '17:00:00';

$nome_periodo = $periodo === 'MANHA'
    ? 'manhã'
    : 'tarde';

// =====================================================
// ENDEREÇO
// =====================================================

$endereco = texto_servico(
    $dados['endereco_servico'] ?? ''
);

$numero = texto_servico(
    $dados['numero_servico'] ?? ''
);

$bairro = texto_servico(
    $dados['bairro_servico'] ?? ''
);

$observacao = texto_servico(
    $dados['observacao'] ?? ''
);

if ($endereco === '' || $numero === '') {
    resposta_servico(
        false,
        'Informe o endereço e o número da faxina.',
        422
    );
}

if (
    strlen($endereco) > 255 ||
    strlen($numero) > 20 ||
    strlen($bairro) > 100 ||
    strlen($observacao) > 1000
) {
    resposta_servico(
        false,
        'Algum campo é maior que o permitido.',
        422
    );
}

// =====================================================
// VALIDAR DATA E LIMITE DAS 14H
// =====================================================

$fuso = new DateTimeZone('America/Sao_Paulo');
$agora = new DateTimeImmutable('now', $fuso);

$hoje = $agora->format('Y-m-d');
$hora_atual = $agora->format('H:i:s');

$data_objeto = DateTimeImmutable::createFromFormat(
    '!Y-m-d',
    $data_servico,
    $fuso
);

if (
    !$data_objeto ||
    $data_objeto->format('Y-m-d') !== $data_servico
) {
    resposta_servico(
        false,
        'Escolha uma data válida.',
        422
    );
}

if ($data_servico < $hoje) {
    resposta_servico(
        false,
        'Não é possível agendar em datas passadas.',
        422
    );
}

// Para HOJE, não permite novos pedidos após as 14h.
if ($data_servico === $hoje) {

    if ($hora_atual >= '14:00:00') {

        resposta_servico(
            false,
            'Após as 14h não é possível solicitar faxina para hoje. Escolha outra data.',
            422
        );
    }

    // A manhã já começou às 06h.
    if (
        $periodo === 'MANHA' &&
        $hora_atual >= '06:00:00'
    ) {

        resposta_servico(
            false,
            'O período da manhã já começou. Escolha a tarde ou uma data futura.',
            422
        );
    }

    // A tarde pode receber pedido até 13h59,
    // conforme o limite solicitado.
}

// =====================================================
// CONSULTAS E TRANSAÇÃO
// =====================================================

$transacao = false;

try {

    mysqli_report(
        MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT
    );

    require_once __DIR__ . '/conexao.php';

    // Busca o profissional pelo anúncio.
    $stmt = mysqli_prepare(
        $conexao,
        "SELECT ID_USUARIO
         FROM ANUNCIO
         WHERE ID_ANUNCIO = ?
           AND STATUS = 'ATIVO'
         LIMIT 1"
    );

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
        throw new DomainException(
            'Anúncio indisponível.',
            404
        );
    }

    $id_profissional = (int) $anuncio['ID_USUARIO'];

    if ($id_profissional === (int) $id_cliente) {
        throw new DomainException(
            'Você não pode contratar seu próprio anúncio.',
            403
        );
    }

    mysqli_begin_transaction($conexao);
    $transacao = true;

    // Trava as contas para evitar reservas simultâneas.
    $stmt = mysqli_prepare(
        $conexao,
        "SELECT ID_USUARIO, ATIVO
         FROM USUARIO
         WHERE ID_USUARIO IN (?, ?)
         ORDER BY ID_USUARIO
         FOR UPDATE"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'ii',
        $id_cliente,
        $id_profissional
    );

    mysqli_stmt_execute($stmt);

    $contas = mysqli_fetch_all(
        mysqli_stmt_get_result($stmt),
        MYSQLI_ASSOC
    );

    mysqli_stmt_close($stmt);

    if (count($contas) !== 2) {
        throw new DomainException(
            'Conta não encontrada.',
            404
        );
    }

    foreach ($contas as $conta) {

        if ((int) $conta['ATIVO'] !== 1) {
            throw new DomainException(
                'Uma das contas está desativada.',
                403
            );
        }
    }

    // Verifica novamente o anúncio.
    $stmt = mysqli_prepare(
        $conexao,
        "SELECT ID_USUARIO
         FROM ANUNCIO
         WHERE ID_ANUNCIO = ?
           AND STATUS = 'ATIVO'
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'i', $id_anuncio);
    mysqli_stmt_execute($stmt);

    $anuncio_atual = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if (
        !$anuncio_atual ||
        (int) $anuncio_atual['ID_USUARIO'] !== $id_profissional
    ) {
        throw new DomainException(
            'Anúncio não está mais disponível.',
            409
        );
    }

    // Impede novas solicitações com avaliação pendente.
    $stmt = mysqli_prepare(
        $conexao,
        "SELECT C.ID_CONTRATACAO
         FROM CONTRATACAO C
         WHERE C.ID_CONTRATANTE = ?
           AND C.STATUS = 'CONCLUIDA'
           AND NOT EXISTS (
               SELECT 1
               FROM AVALIACAO A
               WHERE A.ID_CONTRATACAO = C.ID_CONTRATACAO
           )
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'i', $id_cliente);
    mysqli_stmt_execute($stmt);

    $pendencia = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if ($pendencia) {
        throw new DomainException(
            'Você tem uma faxina concluída aguardando avaliação. Avalie antes de solicitar outra.',
            409
        );
    }

    // =================================================
    // VERIFICAR SE O PROFISSIONAL ESTÁ OCUPADO
    // =================================================
    // CANCELADA e RECUSADA ficam fora da consulta.
    // Portanto, liberam o período automaticamente.
    // =================================================

    $stmt = mysqli_prepare(
        $conexao,
        "SELECT ID_CONTRATACAO
         FROM CONTRATACAO
         WHERE ID_PROFISSIONAL = ?
           AND DATA_SERVICO = ?
           AND HORA_SERVICO >= ?
           AND HORA_SERVICO < ?
           AND STATUS IN ('SOLICITADA', 'ACEITA')
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'isss',
        $id_profissional,
        $data_servico,
        $inicio_periodo,
        $fim_periodo
    );

    mysqli_stmt_execute($stmt);

    $ocupado = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if ($ocupado) {
        throw new DomainException(
            "Esse profissional já possui uma solicitação para essa data de {$nome_periodo}. Escolha outro período ou dia.",
            409
        );
    }

    // Também impede o cliente de contratar
    // duas faxinas no mesmo dia e período.
    $stmt = mysqli_prepare(
        $conexao,
        "SELECT ID_CONTRATACAO
         FROM CONTRATACAO
         WHERE ID_CONTRATANTE = ?
           AND DATA_SERVICO = ?
           AND HORA_SERVICO >= ?
           AND HORA_SERVICO < ?
           AND STATUS IN ('SOLICITADA', 'ACEITA')
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'isss',
        $id_cliente,
        $data_servico,
        $inicio_periodo,
        $fim_periodo
    );

    mysqli_stmt_execute($stmt);

    $cliente_ocupado = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

    if ($cliente_ocupado) {
        throw new DomainException(
            'Você já tem uma faxina nesse dia e período.',
            409
        );
    }

    // =================================================
    // SALVAR SOLICITAÇÃO
    // =================================================

    $stmt = mysqli_prepare(
        $conexao,
        "INSERT INTO CONTRATACAO (
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
        )"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'iiissssss',
        $id_anuncio,
        $id_cliente,
        $id_profissional,
        $data_servico,
        $hora_servico,
        $endereco,
        $numero,
        $bairro,
        $observacao
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conexao);
    $transacao = false;

    resposta_servico(
        true,
        'Solicitação enviada! Aguarde o profissional aceitar ou recusar. Acompanhe em Minhas faxinas.'
    );

} catch (Throwable $erro) {

    if ($transacao && isset($conexao)) {
        mysqli_rollback($conexao);
    }

    if ($erro instanceof DomainException) {

        $codigo = (int) $erro->getCode();

        resposta_servico(
            false,
            $erro->getMessage(),
            $codigo >= 400 && $codigo < 500
                ? $codigo
                : 409
        );
    }

    error_log(
        'LimpaLar - solicitar_servico: ' .
        $erro->getMessage()
    );

    resposta_servico(
        false,
        'Não foi possível salvar a solicitação. Confira o banco.',
        500
    );
}
