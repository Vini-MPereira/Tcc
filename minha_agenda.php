
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['ID_USUARIO'])) {
    header('Location: entrar.php');
    exit;
}

require_once __DIR__ . '/conexao.php';

$id_usuario = (int) $_SESSION['ID_USUARIO'];

$dias = [
    'DOMINGO' => 'Domingo',
    'SEGUNDA' => 'Segunda-feira',
    'TERCA' => 'Terça-feira',
    'QUARTA' => 'Quarta-feira',
    'QUINTA' => 'Quinta-feira',
    'SEXTA' => 'Sexta-feira',
    'SABADO' => 'Sábado'
];

$periodos = [
    'MANHA' => [
        'inicio' => '06:00:00',
        'fim' => '12:00:00',
        'nome' => 'Manhã (06h às 12h)'
    ],
    'TARDE' => [
        'inicio' => '13:00:00',
        'fim' => '17:00:00',
        'nome' => 'Tarde (13h às 17h)'
    ]
];

function agenda_esc($valor) {
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function nome_periodo_agenda($hora) {
    $h = substr((string) $hora, 0, 5);

    if ($h >= '06:00' && $h < '12:00') {
        return 'Manhã (06h às 12h)';
    }

    if ($h >= '13:00' && $h < '17:00') {
        return 'Tarde (13h às 17h)';
    }

    return 'Horário antigo: ' . $h;
}

if (empty($_SESSION['token_agenda'])) {
    $_SESSION['token_agenda'] = bin2hex(random_bytes(32));
}

$erro = '';
$aviso = $_SESSION['aviso_agenda'] ?? '';
unset($_SESSION['aviso_agenda']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transacao = false;

    try {
        $token_recebido = (string) (
            $_POST['token_agenda'] ?? ''
        );

        if (!hash_equals(
            $_SESSION['token_agenda'],
            $token_recebido
        )) {
            throw new DomainException(
                'Formulário expirado. Atualize a página.'
            );
        }

        $acao = (string) ($_POST['acao'] ?? '');

        if ($acao === 'adicionar') {
            $dia = (string) ($_POST['dia_semana'] ?? '');
            $periodo = (string) ($_POST['periodo'] ?? '');

            if (
                !isset($dias[$dia]) ||
                !in_array(
                    $periodo,
                    ['MANHA', 'TARDE', 'AMBOS'],
                    true
                )
            ) {
                throw new DomainException(
                    'Escolha um dia e um período válidos.'
                );
            }

            $aCriar = $periodo === 'AMBOS'
                ? ['MANHA', 'TARDE']
                : [$periodo];

            mysqli_begin_transaction($conexao);
            $transacao = true;

            $stmt = mysqli_prepare(
                $conexao,
                "SELECT ID_USUARIO
                 FROM USUARIO
                 WHERE ID_USUARIO = ?
                   AND ATIVO = 1
                 FOR UPDATE"
            );

            mysqli_stmt_bind_param(
                $stmt,
                'i',
                $id_usuario
            );

            mysqli_stmt_execute($stmt);
            $existe = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );
            mysqli_stmt_close($stmt);

            if (!$existe) {
                throw new DomainException(
                    'Sua conta está indisponível.'
                );
            }

            foreach ($aCriar as $chave) {
                $inicio = $periodos[$chave]['inicio'];
                $fim = $periodos[$chave]['fim'];

                $stmt = mysqli_prepare(
                    $conexao,
                    "SELECT ID_DISPONIBILIDADE
                     FROM DISPONIBILIDADE
                     WHERE ID_USUARIO = ?
                       AND DIA_SEMANA = ?
                       AND ATIVO = 1
                       AND HORA_INICIO < ?
                       AND HORA_FIM > ?
                     LIMIT 1"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    'isss',
                    $id_usuario,
                    $dia,
                    $fim,
                    $inicio
                );

                mysqli_stmt_execute($stmt);
                $conflito = mysqli_fetch_assoc(
                    mysqli_stmt_get_result($stmt)
                );
                mysqli_stmt_close($stmt);

                if ($conflito) {
                    throw new DomainException(
                        'Você já possui disponibilidade nesse período. Remova o horário antigo antes.'
                    );
                }

                $stmt = mysqli_prepare(
                    $conexao,
                    "INSERT INTO DISPONIBILIDADE
                     (ID_USUARIO, DIA_SEMANA,
                      HORA_INICIO, HORA_FIM, ATIVO)
                     VALUES (?, ?, ?, ?, 1)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    'isss',
                    $id_usuario,
                    $dia,
                    $inicio,
                    $fim
                );

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            mysqli_commit($conexao);
            $transacao = false;

            $_SESSION['aviso_agenda'] =
                'Disponibilidade cadastrada!';

        } elseif ($acao === 'remover') {
            $id = filter_input(
                INPUT_POST,
                'id_disponibilidade',
                FILTER_VALIDATE_INT
            );

            if (!$id) {
                throw new DomainException(
                    'Disponibilidade inválida.'
                );
            }

            $stmt = mysqli_prepare(
                $conexao,
                "UPDATE DISPONIBILIDADE
                 SET ATIVO = 0
                 WHERE ID_DISPONIBILIDADE = ?
                   AND ID_USUARIO = ?
                   AND ATIVO = 1"
            );

            mysqli_stmt_bind_param(
                $stmt,
                'ii',
                $id,
                $id_usuario
            );

            mysqli_stmt_execute($stmt);
            $alterados = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($alterados !== 1) {
                throw new DomainException(
                    'Período não encontrado na sua agenda.'
                );
            }

            $_SESSION['aviso_agenda'] =
                'Disponibilidade removida.';
        } else {
            throw new DomainException('Ação inválida.');
        }

        header('Location: minha_agenda.php');
        exit;

    } catch (Throwable $e) {
        if ($transacao) {
            mysqli_rollback($conexao);
        }

        error_log('LimpaLar - agenda: ' . $e->getMessage());

        $erro = $e instanceof DomainException
            ? $e->getMessage()
            : 'Erro ao salvar a agenda. Verifique o banco de dados.';
    }
}

$horarios = [];
$solicitacoes = [];

try {
    $stmt = mysqli_prepare(
        $conexao,
        "SELECT ID_DISPONIBILIDADE,
                DIA_SEMANA, HORA_INICIO, HORA_FIM
         FROM DISPONIBILIDADE
         WHERE ID_USUARIO = ? AND ATIVO = 1
         ORDER BY
            FIELD(DIA_SEMANA,
              'DOMINGO', 'SEGUNDA', 'TERCA',
              'QUARTA', 'QUINTA', 'SEXTA', 'SABADO'),
            HORA_INICIO"
    );

    mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
    mysqli_stmt_execute($stmt);
    $horarios = mysqli_fetch_all(
        mysqli_stmt_get_result($stmt),
        MYSQLI_ASSOC
    );
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare(
        $conexao,
        "SELECT DATA_SERVICO, HORA_SERVICO, STATUS
         FROM CONTRATACAO
         WHERE ID_PROFISSIONAL = ?
           AND DATA_SERVICO >= CURDATE()
           AND STATUS IN ('SOLICITADA', 'ACEITA')
         ORDER BY DATA_SERVICO, HORA_SERVICO"
    );

    mysqli_stmt_bind_param($stmt, 'i', $id_usuario);
    mysqli_stmt_execute($stmt);
    $solicitacoes = mysqli_fetch_all(
        mysqli_stmt_get_result($stmt),
        MYSQLI_ASSOC
    );
    mysqli_stmt_close($stmt);

} catch (Throwable $e) {
    error_log(
        'LimpaLar - leitura da agenda: ' . $e->getMessage()
    );

    $erro =
        'Não foi possível consultar a agenda. Confira o banco de dados.';
}

include __DIR__ . '/cabecalho.php';
?>

<section class="container py-5">

    <h1 class="fw-bold text-center" style="color:#126E68">
        📅 Minha agenda
    </h1>

    <p class="text-secondary text-center mb-4">
        Escolha os períodos em que você atende.
    </p>

    <?php if ($aviso): ?>
        <div class="alert alert-success">
            <?= agenda_esc($aviso) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger">
            <?= agenda_esc($erro) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-lg-5">

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">

                    <h2 class="h5 fw-bold mb-3">
                        Cadastrar disponibilidade
                    </h2>

                    <form action="minha_agenda.php" method="POST">

                        <input
                            type="hidden"
                            name="token_agenda"
                            value="<?= agenda_esc($_SESSION['token_agenda']) ?>"
                        >

                        <input
                            type="hidden"
                            name="acao"
                            value="adicionar"
                        >

                        <label
                            for="dia_semana"
                            class="form-label"
                        >
                            Dia da semana
                        </label>

                        <select
                            id="dia_semana"
                            name="dia_semana"
                            class="form-select mb-3"
                            required
                        >
                            <option value="">Selecione um dia</option>

                            <?php foreach ($dias as $chave => $nome): ?>
                                <option value="<?= agenda_esc($chave) ?>">
                                    <?= agenda_esc($nome) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label
                            for="periodo"
                            class="form-label"
                        >
                            Período
                        </label>

                        <select
                            id="periodo"
                            name="periodo"
                            class="form-select mb-3"
                            required
                        >
                            <option value="">Selecione</option>
                            <option value="MANHA">
                                ☀️ Manhã — 06h às 12h
                            </option>
                            <option value="TARDE">
                                🌤️ Tarde — 13h às 17h
                            </option>
                            <option value="AMBOS">
                                Manhã e tarde
                            </option>
                        </select>

                        <button
                            type="submit"
                            class="btn text-white w-100"
                            style="background:#18A999"
                        >
                            Salvar disponibilidade
                        </button>

                    </form>

                </div>
            </div>

        </div>

        <div class="col-lg-7">

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">

                    <h2 class="h5 fw-bold mb-3">
                        Meus períodos
                    </h2>

                    <?php if (!$horarios): ?>
                        <p class="text-secondary">
                            Nenhum período cadastrado.
                        </p>
                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Dia</th>
                                        <th>Período</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>

                                <?php foreach ($horarios as $item): ?>
                                    <?php
                                    $ini = substr($item['HORA_INICIO'], 0, 5);
                                    $fim = substr($item['HORA_FIM'], 0, 5);

                                    if ($ini === '06:00' && $fim === '12:00') {
                                        $nomePeriodo = 'Manhã — 06h às 12h';
                                    } elseif ($ini === '13:00' && $fim === '17:00') {
                                        $nomePeriodo = 'Tarde — 13h às 17h';
                                    } else {
                                        $nomePeriodo =
                                            'Horário antigo: ' . $ini .
                                            ' às ' . $fim .
                                            ' (remova e recadastre)';
                                    }
                                    ?>

                                    <tr>
                                        <td>
                                            <?= agenda_esc(
                                                $dias[$item['DIA_SEMANA']]
                                                ?? $item['DIA_SEMANA']
                                            ) ?>
                                        </td>

                                        <td><?= agenda_esc($nomePeriodo) ?></td>

                                        <td>
                                            <form
                                                action="minha_agenda.php"
                                                method="POST"
                                                onsubmit="return confirm('Remover este período?')"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="token_agenda"
                                                    value="<?= agenda_esc($_SESSION['token_agenda']) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="acao"
                                                    value="remover"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id_disponibilidade"
                                                    value="<?= (int) $item['ID_DISPONIBILIDADE'] ?>"
                                                >

                                                <button
                                                    class="btn btn-sm btn-outline-danger"
                                                    type="submit"
                                                >
                                                    Remover
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </div>

    </div>

    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body p-4">

            <h2 class="h5 fw-bold">
                Próximas solicitações
            </h2>

            <?php if (!$solicitacoes): ?>
                <p class="text-secondary">
                    Nenhuma solicitação futura.
                </p>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Período</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php foreach ($solicitacoes as $item): ?>
                            <tr>
                                <td>
                                    <?= agenda_esc(
                                        date(
                                            'd/m/Y',
                                            strtotime($item['DATA_SERVICO'])
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= agenda_esc(
                                        nome_periodo_agenda(
                                            $item['HORA_SERVICO']
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= agenda_esc($item['STATUS']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

                <a
                    href="concluir_faxina.php?aba=recebi"
                    class="btn btn-outline-success"
                >
                    Ver Minhas faxinas
                </a>

            <?php endif; ?>

        </div>
    </div>

</section>

</main>
<?php include __DIR__ . '/rodape.php'; ?>
                            