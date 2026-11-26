
<?php
// =====================================================
// LIMPA LAR - MINHA FOTO DE PERFIL
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['ID_USUARIO'])) {
    header('Location: entrar.php');
    exit;
}

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/foto_upload.php';

function e_foto($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$id_usuario = (int) $_SESSION['ID_USUARIO'];

if (empty($_SESSION['token_foto'])) {
    $_SESSION['token_foto'] = bin2hex(random_bytes(32));
}

$mensagem = '';
$tipo_mensagem = 'danger';

// =====================================================
// BUSCAR FOTO ATUAL
// =====================================================

$stmt = mysqli_prepare(
    $conexao,
    "SELECT FOTO
     FROM USUARIO
     WHERE ID_USUARIO = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $id_usuario
);

mysqli_stmt_execute($stmt);

$usuario = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

mysqli_stmt_close($stmt);

if (!$usuario) {
    header('Location: entrar.php');
    exit;
}

$foto_atual = trim(
    (string) ($usuario['FOTO'] ?? '')
);

// =====================================================
// ATUALIZAR FOTO
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $token = $_POST['token_foto'] ?? '';

        if (
            !is_string($token) ||
            !hash_equals($_SESSION['token_foto'], $token)
        ) {
            throw new RuntimeException(
                'O formulário expirou. Atualize a página.'
            );
        }

        if (
            !isset($_FILES['foto']) ||
            $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE
        ) {
            throw new RuntimeException(
                'Escolha uma foto antes de salvar.'
            );
        }

        // =============================================
        // SALVAR NOVA FOTO
        // =============================================

        $nova_foto = salvar_foto_perfil(
            $_FILES['foto']
        );

        try {

            $stmt = mysqli_prepare(
                $conexao,
                'UPDATE USUARIO
                 SET FOTO = ?
                 WHERE ID_USUARIO = ?'
            );

            mysqli_stmt_bind_param(
                $stmt,
                'si',
                $nova_foto,
                $id_usuario
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);

        } catch (Throwable $erro) {

            apagar_foto_perfil_local($nova_foto);

            throw $erro;
        }

        // Apaga a foto antiga somente depois
        // que a nova foi salva com sucesso.
        if ($foto_atual !== '') {
            apagar_foto_perfil_local($foto_atual);
        }

        $_SESSION['token_foto'] = bin2hex(
            random_bytes(32)
        );

        header('Location: minha_foto.php?salvo=1');
        exit;

    } catch (Throwable $erro) {

        if ($erro instanceof RuntimeException) {

            $mensagem = $erro->getMessage();

        } else {

            error_log(
                'LimpaLar - atualizar foto: ' .
                $erro->getMessage()
            );

            $mensagem =
                'Não foi possível atualizar a foto.';
        }
    }
}

if (isset($_GET['salvo'])) {
    $mensagem = 'Foto atualizada com sucesso!';
    $tipo_mensagem = 'success';
}

$foto_existe = (
    $foto_atual !== '' &&
    is_file(__DIR__ . '/' . $foto_atual)
);

include __DIR__ . '/cabecalho.php';
?>

<style>
    .perfil-avatar {
        width: 145px;
        height: 145px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #E7F5F1;
    }

    .perfil-sem-foto {
        width: 145px;
        height: 145px;
        border-radius: 50%;
        background: #E7F5F1;
        border: 4px solid #D5EDE7;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: auto;
        font-size: 60px;
    }

    .perfil-botao {
        background: #18A999;
        border-color: #18A999;
        color: #FFFFFF;
    }

    .perfil-botao:hover {
        background: #126E68;
        border-color: #126E68;
        color: #FFFFFF;
    }
</style>

<section class="container py-5">

    <div class="row justify-content-center">

        <div class="col-12 col-md-10 col-lg-6">

            <div class="card border-0 rounded-4 shadow-sm">

                <div class="card-body p-4 p-md-5">

                    <!-- TÍTULO -->

                    <div class="text-center mb-4">

                        <h1
                            class="fw-bold h2"
                            style="color:#126E68"
                        >
                            📷 Minha foto de perfil
                        </h1>

                        <p class="text-secondary">

                            Adicione ou atualize sua foto
                            para deixar seu perfil mais completo.

                        </p>

                    </div>

                    <!-- AVISO PARA PROFISSIONAL -->

                    <?php if (isset($_GET['origem'])): ?>

                        <div class="alert alert-info">

                            Para oferecer seus serviços,
                            você precisa adicionar
                            uma foto de perfil.

                        </div>

                    <?php endif; ?>

                    <!-- MENSAGEM -->

                    <?php if ($mensagem !== ''): ?>

                        <div
                            class="alert alert-<?= e_foto($tipo_mensagem) ?>"
                            role="alert"
                        >
                            <?= e_foto($mensagem) ?>
                        </div>

                    <?php endif; ?>

                    <!-- FOTO -->

                    <div class="text-center mb-4">

                        <?php if ($foto_existe): ?>

                            <img
                                id="fotoAtual"
                                src="<?= e_foto($foto_atual) ?>"
                                alt="Foto atual do perfil"
                                class="perfil-avatar"
                            >

                        <?php else: ?>

                            <div
                                id="fotoVazia"
                                class="perfil-sem-foto"
                            >
                                👤
                            </div>

                        <?php endif; ?>

                        <!-- PRÉVIA DA NOVA FOTO -->

                        <img
                            id="fotoNova"
                            class="perfil-avatar mx-auto"
                            alt="Prévia da nova foto"
                            style="display:none"
                        >

                        <div class="mt-3">

                            <strong style="color:#126E68">

                                <?= $foto_existe
                                    ? 'Sua foto atual'
                                    : 'Nenhuma foto cadastrada' ?>

                            </strong>

                        </div>

                    </div>

                    <!-- FORMULÁRIO -->

                    <form
                        method="POST"
                        action="minha_foto.php"
                        enctype="multipart/form-data"
                    >

                        <input
                            type="hidden"
                            name="token_foto"
                            value="<?= e_foto($_SESSION['token_foto']) ?>"
                        >

                        <div class="mb-3">

                            <label
                                for="foto"
                                class="form-label fw-semibold"
                            >
                                Escolher uma foto
                            </label>

                            <input
                                type="file"
                                id="foto"
                                name="foto"
                                class="form-control"
                                accept="image/jpeg,image/png,image/webp"
                                required
                            >

                            <!-- BOTÃO REMOVER SELEÇÃO -->

                            <button
                                type="button"
                                id="btnRemoverFoto"
                                class="btn btn-outline-danger btn-sm mt-3 d-none"
                            >
                                ✖ Remover foto selecionada
                            </button>

                            <p class="form-text mt-2">

                                Escolha uma imagem JPG, PNG
                                ou WebP com até 3 MB.

                            </p>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-lg perfil-botao w-100"
                        >
                            Salvar foto
                        </button>

                    </form>

                    <!-- LINKS -->

                    <div class="text-center mt-4">

                        <a
                            href="cadastrar_anuncio.php"
                            class="text-decoration-none fw-semibold"
                            style="color:#126E68"
                        >
                            Oferecer meu serviço
                        </a>

                        <div class="mt-2">

                            <a
                                href="painel.php"
                                class="text-secondary"
                            >
                                Voltar ao painel
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

</main>

<!-- JAVASCRIPT -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const campoFoto = document.getElementById('foto');
    const fotoAtual = document.getElementById('fotoAtual');
    const fotoNova = document.getElementById('fotoNova');
    const fotoVazia = document.getElementById('fotoVazia');
    const btnRemover = document.getElementById('btnRemoverFoto');

    let urlTemporaria = null;

    // ==========================================
    // REMOVER NOVA FOTO SELECIONADA
    // ==========================================

    function removerFotoSelecionada() {

        if (urlTemporaria !== null) {
            URL.revokeObjectURL(urlTemporaria);
            urlTemporaria = null;
        }

        // Limpa a escolha.
        campoFoto.value = '';

        // Esconde a nova prévia.
        fotoNova.removeAttribute('src');
        fotoNova.style.display = 'none';

        // Volta a mostrar a foto já salva.
        if (fotoAtual) {
            fotoAtual.style.display = 'inline-block';
        }

        // Se não existe foto salva, volta ao avatar.
        if (fotoVazia) {
            fotoVazia.style.display = 'flex';
        }

        // Esconde o botão de remoção.
        btnRemover.classList.add('d-none');
    }

    // ==========================================
    // ESCOLHER NOVA FOTO
    // ==========================================

    campoFoto.addEventListener('change', function () {

        const arquivo = campoFoto.files[0];

        if (!arquivo) {
            removerFotoSelecionada();
            return;
        }

        const tiposPermitidos = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        const tamanhoMaximo = 3 * 1024 * 1024;

        if (
            !tiposPermitidos.includes(arquivo.type) ||
            arquivo.size > tamanhoMaximo
        ) {

            alert(
                'Escolha uma imagem JPG, PNG ou WebP de até 3 MB.'
            );

            removerFotoSelecionada();
            return;
        }

        if (urlTemporaria !== null) {
            URL.revokeObjectURL(urlTemporaria);
        }

        // Cria a imagem de prévia.
        urlTemporaria = URL.createObjectURL(arquivo);

        fotoNova.src = urlTemporaria;
        fotoNova.style.display = 'block';

        // Esconde a foto antiga temporariamente.
        if (fotoAtual) {
            fotoAtual.style.display = 'none';
        }

        if (fotoVazia) {
            fotoVazia.style.display = 'none';
        }

        // Mostra o botão remover.
        btnRemover.classList.remove('d-none');

    });

    // ==========================================
    // CLIQUE EM REMOVER
    // ==========================================

    btnRemover.addEventListener(
        'click',
        removerFotoSelecionada
    );

});
</script>

</main>

<script src="editor_foto.js?v=2"></script>

<?php include __DIR__ . '/rodape.php'; ?>

<?php include __DIR__ . '/rodape.php'; ?>
