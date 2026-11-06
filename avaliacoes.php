
<?php
// =====================================================
// LIMPA LAR - MINHAS AVALIAÇÕES
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['ID_USUARIO'])) {
    header('Location: entrar.php');
    exit;
}

require_once __DIR__ . '/conexao.php';

$id_usuario = (int) $_SESSION['ID_USUARIO'];

function escapar_avaliacao($texto)
{
    return htmlspecialchars(
        (string) ($texto ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// =====================================================
// SEGURANÇA DO FORMULÁRIO
// =====================================================

if (empty($_SESSION['token_avaliacao'])) {
    $_SESSION['token_avaliacao'] = bin2hex(random_bytes(32));
}

$token = $_SESSION['token_avaliacao'];
$mensagem_erro = '';

// =====================================================
// REGISTRAR AVALIAÇÃO
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token_enviado = $_POST['token_avaliacao'] ?? '';

    $id_contratacao = filter_input(
        INPUT_POST,
        'id_contratacao',
        FILTER_VALIDATE_INT
    );

    $nota = filter_input(
        INPUT_POST,
        'nota',
        FILTER_VALIDATE_INT
    );

    $comentario = trim(
        (string) ($_POST['comentario'] ?? '')
    );

    $transacao = false;

    try {

        if (
            !is_string($token_enviado) ||
            !hash_equals($token, $token_enviado)
        ) {
            throw new Exception(
                'Formulário expirado. Atualize a página.'
            );
        }

        if (!$id_contratacao || !$nota || $nota < 1 || $nota > 5) {
            throw new Exception(
                'Selecione uma nota de 1 a 5 estrelas.'
            );
        }

        $tamanho_comentario = function_exists('mb_strlen')
            ? mb_strlen($comentario, 'UTF-8')
            : strlen($comentario);

        if ($tamanho_comentario > 1000) {
            throw new Exception(
                'O comentário pode ter até 1.000 caracteres.'
            );
        }

        // =================================================
        // VERIFICA SE A FAXINA ESTÁ CONCLUÍDA
        // =================================================

        mysqli_begin_transaction($conexao);
        $transacao = true;

        $sql = "
            SELECT ID_PROFISSIONAL
            FROM CONTRATACAO
            WHERE ID_CONTRATACAO = ?
              AND ID_CONTRATANTE = ?
              AND STATUS = 'CONCLUIDA'
            FOR UPDATE
        ";

        $stmt = mysqli_prepare($conexao, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            'ii',
            $id_contratacao,
            $id_usuario
        );

        mysqli_stmt_execute($stmt);

        $contratacao = mysqli_fetch_assoc(
            mysqli_stmt_get_result($stmt)
        );

        mysqli_stmt_close($stmt);

        if (!$contratacao) {
            throw new Exception(
                'Essa faxina não está concluída ou não pertence a você.'
            );
        }

        // =================================================
        // IMPEDE AVALIAR DUAS VEZES
        // =================================================

        $sql = "
            SELECT ID_AVALIACAO
            FROM AVALIACAO
            WHERE ID_CONTRATACAO = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conexao, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            'i',
            $id_contratacao
        );

        mysqli_stmt_execute($stmt);

        $ja_avaliada = mysqli_fetch_assoc(
            mysqli_stmt_get_result($stmt)
        );

        mysqli_stmt_close($stmt);

        if ($ja_avaliada) {
            throw new Exception(
                'Essa faxina já foi avaliada.'
            );
        }

        // =================================================
        // SALVA NOTA E COMENTÁRIO
        // =================================================

        $id_profissional = (int) $contratacao['ID_PROFISSIONAL'];

        $sql = "
            INSERT INTO AVALIACAO (
                ID_CONTRATACAO,
                ID_AVALIADOR,
                ID_AVALIADO,
                NOTA,
                COMENTARIO
            )
            VALUES (?, ?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conexao, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            'iiiis',
            $id_contratacao,
            $id_usuario,
            $id_profissional,
            $nota,
            $comentario
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conexao);
        $transacao = false;

        $_SESSION['avaliacao_sucesso'] =
            'Avaliação enviada! Obrigado por compartilhar sua experiência.';

        header('Location: avaliacoes.php');
        exit;

    } catch (Throwable $erro) {

        if ($transacao) {
            mysqli_rollback($conexao);
        }

        if ($erro instanceof mysqli_sql_exception) {

            error_log(
                'LimpaLar - avaliação: ' . $erro->getMessage()
            );

            $mensagem_erro =
                'Não foi possível salvar a avaliação. Tente novamente.';

        } else {

            $mensagem_erro = $erro->getMessage();
        }
    }
}

// =====================================================
// BUSCA AS FAXINAS CONCLUÍDAS DO CLIENTE
// =====================================================

$pendentes = [];
$avaliadas = [];
$erro_consulta = '';

try {

    $sql = "
        SELECT
            C.ID_CONTRATACAO,
            C.DATA_SERVICO,
            C.HORA_SERVICO,

            U.NOME AS NOME_PROFISSIONAL,

            V.ID_AVALIACAO,
            V.NOTA,
            V.COMENTARIO,
            V.DATA_AVALIACAO

        FROM CONTRATACAO C

        INNER JOIN USUARIO U
            ON U.ID_USUARIO = C.ID_PROFISSIONAL

        LEFT JOIN AVALIACAO V
            ON V.ID_CONTRATACAO = C.ID_CONTRATACAO

        WHERE C.ID_CONTRATANTE = ?
          AND C.STATUS = 'CONCLUIDA'

        ORDER BY
            C.DATA_SERVICO DESC,
            C.ID_CONTRATACAO DESC
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $id_usuario
    );

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    while ($linha = mysqli_fetch_assoc($resultado)) {

        if ($linha['ID_AVALIACAO'] === null) {

            $pendentes[] = $linha;

        } else {

            $avaliadas[] = $linha;
        }
    }

    mysqli_stmt_close($stmt);

} catch (Throwable $erro) {

    error_log(
        'LimpaLar - consultar avaliações: ' . $erro->getMessage()
    );

    $erro_consulta =
        'Não foi possível carregar as avaliações.';
}

// =====================================================
// MENSAGEM DE SUCESSO
// =====================================================

$mensagem_sucesso =
    $_SESSION['avaliacao_sucesso'] ?? '';

unset($_SESSION['avaliacao_sucesso']);

include __DIR__ . '/cabecalho.php';

?>

<!-- =====================================================
     ESTILOS DA TELA
====================================================== -->

<style>

    .titulo-avaliacoes {
        color: #126E68;
    }

    .card-avaliacao {
        border-radius: 18px;
    }

    .estrelas-avaliacao {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: .25rem;
    }

    .estrelas-avaliacao input {
        position: absolute;
        opacity: 0;
    }

    .estrelas-avaliacao label {
        cursor: pointer;
        color: #cbd5d1;
        font-size: 2.8rem;
        line-height: 1;
        transition: color .15s ease;
    }

    .estrelas-avaliacao input:checked ~ label,
    .estrelas-avaliacao label:hover,
    .estrelas-avaliacao label:hover ~ label {
        color: #e7a821;
    }

    .estrelas-avaliacao input:focus-visible + label {
        outline: 2px solid #126E68;
        border-radius: 5px;
    }

    .nota-publicada {
        color: #e7a821;
        letter-spacing: 2px;
    }

</style>

<section class="container py-5">

    <!-- TÍTULO -->

    <div class="text-center mb-4">

        <h1 class="fw-bold titulo-avaliacoes">
            ⭐ Minhas avaliações
        </h1>

        <p class="text-secondary mb-0">
            Avalie suas faxinas concluídas.
            Sua identidade não aparece na avaliação pública.
        </p>

    </div>

    <!-- MENSAGENS -->

    <?php if ($erro_consulta): ?>

        <div class="alert alert-danger" role="alert">
            <?= escapar_avaliacao($erro_consulta); ?>
        </div>

    <?php endif; ?>

    <?php if ($mensagem_erro): ?>

        <div class="alert alert-danger" role="alert">
            <?= escapar_avaliacao($mensagem_erro); ?>
        </div>

    <?php endif; ?>

    <?php if ($mensagem_sucesso): ?>

        <div class="alert alert-success" role="status">
            <?= escapar_avaliacao($mensagem_sucesso); ?>
        </div>

    <?php endif; ?>

    <?php if (!$erro_consulta): ?>

        <!-- ==========================================
             AVALIAÇÕES PENDENTES
        =========================================== -->

        <?php if (count($pendentes) > 0): ?>

            <div
                class="alert alert-warning d-flex align-items-center gap-3 rounded-4"
                role="status">

                <span class="fs-3">
                    🔔
                </span>

                <div>

                    <strong>
                        Você tem <?= count($pendentes); ?>
                        avaliação(ões) pendente(s).
                    </strong>

                    <div>
                        A nota de 1 a 5 estrelas é obrigatória.
                        O comentário é opcional.
                    </div>

                </div>

            </div>

            <?php $ultima = $pendentes[0]; ?>

            <div class="row justify-content-center">

                <div class="col-12 col-lg-8">

                    <div class="card border-0 shadow-sm card-avaliacao">

                        <div class="card-body p-4 p-md-5">

                            <div
                                class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">

                                <h2 class="h4 fw-bold titulo-avaliacoes mb-0">
                                    Avalie seu último atendimento pendente
                                </h2>

                                <span class="badge text-bg-warning">
                                    Avaliação pendente
                                </span>

                            </div>

                            <p class="mb-1">

                                <strong>Profissional:</strong>

                                <?= escapar_avaliacao(
                                    $ultima['NOME_PROFISSIONAL']
                                ); ?>

                            </p>

                            <p class="text-secondary mb-4">

                                <strong>Data da faxina:</strong>

                                <?= date(
                                    'd/m/Y',
                                    strtotime($ultima['DATA_SERVICO'])
                                ); ?>

                            </p>

                            <!-- =============================
                                 FORMULÁRIO DE AVALIAÇÃO
                            ============================== -->

                            <form
                                method="POST"
                                action="avaliacoes.php"
                                id="formAvaliacao">

                                <input
                                    type="hidden"
                                    name="token_avaliacao"
                                    value="<?= escapar_avaliacao($token); ?>">

                                <input
                                    type="hidden"
                                    name="id_contratacao"
                                    value="<?= (int)$ultima['ID_CONTRATACAO']; ?>">

                                <!-- ESTRELAS -->

                                <fieldset class="mb-4">

                                    <legend class="h6 fw-bold">
                                        Como foi o atendimento?
                                        <span class="text-danger">*</span>
                                    </legend>

                                    <div
                                        class="estrelas-avaliacao"
                                        role="radiogroup"
                                        aria-label="Nota de 1 a 5 estrelas">

                                        <?php for ($nota = 5; $nota >= 1; $nota--): ?>

                                            <input
                                                type="radio"
                                                id="estrela<?= $nota; ?>"
                                                name="nota"
                                                value="<?= $nota; ?>"
                                                required>

                                            <label
                                                for="estrela<?= $nota; ?>"
                                                title="<?= $nota; ?> estrela(s)"
                                                aria-label="<?= $nota; ?> estrela(s)">
                                                ★
                                            </label>

                                        <?php endfor; ?>

                                    </div>

                                    <div
                                        id="textoNota"
                                        class="small text-secondary mt-2">

                                        Selecione de 1 a 5 estrelas.

                                    </div>

                                </fieldset>

                                <!-- COMENTÁRIO OPCIONAL -->

                                <div class="mb-4">

                                    <label
                                        for="comentarioAvaliacao"
                                        class="form-label fw-semibold">

                                        Conte como foi sua experiência (opcional)

                                    </label>

                                    <textarea
                                        name="comentario"
                                        id="comentarioAvaliacao"
                                        rows="4"
                                        maxlength="1000"
                                        class="form-control"
                                        placeholder="O que achou do atendimento? Não informe nomes nem dados pessoais."></textarea>

                                    <div class="form-text">

                                        Seu nome não será exibido ao profissional.
                                        Por enquanto, o site mostra apenas
                                        a média e a quantidade de avaliações,
                                        não os comentários individuais.

                                    </div>

                                </div>

                                <!-- ENVIAR -->

                                <button
                                    class="btn btn-lg text-white w-100 fw-semibold"
                                    style="background:#18A999"
                                    type="submit"
                                    id="enviarAvaliacao">

                                    ⭐ Enviar avaliação

                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        <?php else: ?>

            <!-- SEM PENDÊNCIAS -->

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body text-center p-5">

                    <div class="display-4 mb-3">
                        ✅
                    </div>

                    <h2 class="h4 fw-bold titulo-avaliacoes">
                        Nenhuma avaliação pendente!
                    </h2>

                    <p class="text-secondary">

                        Quando uma faxina for finalizada pelo profissional,
                        a avaliação aparecerá aqui.

                    </p>

                    <a
                        href="concluir_faxina.php"
                        class="btn btn-outline-success">

                        Ver minhas faxinas

                    </a>

                </div>

            </div>

        <?php endif; ?>

        <!-- ==========================================
             HISTÓRICO PARTICULAR DO CLIENTE
        =========================================== -->

        <?php if (count($avaliadas) > 0): ?>

            <h2 class="h4 fw-bold titulo-avaliacoes mt-5 mb-3">
                Histórico das minhas avaliações
            </h2>

            <div class="row g-3">

                <?php foreach ($avaliadas as $item): ?>

                    <div class="col-12 col-md-6">

                        <div
                            class="card border-0 shadow-sm rounded-4 h-100">

                            <div class="card-body p-4">

                                <h3 class="h6 fw-bold titulo-avaliacoes mb-1">

                                    <?= escapar_avaliacao(
                                        $item['NOME_PROFISSIONAL']
                                    ); ?>

                                </h3>

                                <p class="small text-secondary mb-2">

                                    Faxina de

                                    <?= date(
                                        'd/m/Y',
                                        strtotime($item['DATA_SERVICO'])
                                    ); ?>

                                </p>

                                <div
                                    class="nota-publicada fs-5"
                                    aria-label="<?= (int)$item['NOTA']; ?> de 5 estrelas">

                                    <?= str_repeat(
                                        '★',
                                        (int)$item['NOTA']
                                    ); ?>

                                    <?= str_repeat(
                                        '☆',
                                        5 - (int)$item['NOTA']
                                    ); ?>

                                </div>

                                <?php if (
                                    trim((string)$item['COMENTARIO']) !== ''
                                ): ?>

                                    <p class="mb-0 mt-3 text-secondary">

                                        <?= nl2br(
                                            escapar_avaliacao(
                                                $item['COMENTARIO']
                                            )
                                        ); ?>

                                    </p>

                                <?php endif; ?>

                                <small class="text-secondary d-block mt-2">
                                    Visível somente para você nesta tela.
                                </small>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    <?php endif; ?>

</section>

<!-- =====================================================
     INTERATIVIDADE DAS ESTRELAS
====================================================== -->

<script>

document.querySelectorAll('input[name="nota"]').forEach(
    function (campo) {

        campo.addEventListener('change', function () {

            const texto = document.getElementById('textoNota');

            if (texto) {

                texto.textContent =
                    this.value + ' de 5 estrelas selecionadas.';
            }

        });
    }
);

const formularioAvaliacao =
    document.getElementById('formAvaliacao');

if (formularioAvaliacao) {

    formularioAvaliacao.addEventListener(
        'submit',
        function (evento) {

            if (!this.querySelector('input[name="nota"]:checked')) {

                evento.preventDefault();

                alert(
                    'Você precisa escolher de 1 a 5 estrelas.'
                );

                return;
            }

            const botao =
                document.getElementById('enviarAvaliacao');

            if (botao) {

                botao.disabled = true;

                botao.textContent =
                    'Enviando avaliação...';
            }
        }
    );
}

</script>

</main>

<?php include __DIR__ . '/rodape.php'; ?>
                    