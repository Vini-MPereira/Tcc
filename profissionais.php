
<?php
// LIMPA LAR - PROFISSIONAIS POR PERÍODOS

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexao.php';

function prof_esc($valor) {
    return htmlspecialchars(
        (string)($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$anuncios = [];
$erro = '';

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

        WHERE A.STATUS = 'ATIVO'
          AND U.ATIVO = 1

        ORDER BY A.DATA_CADASTRO DESC
    ";

    $resultado = mysqli_query($conexao, $sql);

    $anuncios = mysqli_fetch_all(
        $resultado,
        MYSQLI_ASSOC
    );

} catch (Throwable $e) {
    error_log(
        'LimpaLar - profissionais: ' . $e->getMessage()
    );

    $erro = 'Não foi possível carregar os profissionais.';
}

$id_logado = (int)($_SESSION['ID_USUARIO'] ?? 0);

include __DIR__ . '/cabecalho.php';
?>

<style>
    .lp-card {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        height: 100%;
        box-shadow: 0 5px 20px #126e681a;
    }

    .lp-foto {
        height: 200px;
        width: 100%;
        object-fit: cover;
    }

    .lp-sem-foto {
        height: 200px;
        display: grid;
        place-items: center;
        background: #e7f5f1;
        font-size: 65px;
    }

    .lp-verde {
        color: #126e68;
    }

    .lp-btn {
        background: #18a999;
        border-color: #18a999;
        color: white;
    }

    .lp-btn:hover,
    .lp-btn:focus {
        background: #126e68;
        border-color: #126e68;
        color: white;
    }

    .lp-periodo {
        cursor: pointer;
        border: 2px solid #d9e9e5;
        border-radius: 14px;
        padding: 15px;
        height: 100%;
        transition: .15s;
    }

    .lp-periodo:has(input:checked) {
        border-color: #18a999;
        background: #e7f5f1;
    }

    .lp-periodo:has(input:disabled) {
        cursor: not-allowed;
        opacity: .48;
    }

    .lp-modal-foto {
        width: 100%;
        height: 190px;
        object-fit: cover;
        border-radius: 12px;
    }

    .lp-modal-sem-foto {
        height: 190px;
        display: grid;
        place-items: center;
        background: #e7f5f1;
        border-radius: 12px;
        font-size: 60px;
    }

    .lp-modal-foto[hidden],
    .lp-modal-sem-foto[hidden] {
        display: none !important;
    }
</style>

<!-- LISTA DE PROFISSIONAIS -->

<section class="container py-5">

    <div class="text-center mb-5">

        <h1 class="fw-bold lp-verde">
            🧹 Profissionais de limpeza
        </h1>

        <p class="text-secondary">
            Escolha um profissional e solicite sua faxina.
        </p>

    </div>

    <?php if ($erro): ?>

        <div class="alert alert-danger">
            <?= prof_esc($erro) ?>
        </div>

    <?php elseif (!$anuncios): ?>

        <div class="alert alert-info">
            Ainda não existem anúncios ativos.
        </div>

    <?php else: ?>

        <div class="row g-4">

            <?php foreach ($anuncios as $a): ?>

                <?php
                $foto = '';

                foreach (
                    [
                        $a['FOTO_USUARIO'] ?? '',
                        $a['FOTO'] ?? ''
                    ] as $arquivo
                ) {
                    $arquivo = trim((string)$arquivo);

                    if (
                        $arquivo !== '' &&
                        is_file(__DIR__ . '/' . $arquivo)
                    ) {
                        $foto = $arquivo;
                        break;
                    }
                }

                $descricao = (string)($a['DESCRICAO'] ?? '');

                $resumo = function_exists('mb_substr')
                    ? mb_substr($descricao, 0, 145, 'UTF-8')
                    : substr($descricao, 0, 145);

                $media = (float)($a['MEDIA'] ?? 0);
                $total = (int)($a['TOTAL'] ?? 0);
                ?>

                <div class="col-12 col-md-6 col-lg-4">

                    <div class="lp-card card">

                        <?php if ($foto): ?>

                            <img
                                class="lp-foto"
                                src="<?= prof_esc($foto) ?>"
                                alt="Foto do profissional"
                            >

                        <?php else: ?>

                            <div class="lp-sem-foto">
                                🧹
                            </div>

                        <?php endif; ?>

                        <div class="card-body p-4 d-flex flex-column">

                            <h2 class="h5 lp-verde fw-bold">
                                <?= prof_esc($a['NOME']) ?>
                            </h2>

                            <p class="text-secondary flex-grow-1">
                                <?= nl2br(prof_esc($resumo)) ?>
                            </p>

                            <p class="small text-secondary">

                                <?php if ($total > 0): ?>

                                    ⭐
                                    <?= number_format(
                                        $media,
                                        1,
                                        ',',
                                        '.'
                                    ) ?>

                                    (<?= $total ?> avaliações)

                                <?php else: ?>

                                    Ainda sem avaliações

                                <?php endif; ?>

                            </p>

                            <p class="fw-bold lp-verde">
                                Valor a combinar diretamente
                            </p>

                            <button
                                type="button"
                                class="btn lp-btn w-100"
                                data-bs-toggle="modal"
                                data-bs-target="#modalSolicitar"

                                data-id-anuncio="<?= (int)$a['ID_ANUNCIO'] ?>"
                                data-id-usuario="<?= (int)$a['ID_USUARIO'] ?>"
                                data-nome="<?= prof_esc($a['NOME']) ?>"
                                data-descricao="<?= prof_esc($descricao) ?>"
                                data-foto="<?= prof_esc($foto) ?>"
                                data-media="<?= number_format($media, 1, '.', '') ?>"
                                data-total="<?= $total ?>"
                            >
                                Conheça o profissional
                            </button>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<!-- MODAL CONHEÇA O PROFISSIONAL -->

<div
    class="modal fade"
    id="modalSolicitar"
    tabindex="-1"
    aria-labelledby="tituloProfissional"
    aria-hidden="true"
>

    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content border-0 rounded-4">

            <div
                class="modal-header"
                style="background:#e7f5f1"
            >

                <h2
                    id="tituloProfissional"
                    class="modal-title h5 lp-verde fw-bold"
                >
                    Conheça o profissional
                </h2>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Fechar"
                ></button>

            </div>

            <div class="modal-body p-4">

                <div class="row g-4">

                    <!-- FOTO E AVALIAÇÕES -->

                    <div class="col-lg-4">

                        <img
                            id="fotoModal"
                            class="lp-modal-foto mb-3"
                            alt="Foto do profissional"
                            hidden
                        >

                        <div
                            id="semFotoModal"
                            class="lp-modal-sem-foto mb-3"
                        >
                            🧹
                        </div>

                        <h3
                            id="nomeModal"
                            class="h4 fw-bold lp-verde"
                        >
                            Profissional
                        </h3>

                        <p
                            id="avaliacaoModal"
                            class="text-secondary"
                        ></p>

                        <p class="fw-bold lp-verde">
                            Valor a combinar diretamente
                        </p>

                    </div>

                    <!-- DESCRIÇÃO -->

                    <div class="col-lg-4">

                        <h3 class="h5 lp-verde">
                            Sobre o profissional
                        </h3>

                        <p
                            id="descricaoModal"
                            class="text-secondary"
                            style="white-space:pre-line;overflow-wrap:anywhere"
                        ></p>

                        <div
                            class="p-3 rounded-3"
                            style="background:#e7f5f1"
                        >
                            Após o profissional aceitar sua
                            solicitação, vocês poderão entrar
                            em contato para combinar o valor
                            e os detalhes.
                        </div>

                    </div>

                    <!-- FORMULÁRIO -->

                    <div class="col-lg-4">

                        <h3 class="h5 lp-verde fw-bold">
                            Solicitar faxina
                        </h3>

                        <div
                            id="alertaLogin"
                            class="alert alert-warning"
                            hidden
                        ></div>

                        <form id="formSolicitarServico">

                            <!-- DATA -->

                            <div class="mb-3">

                                <label
                                    for="dataServico"
                                    class="form-label fw-bold"
                                >
                                    Data da faxina
                                </label>

                                <input
                                    id="dataServico"
                                    type="date"
                                    class="form-control"
                                    required
                                >

                            </div>

                            <!-- MANHÃ OU TARDE -->

                            <fieldset class="mb-3">

                                <legend class="fs-6 fw-bold">
                                    Escolha um período
                                </legend>

                                <div class="row g-2">

                                    <div class="col-6">

                                        <label class="lp-periodo d-block">

                                            <input
                                                class="form-check-input me-1"
                                                type="radio"
                                                name="periodoServico"
                                                value="MANHA"
                                                required
                                            >

                                            <strong>☀️ Manhã</strong>

                                            <small class="d-block text-secondary mt-1">
                                                06h às 12h
                                            </small>

                                        </label>

                                    </div>

                                    <div class="col-6">

                                        <label class="lp-periodo d-block">

                                            <input
                                                class="form-check-input me-1"
                                                type="radio"
                                                name="periodoServico"
                                                value="TARDE"
                                                required
                                            >

                                            <strong>🌤️ Tarde</strong>

                                            <small class="d-block text-secondary mt-1">
                                                13h às 17h
                                            </small>

                                        </label>

                                    </div>

                                </div>

                                <div
                                    id="mensagemPeriodos"
                                    class="form-text"
                                    aria-live="polite"
                                >
                                    Escolha uma data e selecione
                                    manhã ou tarde.
                                </div>

                            </fieldset>

                            <!-- ENDEREÇO -->

                            <div class="mb-3">

                                <label
                                    for="enderecoServico"
                                    class="form-label fw-bold"
                                >
                                    Rua ou avenida
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="enderecoServico"
                                    maxlength="255"
                                    required
                                >

                            </div>

                            <div class="row g-2 mb-3">

                                <div class="col-4">

                                    <label
                                        for="numeroServico"
                                        class="form-label fw-bold"
                                    >
                                        Número
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="numeroServico"
                                        maxlength="20"
                                        required
                                    >

                                </div>

                                <div class="col-8">

                                    <label
                                        for="bairroServico"
                                        class="form-label fw-bold"
                                    >
                                        Bairro da faxina
                                    </label>

                                    <input
                                        type="text"
                                        class="form-control"
                                        id="bairroServico"
                                        maxlength="100"
                                    >

                                </div>

                            </div>

                            <div class="mb-3">

                                <label
                                    for="observacaoServico"
                                    class="form-label fw-bold"
                                >
                                    Observações (opcional)
                                </label>

                                <textarea
                                    class="form-control"
                                    id="observacaoServico"
                                    rows="2"
                                    maxlength="1000"
                                ></textarea>

                            </div>

                            <div
                                id="mensagemSolicitacao"
                                aria-live="polite"
                            ></div>

                            <button
                                id="btnSolicitarServico"
                                type="submit"
                                class="btn lp-btn w-100 mt-2"
                            >
                                Solicitar faxina
                            </button>

                            <a
                                id="acompanharServico"
                                href="concluir_faxina.php?aba=contratei"
                                class="btn btn-outline-success w-100 mt-2 d-none"
                            >
                                Acompanhar em Minhas faxinas
                            </a>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const modal = document.getElementById('modalSolicitar');

    if (!modal) return;

    const usuarioLogado = <?= $id_logado ?>;

    const form = document.getElementById('formSolicitarServico');
    const data = document.getElementById('dataServico');

    const radios = [
        ...document.querySelectorAll(
            'input[name="periodoServico"]'
        )
    ];

    const status = document.getElementById('mensagemPeriodos');
    const mensagem = document.getElementById('mensagemSolicitacao');
    const botaoEnviar = document.getElementById('btnSolicitarServico');
    const acompanhar = document.getElementById('acompanharServico');

    let anuncioId = 0;
    let profissionalId = 0;
    let bloqueado = false;

    // DATA LOCAL

    function hojeLocal() {
        const d = new Date();

        return [
            d.getFullYear(),
            String(d.getMonth() + 1).padStart(2, '0'),
            String(d.getDate()).padStart(2, '0')
        ].join('-');
    }

    // DESMARCAR E DESABILITAR PERÍODOS

    function desligarRadios() {
        radios.forEach(r => {
            r.checked = false;
            r.disabled = true;
        });
    }

    // MOSTRAR MENSAGEM

    function mensagemEnvio(texto, tipo = 'danger') {
        mensagem.className = 'alert alert-' + tipo + ' mt-3';
        mensagem.textContent = texto;
    }

    // ATIVAR MANHÃ E TARDE DEPOIS DE ESCOLHER DATA

    function atualizarPeriodos() {

        desligarRadios();

        if (!data.value || data.value < hojeLocal()) {
            status.textContent =
                'Escolha uma data de hoje em diante.';
            return;
        }

        const agora = new Date();

        const minutos =
            agora.getHours() * 60 + agora.getMinutes();

        let opcoes = 0;

        radios.forEach(r => {

            const inicio = r.value === 'MANHA'
                ? 360
                : 780;

            // Não permite selecionar um período
            // que já começou no dia atual.

            r.disabled =
                data.value === hojeLocal() &&
                minutos >= inicio;

            if (!r.disabled) {
                opcoes++;
            }

        });

        status.textContent = opcoes
            ? 'Selecione manhã ou tarde. O profissional confirmará a solicitação.'
            : 'Os períodos de hoje já começaram. Escolha outro dia.';
    }

    // ABRIR CONHEÇA O PROFISSIONAL

    modal.addEventListener('show.bs.modal', event => {

        const alvo = event.relatedTarget;

        if (!alvo) return;

        form.reset();

        mensagem.textContent = '';
        mensagem.className = '';

        acompanhar.classList.add('d-none');

        anuncioId = Number(
            alvo.dataset.idAnuncio || 0
        );

        profissionalId = Number(
            alvo.dataset.idUsuario || 0
        );

        bloqueado =
            !usuarioLogado ||
            usuarioLogado === profissionalId;

        botaoEnviar.disabled = bloqueado;

        desligarRadios();

        data.min = hojeLocal();

        status.textContent =
            'Escolha uma data e selecione manhã ou tarde.';

        document.getElementById('nomeModal').textContent =
            alvo.dataset.nome || 'Profissional';

        document.getElementById('descricaoModal').textContent =
            alvo.dataset.descricao || 'Sem descrição.';

        // AVALIAÇÕES

        const total = Number(
            alvo.dataset.total || 0
        );

        const nota = Number(
            alvo.dataset.media || 0
        );

        document.getElementById('avaliacaoModal').textContent =
            total > 0
                ? '⭐ ' +
                    nota.toFixed(1).replace('.', ',') +
                    ' (' + total + ' avaliações)'
                : 'Ainda sem avaliações';

        // FOTO

        const foto = document.getElementById('fotoModal');
        const semFoto = document.getElementById('semFotoModal');

        const caminho = alvo.dataset.foto || '';

        foto.hidden = !caminho;
        semFoto.hidden = !!caminho;

        if (caminho) {
            foto.src = caminho;
        } else {
            foto.removeAttribute('src');
        }

        foto.onerror = () => {
            foto.hidden = true;
            semFoto.hidden = false;
        };

        // LOGIN

        const aviso = document.getElementById('alertaLogin');

        aviso.hidden = !bloqueado;

        aviso.textContent = !usuarioLogado
            ? 'Entre na sua conta para solicitar uma faxina.'
            : 'Você não pode contratar seu próprio anúncio.';

    });

    data.addEventListener('change', atualizarPeriodos);

    // ENVIAR SOLICITAÇÃO

    form.addEventListener('submit', async e => {

        e.preventDefault();

        if (bloqueado) return;

        const escolha = radios.find(
            r => r.checked && !r.disabled
        );

        if (!data.value || !escolha) {
            mensagemEnvio(
                'Escolha uma data e um período.'
            );
            return;
        }

        botaoEnviar.disabled = true;
        botaoEnviar.textContent = 'Enviando...';

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

                        id_anuncio: anuncioId,

                        data_servico: data.value,

                        periodo_servico: escolha.value,

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

            const retorno = await resposta.json();

            if (!resposta.ok || !retorno.sucesso) {
                throw new Error(
                    retorno.mensagem ||
                    'Não foi possível solicitar a faxina.'
                );
            }

            mensagemEnvio(
                retorno.mensagem ||
                'Solicitação enviada com sucesso!',
                'success'
            );

            acompanhar.classList.remove('d-none');

            desligarRadios();

            status.textContent =
                'Pedido enviado. Acompanhe em Minhas faxinas.';

        } catch (erro) {

            mensagemEnvio(
                erro.message ||
                'Erro ao enviar solicitação.'
            );

        } finally {

            botaoEnviar.disabled = bloqueado;

            botaoEnviar.textContent =
                'Solicitar faxina';

        }

    });

});
</script>

</main>

<?php include __DIR__ . '/rodape.php'; ?>
