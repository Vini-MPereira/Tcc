<?php

session_start();

include "conexao.php";

$mensagem = "";
$tipo_mensagem = "";


// =====================================================
// VERIFICA SE O USUÁRIO JÁ ESTÁ LOGADO
// =====================================================

if (isset($_SESSION["ID_USUARIO"])) {

    header("Location: painel.php");
    exit;

}


// =====================================================
// PROCESSA O LOGIN
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";


    // =================================================
    // VALIDA OS CAMPOS
    // =================================================

    if (empty($email) || empty($senha)) {

        $mensagem = "Preencha o e-mail e a senha.";
        $tipo_mensagem = "danger";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensagem = "Digite um e-mail válido.";
        $tipo_mensagem = "danger";

    } else {


        // =============================================
        // BUSCA O USUÁRIO
        // =============================================

        $sql = "SELECT
                    ID_USUARIO,
                    NOME,
                    EMAIL,
                    SENHA,
                    ATIVO
                FROM USUARIO
                WHERE EMAIL = ?
                LIMIT 1";


        $stmt = mysqli_prepare($conexao, $sql);


        if (!$stmt) {

            $mensagem = "Erro ao preparar a consulta.";
            $tipo_mensagem = "danger";

        } else {


            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );


            mysqli_stmt_execute($stmt);


            $resultado = mysqli_stmt_get_result($stmt);


            // =========================================
            // VERIFICA SE ENCONTROU O USUÁRIO
            // =========================================

            if (mysqli_num_rows($resultado) === 1) {


                $usuario = mysqli_fetch_assoc($resultado);


                // =====================================
                // VERIFICA SE A CONTA ESTÁ ATIVA
                // =====================================

                if (!$usuario["ATIVO"]) {

                    $mensagem = "Sua conta está desativada.";
                    $tipo_mensagem = "warning";

                }


                // =====================================
                // VERIFICA A SENHA
                // =====================================

                elseif (password_verify($senha, $usuario["SENHA"])) {


                    // =================================
                    // CRIA UMA NOVA SESSÃO
                    // =================================

                    session_regenerate_id(true);


                    $_SESSION["ID_USUARIO"] = $usuario["ID_USUARIO"];

                    $_SESSION["NOME_USUARIO"] = $usuario["NOME"];

                    $_SESSION["EMAIL_USUARIO"] = $usuario["EMAIL"];


                    // =================================
                    // REDIRECIONA PARA O PAINEL
                    // =================================

                    header("Location: painel.php");
                    exit;


                } else {

                    $mensagem = "E-mail ou senha incorretos.";
                    $tipo_mensagem = "danger";

                }


            } else {

                $mensagem = "E-mail ou senha incorretos.";
                $tipo_mensagem = "danger";

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

            <div class="col-12 col-sm-10 col-md-7 col-lg-5">


                <div
                    class="card border-0 shadow-sm rounded-4">

                    <div class="card-body p-4 p-md-5">


                        <div class="text-center mb-4">

                            <h1
                                class="fw-bold"
                                style="color: #315F6B;">

                                Entrar

                            </h1>

                            <p class="text-secondary">

                                Acesse sua conta do LimpaLar.

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


                            <!-- SENHA -->

                            <div class="mb-4">

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


                            <!-- BOTÃO -->

                            <div class="d-grid">

                                <button
                                    type="submit"
                                    class="btn btn-lg text-white"
                                    style="background-color: #3F7C8C;">

                                    Entrar

                                </button>

                            </div>


                        </form>


                        <div class="text-center mt-4">

                            <p class="text-secondary mb-1">

                                Ainda não possui uma conta?

                            </p>

                            <a
                                href="cadastrar.php"
                                class="text-decoration-none fw-semibold"
                                style="color: #3F7C8C;">

                                Criar uma conta

                            </a>

                        </div>


                    </div>

                </div>


            </div>

        </div>

    </section>

</main>


<?php include "rodape.php"; ?>
