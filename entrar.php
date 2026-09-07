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


                        <form method="POST">


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