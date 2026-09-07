<?php include "cabecalho.php"; ?>


<main>

    <section class="container py-5">

        <div class="row justify-content-center">

            <div class="col-12 col-sm-10 col-md-8 col-lg-6">


                <div
                    class="card border-0 shadow-sm rounded-4">

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


                        <form method="POST">


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