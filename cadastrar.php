<?php

include "conexao.php";

$mensagem = "";
$tipo_mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // =====================================================
    // RECEBE OS DADOS
    // =====================================================

    $nome = trim($_POST["nome"] ?? "");
    $cpf = trim($_POST["cpf"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmar_senha = $_POST["confirmar_senha"] ?? "";


    // =====================================================
    // VALIDAÇÕES
    // =====================================================

    if (
        empty($nome) ||
        empty($cpf) ||
        empty($email) ||
        empty($telefone) ||
        empty($senha) ||
        empty($confirmar_senha)
    ) {

        $mensagem = "Preencha todos os campos.";
        $tipo_mensagem = "danger";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensagem = "Digite um e-mail válido.";
        $tipo_mensagem = "danger";

    } elseif ($senha !== $confirmar_senha) {

        $mensagem = "As senhas não são iguais.";
        $tipo_mensagem = "danger";

    } elseif (strlen($senha) < 6) {

        $mensagem = "A senha deve ter pelo menos 6 caracteres.";
        $tipo_mensagem = "danger";

    } else {

        // =================================================
        // LIMPA CPF
        // =================================================

        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) !== 11) {

            $mensagem = "Digite um CPF válido.";
            $tipo_mensagem = "danger";

        } else {

            // =================================================
            // VERIFICA SE EMAIL OU CPF JÁ EXISTEM
            // =================================================

            $sql = "SELECT ID_USUARIO
                    FROM USUARIO
                    WHERE EMAIL = ?
                    OR CPF = ?
                    LIMIT 1";

            $stmt = mysqli_prepare($conexao, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "ss",
                $email,
                $cpf
            );

            mysqli_stmt_execute($stmt);

            $resultado = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($resultado) > 0) {

                $mensagem = "Este e-mail ou CPF já está cadastrado.";
                $tipo_mensagem = "warning";

            } else {

                // =============================================
                // CRIPTOGRAFA A SENHA
                // =============================================

                $senha_hash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );


                // =============================================
                // CADASTRA O USUÁRIO
                // =============================================

                $sql = "INSERT INTO USUARIO (
                            NOME,
                            CPF,
                            TELEFONE,
                            EMAIL,
                            SENHA
                        )
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($conexao, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssss",
                    $nome,
                    $cpf,
                    $telefone,
                    $email,
                    $senha_hash
                );


                if (mysqli_stmt_execute($stmt)) {

                    $mensagem = "Conta criada com sucesso! Você já pode entrar.";
                    $tipo_mensagem = "success";

                    // Limpa os campos depois do cadastro
                    $nome = "";
                    $cpf = "";
                    $email = "";
                    $telefone = "";

                } else {

                    $mensagem = "Erro ao criar a conta. Tente novamente.";
                    $tipo_mensagem = "danger";
                }
            }

            mysqli_stmt_close($stmt);
        }
    }
}

?>

<?php include "cabecalho.php"; ?>


<main>

    <section class="container py-5">

        <div class="row justify-content-center">

            <div class="col-12 col-sm-10 col-md-8 col-lg-6">

                <div class="card border-0 shadow-sm rounded-4">

                    <div class="card-body p-4 p-md-5">

                        <div class="text-center mb-4">

                            <h1
                                class="fw-bold"
                                style="color: #315F6B;">

                                Criar uma conta

                            </h1>

                            <p class="text-secondary">

                                Cadastre-se no LimpaLar.

                            </p>

                        </div>


                        <?php if (!empty($mensagem)) { ?>

                            <div
                                class="alert alert-<?php echo $tipo_mensagem; ?>"
                                role="alert">

                                <?php echo htmlspecialchars($mensagem); ?>

                            </div>

                        <?php } ?>


                        <form method="POST" action="">


                            <!-- NOME -->

                            <div class="mb-3">

                                <label
                                    for="nome"
                                    class="form-label fw-semibold">

                                    Nome completo

                                </label>

                                <input
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    class="form-control form-control-lg"
                                    placeholder="Digite seu nome completo"
                                    value="<?php echo htmlspecialchars($nome ?? ''); ?>"
                                    required>

                            </div>


                            <!-- CPF -->

                            <div class="mb-3">

                                <label
                                    for="cpf"
                                    class="form-label fw-semibold">

                                    CPF

                                </label>

                                <input
                                    type="text"
                                    id="cpf"
                                    name="cpf"
                                    class="form-control form-control-lg"
                                    placeholder="000.000.000-00"
                                    maxlength="14"
                                    value="<?php echo htmlspecialchars($cpf ?? ''); ?>"
                                    required>

                            </div>


                            <!-- EMAIL -->

                            <div class="mb-3">

                                <label
                                    for="email"
                                    class="form-label fw-semibold">

                                    E-mail

                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="form-control form-control-lg"
                                    placeholder="Digite seu e-mail"
                                    value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                    required>

                            </div>


                            <!-- TELEFONE -->

                            <div class="mb-3">

                                <label
                                    for="telefone"
                                    class="form-label fw-semibold">

                                    Telefone

                                </label>

                                <input
                                    type="tel"
                                    id="telefone"
                                    name="telefone"
                                    class="form-control form-control-lg"
                                    placeholder="(14) 99999-9999"
                                    value="<?php echo htmlspecialchars($telefone ?? ''); ?>"
                                    required>

                            </div>


                            <!-- SENHA -->

                            <div class="mb-3">

                                <label
                                    for="senha"
                                    class="form-label fw-semibold">

                                    Senha

                                </label>

                                <input
                                    type="password"
                                    id="senha"
                                    name="senha"
                                    class="form-control form-control-lg"
                                    placeholder="Digite sua senha"
                                    required>

                            </div>


                            <!-- CONFIRMAÇÃO -->

                            <div class="mb-4">

                                <label
                                    for="confirmar_senha"
                                    class="form-label fw-semibold">

                                    Confirmar senha

                                </label>

                                <input
                                    type="password"
                                    id="confirmar_senha"
                                    name="confirmar_senha"
                                    class="form-control form-control-lg"
                                    placeholder="Digite a senha novamente"
                                    required>

                            </div>


                            <!-- BOTÃO -->

                            <div class="d-grid">

                                <button
                                    type="submit"
                                    class="btn btn-lg text-white"
                                    style="background-color: #3F7C8C;">

                                    Criar minha conta

                                </button>

                            </div>


                        </form>


                        <div class="text-center mt-4">

                            <p class="text-secondary mb-1">

                                Já possui uma conta?

                            </p>

                            <a
                                href="entrar.php"
                                class="text-decoration-none fw-semibold"
                                style="color: #3F7C8C;">

                                Entrar na minha conta

                            </a>

                        </div>


                    </div>

                </div>

            </div>

        </div>

    </section>

</main>


<?php include "rodape.php"; ?>
