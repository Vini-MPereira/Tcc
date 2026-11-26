
<?php
// =====================================================
// LIMPA LAR - CADASTRO DE USUÁRIO COM FOTO
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/foto_upload.php';

function e_cadastro($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

if (empty($_SESSION['token_cadastro'])) {
    $_SESSION['token_cadastro'] = bin2hex(random_bytes(32));
}

$nome = '';
$cpf = '';
$email = '';
$telefone = '';

$mensagem = '';
$tipo_mensagem = 'danger';

// =====================================================
// PROCESSAR CADASTRO
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim((string) ($_POST['nome'] ?? ''));

    $cpf = preg_replace(
        '/\D/',
        '',
        (string) ($_POST['cpf'] ?? '')
    );

    $email = trim((string) ($_POST['email'] ?? ''));
    $telefone = trim((string) ($_POST['telefone'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmar = (string) ($_POST['confirmar_senha'] ?? '');
    $token = $_POST['token_cadastro'] ?? '';

    try {

        // =============================================
        // VALIDAR FORMULÁRIO
        // =============================================

        if (
            !is_string($token) ||
            !hash_equals($_SESSION['token_cadastro'], $token)
        ) {
            throw new RuntimeException(
                'O formulário expirou. Atualize a página.'
            );
        }

        if (
            $nome === '' ||
            $cpf === '' ||
            $email === '' ||
            $telefone === '' ||
            $senha === '' ||
            $confirmar === ''
        ) {
            throw new RuntimeException(
                'Preencha todos os campos obrigatórios.'
            );
        }

        $tamanho_nome = function_exists('mb_strlen')
            ? mb_strlen($nome, 'UTF-8')
            : strlen($nome);

        if (
            $tamanho_nome > 150 ||
            strlen($email) > 150 ||
            strlen($telefone) > 20
        ) {
            throw new RuntimeException(
                'Confira o tamanho dos dados informados.'
            );
        }

        if (strlen($cpf) !== 11) {
            throw new RuntimeException(
                'Informe um CPF com 11 números.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(
                'Informe um e-mail válido.'
            );
        }

        if (strlen($senha) < 6) {
            throw new RuntimeException(
                'A senha deve ter pelo menos 6 caracteres.'
            );
        }

        if ($senha !== $confirmar) {
            throw new RuntimeException(
                'As senhas não coincidem.'
            );
        }

        // =============================================
        // VERIFICAR USUÁRIO EXISTENTE
        // =============================================

        $stmt = mysqli_prepare(
            $conexao,
            "SELECT ID_USUARIO
             FROM USUARIO
             WHERE EMAIL = ? OR CPF = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $stmt,
            'ss',
            $email,
            $cpf
        );

        mysqli_stmt_execute($stmt);

        $resultado = mysqli_stmt_get_result($stmt);
        $existe = mysqli_num_rows($resultado) > 0;

        mysqli_stmt_close($stmt);

        if ($existe) {
            throw new RuntimeException(
                'Este e-mail ou CPF já está cadastrado.'
            );
        }

        // =============================================
        // SALVAR FOTO OPCIONAL
        // =============================================

        $foto = null;

        if (
            isset($_FILES['foto']) &&
            $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $foto = salvar_foto_perfil($_FILES['foto']);
        }

        // =============================================
        // CADASTRAR USUÁRIO
        // =============================================

        try {

            $senha_hash = password_hash(
                $senha,
                PASSWORD_DEFAULT
            );

            $stmt = mysqli_prepare(
                $conexao,
                "INSERT INTO USUARIO
                (NOME, CPF, TELEFONE, EMAIL, SENHA, FOTO)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                'ssssss',
                $nome,
                $cpf,
                $telefone,
                $email,
                $senha_hash,
                $foto
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

        } catch (Throwable $erro) {

            if ($foto !== null) {
                apagar_foto_perfil_local($foto);
            }

            throw $erro;
        }

        $mensagem =
            'Conta criada com sucesso! Agora você pode entrar.';

        $tipo_mensagem = 'success';

        $nome = '';
        $cpf = '';
        $email = '';
        $telefone = '';

        $_SESSION['token_cadastro'] = bin2hex(
            random_bytes(32)
        );

    } catch (Throwable $erro) {

        if ($erro instanceof RuntimeException) {

            $mensagem = $erro->getMessage();

        } else {

            error_log(
                'Erro no cadastro LimpaLar: ' .
                $erro->getMessage()
            );

            $mensagem =
                'Não foi possível criar a conta. Tente novamente.';
        }
    }
}

include __DIR__ . '/cabecalho.php';
?>

<style>
    .cadastro-foto {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #E7F5F1;
    }

    .cadastro-avatar {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        background: #E7F5F1;
        border: 4px solid #D5EDE7;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: auto;
        font-size: 58px;
    }

    .cadastro-titulo {
        color: #126E68;
    }

    .cadastro-botao {
        background: #18A999;
        border-color: #18A999;
        color: #FFFFFF;
    }

    .cadastro-botao:hover {
        background: #126E68;
        border-color: #126E68;
        color: #FFFFFF;
    }
</style>

<section class="container py-5">

    <div class="row justify-content-center">

        <div class="col-12 col-md-10 col-lg-7">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body p-4 p-md-5">

                    <!-- CABEÇALHO -->

                    <div class="text-center mb-4">

                        <h1 class="fw-bold cadastro-titulo">
                            Criar uma conta
                        </h1>

                        <p class="text-secondary">
                            Cadastre-se no LimpaLar e encontre
                            ou ofereça serviços de faxina.
                        </p>

                    </div>

                    <!-- MENSAGENS -->

                    <?php if ($mensagem !== ''): ?>

                        <div
                            class="alert alert-<?= e_cadastro($tipo_mensagem) ?>"
                            role="alert"
                        >

                            <?= e_cadastro($mensagem) ?>

                            <?php if ($tipo_mensagem === 'success'): ?>

                                <div class="mt-2">

                                    <a
                                        href="entrar.php"
                                        class="alert-link"
                                    >
                                        Entrar na minha conta
                                    </a>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                    <!-- FORMULÁRIO -->

                    <form
                        method="POST"
                        action="cadastrar.php"
                        enctype="multipart/form-data"
                    >

                        <input
                            type="hidden"
                            name="token_cadastro"
                            value="<?= e_cadastro($_SESSION['token_cadastro']) ?>"
                        >

                        <!-- FOTO DE PERFIL -->

                        <div class="mb-4 text-center">

                            <div
                                id="previewVazio"
                                class="cadastro-avatar"
                            >
                                👤
                            </div>

                            <img
                                id="previewFoto"
                                class="cadastro-foto mx-auto"
                                alt="Prévia da foto de perfil"
                                style="display:none"
                            >

                            <h2
                                class="h5 fw-bold mt-3 mb-2"
                                style="color:#126E68"
                            >
                                Foto de perfil
                            </h2>

                            <p class="small text-secondary">

                                Adicione uma foto para deixar
                                seu perfil mais completo.

                            </p>

                            <input
                                type="file"
                                id="foto"
                                name="foto"
                                class="form-control"
                                accept="image/jpeg,image/png,image/webp"
                            >

                            <!-- REMOVER FOTO ESCOLHIDA -->

                            <button
                                type="button"
                                id="btnRemoverFoto"
                                class="btn btn-outline-danger btn-sm mt-3 d-none"
                            >
                                ✖ Remover foto selecionada
                            </button>

                            <p class="form-text mt-2">

                                JPG, PNG ou WebP. Máximo de 3 MB.
                                A foto é opcional para criar a conta,
                                mas obrigatória para publicar faxinas.

                            </p>

                        </div>

                        <hr class="my-4">

                        <!-- NOME -->

                        <div class="mb-3">

                            <label
                                for="nome"
                                class="form-label fw-semibold"
                            >
                                Nome completo
                            </label>

                            <input
                                type="text"
                                id="nome"
                                name="nome"
                                class="form-control form-control-lg"
                                maxlength="150"
                                placeholder="Digite seu nome completo"
                                value="<?= e_cadastro($nome) ?>"
                                required
                            >

                        </div>

                        <!-- CPF -->

                        <div class="mb-3">

                            <label
                                for="cpf"
                                class="form-label fw-semibold"
                            >
                                CPF
                            </label>

                            <input
                                type="text"
                                id="cpf"
                                name="cpf"
                                class="form-control form-control-lg"
                                maxlength="14"
                                placeholder="000.000.000-00"
                                value="<?= e_cadastro($cpf) ?>"
                                required
                            >

                        </div>

                        <!-- E-MAIL -->

                        <div class="mb-3">

                            <label
                                for="email"
                                class="form-label fw-semibold"
                            >
                                E-mail
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control form-control-lg"
                                maxlength="150"
                                placeholder="Digite seu e-mail"
                                value="<?= e_cadastro($email) ?>"
                                required
                            >

                        </div>

                        <!-- TELEFONE -->

                        <div class="mb-3">

                            <label
                                for="telefone"
                                class="form-label fw-semibold"
                            >
                                Telefone
                            </label>

                            <input
                                type="tel"
                                id="telefone"
                                name="telefone"
                                class="form-control form-control-lg"
                                maxlength="20"
                                placeholder="(14) 99999-9999"
                                value="<?= e_cadastro($telefone) ?>"
                                required
                            >

                        </div>

                        <!-- SENHA -->

                        <div class="mb-3">

                            <label
                                for="senha"
                                class="form-label fw-semibold"
                            >
                                Senha
                            </label>

                            <input
                                type="password"
                                id="senha"
                                name="senha"
                                class="form-control form-control-lg"
                                minlength="6"
                                placeholder="Digite sua senha"
                                required
                            >

                        </div>

                        <!-- CONFIRMAR SENHA -->

                        <div class="mb-4">

                            <label
                                for="confirmar_senha"
                                class="form-label fw-semibold"
                            >
                                Confirmar senha
                            </label>

                            <input
                                type="password"
                                id="confirmar_senha"
                                name="confirmar_senha"
                                class="form-control form-control-lg"
                                minlength="6"
                                placeholder="Digite a senha novamente"
                                required
                            >

                        </div>

                        <!-- CADASTRAR -->

                        <button
                            type="submit"
                            class="btn btn-lg cadastro-botao w-100"
                        >
                            Criar minha conta
                        </button>

                    </form>

                    <!-- LOGIN -->

                    <div class="text-center mt-4">

                        <p class="text-secondary mb-1">
                            Já possui uma conta?
                        </p>

                        <a
                            href="entrar.php"
                            class="fw-semibold text-decoration-none"
                            style="color:#126E68"
                        >
                            Entrar na minha conta
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

</main>

<!-- JAVASCRIPT DA FOTO -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    const campoFoto = document.getElementById('foto');
    const previewFoto = document.getElementById('previewFoto');
    const previewVazio = document.getElementById('previewVazio');
    const btnRemover = document.getElementById('btnRemoverFoto');

    let urlTemporaria = null;

    // ==========================================
    // LIMPAR FOTO ESCOLHIDA
    // ==========================================

    function removerFoto() {

        if (urlTemporaria !== null) {
            URL.revokeObjectURL(urlTemporaria);
            urlTemporaria = null;
        }

        campoFoto.value = '';

        previewFoto.removeAttribute('src');
        previewFoto.style.display = 'none';

        previewVazio.style.display = 'flex';

        btnRemover.classList.add('d-none');
    }

    // ==========================================
    // ESCOLHER FOTO
    // ==========================================

    campoFoto.addEventListener('change', function () {

        const arquivo = campoFoto.files[0];

        if (!arquivo) {
            removerFoto();
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
                'Escolha uma foto JPG, PNG ou WebP de até 3 MB.'
            );

            removerFoto();
            return;
        }

        if (urlTemporaria !== null) {
            URL.revokeObjectURL(urlTemporaria);
        }

        urlTemporaria = URL.createObjectURL(arquivo);

        previewFoto.src = urlTemporaria;

        previewFoto.style.display = 'block';
        previewVazio.style.display = 'none';

        btnRemover.classList.remove('d-none');

    });

    // ==========================================
    // BOTÃO REMOVER
    // ==========================================

    btnRemover.addEventListener('click', removerFoto);

});
</script>

</main>

<script src="editor_foto.js?v=2"></script>

<?php include __DIR__ . '/rodape.php'; ?>


<?php include __DIR__ . '/rodape.php'; ?>
