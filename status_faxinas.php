
<?php
// LIMPA LAR - VERIFICAR MUDANÇAS NAS FAXINAS

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (empty($_SESSION['ID_USUARIO'])) {

    http_response_code(401);

    echo json_encode([
        'erro' => 'Sessão encerrada.'
    ]);

    exit;
}

try {

    require_once __DIR__ . '/conexao.php';

    $usuario = (int) $_SESSION['ID_USUARIO'];

    $stmt = mysqli_prepare(
        $conexao,
        "SELECT
            ID_CONTRATACAO,
            STATUS,
            DATA_SERVICO,
            HORA_SERVICO
         FROM CONTRATACAO
         WHERE ID_CONTRATANTE = ?
            OR ID_PROFISSIONAL = ?
         ORDER BY ID_CONTRATACAO"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'ii',
        $usuario,
        $usuario
    );

    mysqli_stmt_execute($stmt);

    $registros = mysqli_fetch_all(
        mysqli_stmt_get_result($stmt),
        MYSQLI_ASSOC
    );

    mysqli_stmt_close($stmt);

    echo json_encode(
        [
            'versao' => hash(
                'sha256',
                json_encode($registros)
            )
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $erro) {

    error_log(
        'LimpaLar - status_faxinas: ' .
        $erro->getMessage()
    );

    http_response_code(503);

    echo json_encode([
        'erro' => 'Não foi possível consultar as faxinas.'
    ]);
}
    