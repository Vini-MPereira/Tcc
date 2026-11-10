
<?php
// =====================================================
// PAINEL LIMPA LAR - AVISOS DE SOLICITAÇÕES E AVALIAÇÕES
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// EXIGE LOGIN
// =====================================================

if (empty($_SESSION["ID_USUARIO"])) {
    if (($_GET["notificacoes"] ?? "") === "1") {
        header("Content-Type: application/json; charset=utf-8");
        http_response_code(401);
        echo json_encode([
            "sucesso" => false,
            "mensagem" => "Faça login novamente."
        ]);
        exit;
    }

    header("Location: entrar.php");
    exit;
}

require_once __DIR__ . "/conexao.php";

$id_usuario = (int) $_SESSION["ID_USUARIO"];
$nome_usuario = (string) (
    $_SESSION["NOME_USUARIO"] ?? "Usuário"
);

// =====================================================
// CONTAGEM SEGURA DAS NOTIFICAÇÕES NO MYSQL
// =====================================================

function contar_notificacoes_painel($conexao, $id_usuario)
{
    $sql = "
        SELECT
            (
                SELECT COUNT(*)
                FROM CONTRATACAO
                WHERE ID_PROFISSIONAL = ?
                  AND STATUS = 'SOLICITADA'
            ) AS solicitacoes,

            (
                SELECT COUNT(*)
                FROM CONTRATACAO C
                WHERE C.ID_CONTRATANTE = ?
                  AND C.STATUS = 'CONCLUIDA'
                  AND NOT EXISTS (
                      SELECT 1
                      FROM AVALIACAO A
                      WHERE A.ID_CONTRATACAO = C.ID_CONTRATACAO
                  )
            ) AS avaliacoes
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id_usuario,
        $id_usuario
    );

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $dados = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($stmt);

    return [
        "solicitacoes" => (int) (
            $dados["solicitacoes"] ?? 0
        ),
        "avaliacoes" => (int) (
            $dados["avaliacoes"] ?? 0
        )
    ];
}

$notificacoes = [
    "solicitacoes" => 0,
    "avaliacoes" => 0
];

$erro_consulta = "";

try {
    $notificacoes = contar_notificacoes_painel(
        $conexao,
        $id_usuario
    );

} catch (Throwable $erro) {

    error_log(
        "LimpaLar - notificações: " . $erro->getMessage()
    );

    $erro_consulta =
        "Não foi possível consultar as notificações neste momento.";
}

// =====================================================
// RESPOSTA JSON PARA ATUALIZAÇÃO AUTOMÁTICA
// =====================================================

if (($_GET["notificacoes"] ?? "") === "1") {

    header("Content-Type: application/json; charset=utf-8");

    header("Cache-Control: no-store, no-cache, must-revalidate");

    if ($erro_consulta !== "") {

        http_response_code(500);

        echo json_encode([
            "sucesso" => false,
            "mensagem" => $erro_consulta
        ]);

    } else {

        echo json_encode([
            "sucesso" => true,
            "solicitacoes" => $notificacoes["solicitacoes"],
            "avaliacoes" => $notificacoes["avaliacoes"]
        ]);
    }

    exit;
}

include __DIR__ . "/cabecalho.php";
?>

<!-- =====================================================
     ESTILO DO PAINEL
====================================================== -->

<style>
    .titulo-painel {
        color: #126E68;
    }

    .card-painel {
        border: 0;
        border-radius: 20px;
        transition:
            transform .2s ease,
            box-shadow .2s ease;
    }

    .card-painel:hover {
        transform: translateY(-3px);
        box-shadow:
            0 10px 25px rgba(18, 110, 104, .10) !important;
    }

    .btn-limpalar-painel {
        background: #18A999;
        border-color: #18A999;
        color: white;
    }

    .btn-limpalar-painel:hover,
    .btn-limpalar-painel:focus {
        background: #126E68;
        border-color: #126E68;
        color: white;
    }

    .aviso-painel {
        border-radius: 15px;
    }
</style>

<!-- O cabecalho.php já abre a tag main -->

<section class="container py-5">

    <!-- =====================================================
         CABEÇALHO DO PAINEL
    ====================================================== -->

    <div class="text-center mb-4">

        <h1 class="fw-bold titulo-painel">

            Olá, <?= htmlspecialchars(
                $nome_usuario,
                ENT_QUOTES,
                "UTF-8"
            ); ?>! 👋

        </h1>

        <p class="text-secondary mb-0">
            O que você deseja fazer hoje?
        </p>

    </div>

    <?php if ($erro_consulta !== ""): ?>

        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars(
                $erro_consulta,
                ENT_QUOTES,
                "UTF-8"
            ); ?>
        </div>

    <?php endif; ?>

    <!-- =====================================================
         NOTIFICAÇÕES EM DESTAQUE
    ====================================================== -->

    <div id="avisosPainel" aria-live="polite">

        <!-- SOLICITAÇÕES DO PROFISSIONAL -->

        <div
            id="avisoSolicitacoes"
            class="alert alert-warning aviso-painel shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-3 <?= $notificacoes['solicitacoes'] === 0 ? 'd-none' : ''; ?>"
            role="status">

            <div>

                <strong>
                    🔔 Nova solicitação para você!
                </strong>

                <div>

                    Você tem

                    <strong id="numeroSolicitacoes">
                        <?= $notificacoes['solicitacoes']; ?>
                    </strong>

                    solicitação(ões) aguardando resposta.

                </div>

            </div>

            <a
                href="concluir_faxina.php?aba=recebi"
                class="btn btn-warning fw-semibold">

                Ver solicitações

            </a>

        </div>

        <!-- AVALIAÇÃO PENDENTE DO CLIENTE -->

        <div
            id="avisoAvaliacoes"
            class="alert alert-info aviso-painel shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-3 <?= $notificacoes['avaliacoes'] === 0 ? 'd-none' : ''; ?>"
            role="status">

            <div>

                <strong>
                    ⭐ Sua avaliação está pendente!
                </strong>

                <div>

                    Você tem

                    <strong id="numeroAvaliacoes">
                        <?= $notificacoes['avaliacoes']; ?>
                    </strong>

                    faxina(s) concluída(s) aguardando
                    sua nota de 1 a 5 estrelas.

                    O comentário é opcional.

                </div>

            </div>

            <a
                href="avaliacoes.php"
                class="btn btn-primary fw-semibold">

                Avaliar agora

            </a>

        </div>

    </div>

    <!-- =====================================================
         CARDS DO PAINEL
    ====================================================== -->

    <div class="row g-4 justify-content-center">

        <!-- =========================================
             MINHAS AVALIAÇÕES
        ========================================== -->

        <div class="col-12 col-md-6">

            <div class="card card-painel h-100 shadow-sm">

                <div class="card-body p-4 p-md-5 text-center">

                    <div class="fs-1 mb-3">
                        ⭐
                    </div>

                    <h2 class="h4 fw-bold titulo-painel">
                        Minhas avaliações
                    </h2>

                    <p class="mt-3 text-secondary">
                        Avalie os profissionais após
                        a conclusão da faxina.
                    </p>

                    <span
                        id="badgeAvaliacoes"
                        class="badge rounded-pill text-bg-warning d-block mx-auto mb-3 <?= $notificacoes['avaliacoes'] === 0 ? 'd-none' : ''; ?>"
                        style="width:fit-content;">

                        <?= $notificacoes['avaliacoes']; ?>
                        pendente(s)

                    </span>

                    <a
                        href="avaliacoes.php"
                        class="btn btn-lg btn-limpalar-painel px-4 mt-2">

                        ⭐ Ver avaliações

                    </a>

                </div>

            </div>

        </div>

        <!-- =========================================
             MINHAS FAXINAS
        ========================================== -->

        <div class="col-12 col-md-6">

            <div class="card card-painel h-100 shadow-sm">

                <div class="card-body p-4 p-md-5 text-center">

                    <div class="fs-1 mb-3">
                        🧹
                    </div>

                    <h2 class="h4 fw-bold titulo-painel">
                        Minhas faxinas
                    </h2>

                    <p class="mt-3 text-secondary">
                        Receba solicitações, aceite serviços
                        e finalize faxinas realizadas.
                    </p>

                    <span
                        id="badgeSolicitacoes"
                        class="badge rounded-pill text-bg-warning d-block mx-auto mb-3 <?= $notificacoes['solicitacoes'] === 0 ? 'd-none' : ''; ?>"
                        style="width:fit-content;">

                        <?= $notificacoes['solicitacoes']; ?>
                        nova(s) solicitação(ões)

                    </span>

                    <a
                        href="concluir_faxina.php"
                        class="btn btn-lg btn-limpalar-painel px-4 mt-2">

                        ✓ Gerenciar faxinas

                    </a>

                </div>

            </div>

        </div>

        <!-- =========================================
             OFERECER SERVIÇO
        ========================================== -->

        <div class="col-12 col-md-6">

            <div class="card card-painel h-100 shadow-sm">

                <div class="card-body p-4 p-md-5 text-center">

                    <div class="fs-1 mb-3">
                        🧹
                    </div>

                    <h2 class="h4 fw-bold titulo-painel">
                        Oferecer meu serviço
                    </h2>

                    <p class="mt-3 text-secondary">
                        Cadastre seu serviço de faxina
                        e encontre clientes em Bauru.
                    </p>

                    <a
                        href="cadastrar_anuncio.php"
                        class="btn btn-lg btn-limpalar-painel px-4 mt-3">

                        🧹 Oferecer meu serviço

                    </a>

                </div>

            </div>

        </div>

        <!-- =========================================
             PROCURAR SERVIÇOS
        ========================================== -->

        <div class="col-12 col-md-6">

            <div class="card card-painel h-100 shadow-sm">

                <div class="card-body p-4 p-md-5 text-center">

                    <div class="fs-1 mb-3">
                        🔎
                    </div>

                    <h2 class="h4 fw-bold titulo-painel">
                        Procurar serviços
                    </h2>

                    <p class="mt-3 text-secondary">
                        Encontre profissionais de faxina
                        disponíveis em Bauru.
                    </p>

                    <a
                        href="profissionais.php"
                        class="btn btn-lg btn-limpalar-painel px-4 mt-3">

                        🔎 Procurar serviços

                    </a>

                </div>

            </div>

        </div>

    </div>

    <!-- =====================================================
         SAIR
    ====================================================== -->

    <div class="text-center mt-5">

        <a
            href="sair.php"
            class="btn btn-outline-secondary px-4">

            Sair da conta

        </a>

    </div>

</section>

<!-- =====================================================
     NOTIFICAÇÃO FLUTUANTE DO BOOTSTRAP
====================================================== -->

<div
    class="toast-container position-fixed bottom-0 end-0 p-3"
    style="z-index:1100;">

    <div
        id="toastNotificacao"
        class="toast border-0 shadow"
        role="status"
        aria-live="polite"
        aria-atomic="true">

        <div class="toast-header">

            <strong
                class="me-auto"
                style="color:#126E68;">

                🔔 LimpaLar

            </strong>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="toast"
                aria-label="Fechar">
            </button>

        </div>

        <div
            class="toast-body"
            id="textoToastNotificacao">
        </div>

    </div>

</div>

<!-- =====================================================
     ATUALIZAÇÃO AUTOMÁTICA DAS NOTIFICAÇÕES
====================================================== -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    let anteriores = {
        solicitacoes: <?= (int) $notificacoes['solicitacoes']; ?>,
        avaliacoes: <?= (int) $notificacoes['avaliacoes']; ?>
    };

    let consultando = false;

    // =================================================
    // ATUALIZA OS AVISOS E BADGES
    // =================================================

    function atualizarAviso(tipo, quantidade) {

        const ehSolicitacao = tipo === 'solicitacoes';

        const aviso = document.getElementById(
            ehSolicitacao
                ? 'avisoSolicitacoes'
                : 'avisoAvaliacoes'
        );

        const numero = document.getElementById(
            ehSolicitacao
                ? 'numeroSolicitacoes'
                : 'numeroAvaliacoes'
        );

        const badge = document.getElementById(
            ehSolicitacao
                ? 'badgeSolicitacoes'
                : 'badgeAvaliacoes'
        );

        aviso.classList.toggle(
            'd-none',
            quantidade === 0
        );

        badge.classList.toggle(
            'd-none',
            quantidade === 0
        );

        numero.textContent = quantidade;

        badge.textContent = ehSolicitacao
            ? `${quantidade} nova(s) solicitação(ões)`
            : `${quantidade} pendente(s)`;
    }

    // =================================================
    // MOSTRA NOTIFICAÇÃO FLUTUANTE
    // =================================================

    function mostrarToast(texto) {

        const elemento = document.getElementById(
            'toastNotificacao'
        );

        document.getElementById(
            'textoToastNotificacao'
        ).textContent = texto;

        if (
            window.bootstrap &&
            bootstrap.Toast
        ) {

            bootstrap.Toast.getOrCreateInstance(
                elemento,
                { delay: 6500 }
            ).show();
        }
    }

    // =================================================
    // CONSULTA O MYSQL SEM RECARREGAR A PÁGINA
    // =================================================

    async function verificarNotificacoes() {

        if (consultando) {
            return;
        }

        consultando = true;

        try {

            const resposta = await fetch(
                'painel.php?notificacoes=1',
                {
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!resposta.ok) {
                return;
            }

            const dados = await resposta.json();

            if (!dados.sucesso) {
                return;
            }

            const solicitacoes = Math.max(
                0,
                Number(dados.solicitacoes) || 0
            );

            const avaliacoes = Math.max(
                0,
                Number(dados.avaliacoes) || 0
            );

            atualizarAviso(
                'solicitacoes',
                solicitacoes
            );

            atualizarAviso(
                'avaliacoes',
                avaliacoes
            );

            // =========================================
            // AVISA QUANDO CHEGAR UMA NOVIDADE
            // =========================================

            if (
                solicitacoes > anteriores.solicitacoes
            ) {

                mostrarToast(
                    'Você recebeu uma nova solicitação de faxina! Veja em Minhas faxinas.'
                );

            } else if (
                avaliacoes > anteriores.avaliacoes
            ) {

                mostrarToast(
                    'Uma faxina foi concluída! Sua avaliação está disponível.'
                );
            }

            anteriores = {
                solicitacoes,
                avaliacoes
            };

        } catch (erro) {

            // Se a internet/local falhar, mantém
            // os avisos que já estavam aparecendo.

            console.warn(
                'Falha ao atualizar notificações do LimpaLar.',
                erro
            );

        } finally {

            consultando = false;
        }
    }

    // =================================================
    // VERIFICA A CADA 10 SEGUNDOS
    // =================================================

    setInterval(
        verificarNotificacoes,
        10000
    );

    // Atualiza também quando o usuário
    // volta para a aba do navegador.

    document.addEventListener(
        'visibilitychange',
        function () {

            if (!document.hidden) {
                verificarNotificacoes();
            }
        }
    );

});
</script>

</main>

<?php include __DIR__ . "/rodape.php"; ?>