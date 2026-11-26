
<?php
// =====================================================
// LIMPA LAR - OFERECER MEU SERVIÇO
// Exige foto de perfil antes de publicar.
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['ID_USUARIO'])) {
    header('Location: entrar.php');
    exit;
}

require_once __DIR__ . '/conexao.php';

$id_usuario = (int) $_SESSION['ID_USUARIO'];

// =====================================================
// VERIFICAR FOTO DO PROFISSIONAL
// =====================================================

$stmt_foto = mysqli_prepare(
    $conexao,
    'SELECT FOTO FROM USUARIO WHERE ID_USUARIO = ? LIMIT 1'
);

mysqli_stmt_bind_param(
    $stmt_foto,
    'i',
    $id_usuario
);

mysqli_stmt_execute($stmt_foto);

$dados_foto = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt_foto)
);

mysqli_stmt_close($stmt_foto);

$foto_perfil = trim(
    (string) ($dados_foto['FOTO'] ?? '')
);

// Se não tem foto, direciona para o cadastro da imagem.
if (
    !$dados_foto ||
    $foto_perfil === '' ||
    !is_file(__DIR__ . '/' . $foto_perfil)
) {
    header('Location: minha_foto.php?origem=anuncio');
    exit;
}

// =====================================================
// DADOS DO PROFISSIONAL
// =====================================================

$nome_usuario = trim(
    (string) ($_SESSION['NOME_USUARIO'] ?? 'Profissional')
);

$descricao = '';
$erro_formulario = '';

$mensagem_sucesso = (string) (
    $_SESSION['sucesso_anuncio'] ?? ''
);

unset($_SESSION['sucesso_anuncio']);

function escapar_anuncio($valor)
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

if (empty($_SESSION['token_anuncio'])) {
    $_SESSION['token_anuncio'] = bin2hex(
        random_bytes(32)
    );
}

// =====================================================
// PUBLICAR ANÚNCIO
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['token_anuncio'] ?? '';

    $descricao_enviada = $_POST['descricao'] ?? '';

    $descricao = is_string($descricao_enviada)
        ? trim($descricao_enviada)
        : '';

    $tamanho = function_exists('mb_strlen')
        ? mb_strlen($descricao, 'UTF-8')
        : strlen($descricao);

    if (
        !is_string($token) ||
        !hash_equals(
            $_SESSION['token_anuncio'],
            $token
        )
    ) {

        $erro_formulario =
            'O formulário expirou. Atualize a página e tente novamente.';

    } elseif ($descricao === '') {

        $erro_formulario =
            'Conte um pouco sobre sua faxina.';

    } elseif ($tamanho > 1000) {

        $erro_formulario =
            'A descrição pode ter até 1.000 caracteres.';

    } else {

        try {

            // =========================================
            // GARANTIR CATEGORIA FAXINA
            // =========================================

            mysqli_query(
                $conexao,
                "INSERT IGNORE INTO CATEGORIA
                    (NOME, DESCRICAO, ATIVO)
                 VALUES
                    ('Faxina',
                     'Serviços de limpeza residencial.',
                     1)"
            );

            $resultado = mysqli_query(
                $conexao,
                "SELECT ID_CATEGORIA
                 FROM CATEGORIA
                 WHERE NOME = 'Faxina'
                 LIMIT 1"
            );

            $categoria = mysqli_fetch_assoc(
                $resultado
            );

            if (!$categoria) {
                throw new RuntimeException(
                    'Categoria Faxina não encontrada.'
                );
            }

            $id_categoria = (int) (
                $categoria['ID_CATEGORIA']
            );

            // =========================================
            // TÍTULO AUTOMÁTICO
            // =========================================

            $nome_curto = function_exists('mb_substr')
                ? mb_substr(
                    $nome_usuario,
                    0,
                    141,
                    'UTF-8'
                )
                : substr($nome_usuario, 0, 141);

            $titulo = 'Faxina - ' . $nome_curto;

            // =========================================
            // INSERIR ANÚNCIO
            // =========================================

            $sql = "
                INSERT INTO ANUNCIO (
                    ID_USUARIO,
                    ID_CATEGORIA,
                    TITULO,
                    DESCRICAO,
                    PRECO,
                    ESTADO,
                    CIDADE,
                    BAIRRO,
                    STATUS
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    NULL,
                    'SP',
                    'Bauru',
                    NULL,
                    'ATIVO'
                )
            ";

            $stmt = mysqli_prepare(
                $conexao,
                $sql
            );

            mysqli_stmt_bind_param(
                $stmt,
                'iiss',
                $id_usuario,
                $id_categoria,
                $titulo,
                $descricao
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);

            $_SESSION['sucesso_anuncio'] =
                'Seu serviço foi publicado com sucesso!';

            header('Location: cadastrar_anuncio.php');
            exit;

        } catch (Throwable $erro) {

            error_log(
                'LimpaLar - publicar anúncio: ' .
                $erro->getMessage()
            );

            $erro_formulario =
                'Não foi possível publicar o serviço. Confira o banco e tente novamente.';
        }
    }
}

include __DIR__ . '/cabecalho.php';
?>

<section class="container py-5">

    <div class="text-center mb-4">

        <h1
            class="fw-bold"
            style="color:#126E68"
        >
            🧹 Oferecer meu serviço
        </h1>

        <p class="text-secondary">

            Apresente seu trabalho para os clientes
            do LimpaLar em Bauru.

        </p>

    </div>

    <div class="row justify-content-center">

        <div class="col-12 col-lg-8">

            <div class="card border-0 rounded-4 shadow-sm">

                <div class="card-body p-4 p-md-5">

                    <!-- MENSAGEM DE SUCESSO -->

                    <?php if ($mensagem_sucesso !== ''): ?>

                        <div
                            class="alert alert-success"
                            role="status"
                        >

                            <?= escapar_anuncio(
                                $mensagem_sucesso
                            ) ?>

                            <div class="mt-2">

                                <a
                                    href="profissionais.php"
                                    class="alert-link"
                                >
                                    Ver profissionais e anúncios
                                </a>

                            </div>

                        </div>

                    <?php endif; ?>

                    <!-- MENSAGEM DE ERRO -->

                    <?php if ($erro_formulario !== ''): ?>

                        <div
                            class="alert alert-danger"
                            role="alert"
                        >
                            <?= escapar_anuncio(
                                $erro_formulario
                            ) ?>
                        </div>

                    <?php endif; ?>

                    <!-- PROFISSIONAL -->

                    <div class="text-center mb-4">

                        <img
                            src="<?= escapar_anuncio($foto_perfil) ?>"
                            alt="Foto do profissional"
                            class="rounded-circle mb-3"
                            style="
                                width:110px;
                                height:110px;
                                object-fit:cover;
                            "
                        >

                        <h2
                            class="h4 fw-bold"
                            style="color:#126E68"
                        >
                            <?= escapar_anuncio($nome_usuario) ?>
                        </h2>

                        <p class="text-secondary mb-0">
                            Serviço de limpeza residencial
                        </p>

                    </div>

                    <!-- FORMULÁRIO -->

                    <form
                        method="POST"
                        action="cadastrar_anuncio.php"
                    >

                        <input
                            type="hidden"
                            name="token_anuncio"
                            value="<?= escapar_anuncio(
                                $_SESSION['token_anuncio']
                            ) ?>"
                        >

                        <!-- DESCRIÇÃO -->

                        <div class="mb-4">

                            <label
                                for="descricao"
                                class="form-label fw-bold"
                                style="color:#126E68"
                            >
                                Fale sobre sua faxina
                            </label>

                            <textarea
                                id="descricao"
                                name="descricao"
                                rows="6"
                                maxlength="1000"
                                class="form-control"
                                placeholder="Conte quais ambientes você costuma limpar, o que está incluído no serviço e como é seu trabalho..."
                                required><?= escapar_anuncio($descricao) ?></textarea>

                            <div class="form-text">

                                Esse texto será exibido para
                                os clientes na página de
                                profissionais.

                            </div>

                        </div>

                        <!-- LOCAL -->

                        <div
                            class="rounded-4 p-4 mb-4"
                            style="background:#E7F5F1"
                        >

                            <h3
                                class="h5 fw-bold"
                                style="color:#126E68"
                            >
                                📍 Atendimento em Bauru — SP
                            </h3>

                            <p
                                class="mb-0"
                                style="color:#617477"
                            >

                                Você não precisa selecionar bairros.

                                Após aceitar uma solicitação,
                                combine diretamente com o cliente
                                o endereço, o deslocamento
                                e os detalhes do serviço.

                            </p>

                        </div>

                        <!-- VALOR -->

                        <div
                            class="rounded-4 p-4 mb-4"
                            style="background:#E7F5F1"
                        >

                            <h3
                                class="h5 fw-bold"
                                style="color:#126E68"
                            >
                                💬 Valor a combinar
                            </h3>

                            <p
                                class="mb-0"
                                style="color:#617477"
                            >

                                Não é necessário definir um
                                preço agora.

                                O valor será combinado diretamente
                                com o cliente depois que você
                                aceitar a solicitação.

                            </p>

                        </div>

                        <!-- BOTÕES -->

                        <div
                            class="d-flex flex-column flex-sm-row gap-3 justify-content-center"
                        >

                            <a
                                href="painel.php"
                                class="btn btn-outline-secondary btn-lg"
                            >
                                Voltar ao painel
                            </a>

                            <button
                                type="submit"
                                class="btn btn-lg text-white"
                                style="background:#18A999"
                            >
                                🧹 Publicar meu serviço
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</section>

</main>

<?php include __DIR__ . '/rodape.php'; ?>
