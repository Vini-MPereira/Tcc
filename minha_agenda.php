
<?php
// =====================================================
// LIMPA LAR - AGENDA DO PROFISSIONAL
// Mostra manhã e tarde livres ou ocupadas.
// Cancelamentos não ocupam horários.
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['ID_USUARIO'])) {
    header('Location: entrar.php');
    exit;
}

require_once __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

$id_usuario = (int) $_SESSION['ID_USUARIO'];

$fuso = new DateTimeZone('America/Sao_Paulo');

$agora = new DateTimeImmutable('now', $fuso);

$hoje = $agora->format('Y-m-d');

$ultima_data = $agora
    ->modify('+13 days')
    ->format('Y-m-d');

$hora_atual = $agora->format('H:i:s');

function escapar_agenda($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function periodo_agenda($hora): ?string
{
    $hora = substr((string) $hora, 0, 8);

    if ($hora >= '06:00:00' && $hora < '12:00:00') {
        return 'MANHA';
    }

    if ($hora >= '13:00:00' && $hora < '17:00:00') {
        return 'TARDE';
    }

    return null;
}

$ocupados = [];
$cancelados = [];
$historico = [];

$erro_consulta = '';

// =====================================================
// CONSULTAR SOLICITAÇÕES
// =====================================================

try {

    $stmt = mysqli_prepare(
        $conexao,
        "SELECT
            C.ID_CONTRATACAO,
            C.DATA_SERVICO,
            C.HORA_SERVICO,
            C.STATUS,
            U.NOME AS CLIENTE
         FROM CONTRATACAO C
         INNER JOIN USUARIO U
            ON U.ID_USUARIO = C.ID_CONTRATANTE
         WHERE C.ID_PROFISSIONAL = ?
           AND C.DATA_SERVICO BETWEEN ? AND ?
         ORDER BY
            C.DATA_SERVICO,
            C.HORA_SERVICO,
            C.ID_CONTRATACAO"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'iss',
        $id_usuario,
        $hoje,
        $ultima_data
    );

    mysqli_stmt_execute($stmt);

    $registros = mysqli_fetch_all(
        mysqli_stmt_get_result($stmt),
        MYSQLI_ASSOC
    );

    mysqli_stmt_close($stmt);

    foreach ($registros as $registro) {

        $data = $registro['DATA_SERVICO'];

        $periodo = periodo_agenda(
            $registro['HORA_SERVICO']
        );

        if ($periodo === null) {
            continue;
        }

        // Pedidos pendentes ou aceitos ocupam a vaga.
        if (
            in_array(
                $registro['STATUS'],
                ['SOLICITADA', 'ACEITA'],
                true
            )
        ) {

            if (
                !isset($ocupados[$data][$periodo]) ||
                $registro['STATUS'] === 'ACEITA'
            ) {
                $ocupados[$data][$periodo] = $registro;
            }

        // Cancelados deixam o horário livre.
        } elseif ($registro['STATUS'] === 'CANCELADA') {

            $cancelados[$data][$periodo] = true;

            $historico[] = $registro;
        }
    }

} catch (Throwable $erro) {

    error_log(
        'LimpaLar - minha_agenda: ' .
        $erro->getMessage()
    );

    $erro_consulta =
        'Não foi possível carregar a agenda. Confira o MySQL.';
}

$dias_semana = [
    'Domingo',
    'Segunda-feira',
    'Terça-feira',
    'Quarta-feira',
    'Quinta-feira',
    'Sexta-feira',
    'Sábado'
];

include __DIR__ . '/cabecalho.php';
?>

<section class="container py-5">

    <div class="text-center mb-4">

        <h1
            class="fw-bold"
            style="color:#126E68"
        >
            📅 Minha agenda
        </h1>

        <p class="text-secondary mb-0">

            Acompanhe as solicitações de manhã
            e à tarde nos próximos 14 dias.

        </p>

    </div>

    <div
        class="alert border-0 rounded-4 mb-4"
        style="background:#E7F5F1;color:#126E68"
    >

        ✅ Quando um cliente cancela, o período
        deixa de ficar ocupado e pode receber
        uma nova solicitação, desde que ainda
        esteja dentro do prazo.

        <div class="small mt-2">

            Novas solicitações para hoje encerram
            às 14h. Para a manhã, o limite é
            o início do período, às 06h.

        </div>

    </div>

    <?php if ($erro_consulta !== ''): ?>

        <div class="alert alert-danger">

            <?= escapar_agenda($erro_consulta) ?>

        </div>

    <?php else: ?>

        <!-- CALENDÁRIO -->

        <div class="row g-3">

            <?php for ($i = 0; $i < 14; $i++): ?>

                <?php
                    $dia = $agora->modify(
                        '+' . $i . ' days'
                    );

                    $data = $dia->format('Y-m-d');
                ?>

                <div class="col-12 col-md-6 col-xl-4">

                    <div
                        class="card border-0 shadow-sm rounded-4 h-100"
                    >

                        <div class="card-body p-4">

                            <h2
                                class="h5 fw-bold mb-1"
                                style="color:#126E68"
                            >

                                <?= escapar_agenda(
                                    $dia->format('d/m/Y')
                                ) ?>

                            </h2>

                            <p class="small text-secondary mb-3">

                                <?= escapar_agenda(
                                    $dias_semana[
                                        (int) $dia->format('w')
                                    ]
                                ) ?>

                            </p>

                            <?php foreach (
                                [
                                    'MANHA' => '☀️ Manhã (06h–12h)',
                                    'TARDE' => '🌤️ Tarde (13h–17h)'
                                ] as $periodo => $rotulo
                            ): ?>

                                <?php

                                $reserva =
                                    $ocupados[$data][$periodo] ?? null;

                                $ja_cancelou = !empty(
                                    $cancelados[$data][$periodo]
                                );

                                $encerrado =
                                    $data === $hoje &&
                                    (
                                        $hora_atual >= '14:00:00' ||
                                        (
                                            $periodo === 'MANHA' &&
                                            $hora_atual >= '06:00:00'
                                        )
                                    );

                                ?>

                                <div
                                    class="border rounded-3 p-3 mb-2"
                                >

                                    <div class="fw-semibold mb-2">

                                        <?= escapar_agenda($rotulo) ?>

                                    </div>

                                    <?php if ($reserva): ?>

                                        <span
                                            class="badge <?= $reserva['STATUS'] === 'ACEITA'
                                                ? 'text-bg-success'
                                                : 'text-bg-warning' ?>"
                                        >

                                            <?= $reserva['STATUS'] === 'ACEITA'
                                                ? 'Faxina aceita'
                                                : 'Aguardando resposta' ?>

                                        </span>

                                        <div class="small text-secondary mt-2">

                                            Cliente:

                                            <?= escapar_agenda(
                                                $reserva['CLIENTE']
                                            ) ?>

                                        </div>

                                    <?php elseif ($encerrado): ?>

                                        <span class="badge text-bg-secondary">

                                            Encerrado para hoje

                                        </span>

                                        <?php if ($ja_cancelou): ?>

                                            <div class="small text-secondary mt-2">

                                                Houve cancelamento,
                                                mas o prazo para hoje
                                                já passou.

                                            </div>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        <span
                                            class="badge text-bg-light border"
                                            style="color:#126E68 !important"
                                        >

                                            <?= $ja_cancelou
                                                ? 'Livre novamente após cancelamento'
                                                : 'Livre para solicitação' ?>

                                        </span>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </div>

            <?php endfor; ?>

        </div>

        <!-- HISTÓRICO DE CANCELAMENTOS -->

        <div
            class="card border-0 shadow-sm rounded-4 mt-4"
        >

            <div class="card-body p-4">

                <h2
                    class="h5 fw-bold mb-3"
                    style="color:#126E68"
                >

                    Cancelamentos registrados

                </h2>

                <?php if (empty($historico)): ?>

                    <p class="text-secondary mb-0">

                        Nenhum cancelamento com data de
                        atendimento nos próximos 14 dias.

                    </p>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>
                                    <th>Cliente</th>
                                    <th>Data</th>
                                    <th>Período</th>
                                    <th>Situação</th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($historico as $item): ?>

                                    <tr>

                                        <td>

                                            <?= escapar_agenda(
                                                $item['CLIENTE']
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= escapar_agenda(
                                                date(
                                                    'd/m/Y',
                                                    strtotime(
                                                        $item['DATA_SERVICO']
                                                    )
                                                )
                                            ) ?>

                                        </td>

                                        <td>

                                            <?= periodo_agenda(
                                                $item['HORA_SERVICO']
                                            ) === 'MANHA'
                                                ? 'Manhã'
                                                : 'Tarde' ?>

                                        </td>

                                        <td>

                                            <span
                                                class="badge text-bg-secondary"
                                            >
                                                Cancelada
                                            </span>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    <?php endif; ?>

    <!-- BOTÕES -->

    <div class="text-center mt-4">

        <a
            href="concluir_faxina.php?aba=recebi"
            class="btn btn-lg text-white"
            style="background:#18A999"
        >

            Gerenciar solicitações recebidas

        </a>

        <a
            href="painel.php"
            class="btn btn-outline-secondary btn-lg ms-2"
        >

            Voltar ao painel

        </a>

    </div>

</section>

</main>

<script src="monitor_faxinas.js?v=1"></script>

<?php include __DIR__ . '/rodape.php'; ?>
