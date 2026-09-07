<?php include "cabecalho.php"; ?>


<main>


    <!-- =========================================
         TÍTULO
    ========================================== -->

    <section class="container py-5">

        <div class="text-center">

            <h1
                class="fw-bold"
                style="color: #315F6B;">

                Encontre um profissional

            </h1>

            <p
                class="lead text-secondary">

                Escolha um profissional de limpeza
                para cuidar da sua casa.

            </p>

        </div>

    </section>



    <!-- =========================================
         CARDS
    ========================================== -->

    <section class="container pb-5">

        <div
            class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">


            <!-- PROFISSIONAL 1 -->

            <div class="col">

                <div
                    class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">

                    <img
                        src="img/inicio.jfif"
                        class="card-img-top"
                        style="height: 230px; object-fit: cover;"
                        alt="Profissional">

                    <div class="card-body d-flex flex-column">


                        <div
                            class="d-flex justify-content-between align-items-center mb-2">

                            <h5 class="fw-bold mb-0">

                                Maria Oliveira

                            </h5>

                            <span
                                class="text-warning fw-bold">

                                ★ 4.8

                            </span>

                        </div>


                        <p class="text-secondary">

                            Profissional especializada em
                            limpeza residencial.

                        </p>


                        <button
                            class="btn mt-auto text-white"
                            style="background-color: #3F7C8C;"
                            data-bs-toggle="modal"
                            data-bs-target="#modalAgendamento">

                            Agendar

                        </button>


                    </div>

                </div>

            </div>



            <!-- PROFISSIONAL 2 -->

            <div class="col">

                <div
                    class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">

                    <img
                        src="img/inicio.jfif"
                        class="card-img-top"
                        style="height: 230px; object-fit: cover;"
                        alt="Profissional">

                    <div class="card-body d-flex flex-column">


                        <div
                            class="d-flex justify-content-between align-items-center mb-2">

                            <h5 class="fw-bold mb-0">

                                João Pereira

                            </h5>

                            <span
                                class="text-warning fw-bold">

                                ★ 4.9

                            </span>

                        </div>


                        <p class="text-secondary">

                            Profissional com experiência
                            em limpeza e organização.

                        </p>


                        <button
                            class="btn mt-auto text-white"
                            style="background-color: #3F7C8C;"
                            data-bs-toggle="modal"
                            data-bs-target="#modalAgendamento">

                            Agendar

                        </button>


                    </div>

                </div>

            </div>



            <!-- PROFISSIONAL 3 -->

            <div class="col">

                <div
                    class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">

                    <img
                        src="img/inicio.jfif"
                        class="card-img-top"
                        style="height: 230px; object-fit: cover;"
                        alt="Profissional">

                    <div class="card-body d-flex flex-column">


                        <div
                            class="d-flex justify-content-between align-items-center mb-2">

                            <h5 class="fw-bold mb-0">

                                Ana Santos

                            </h5>

                            <span
                                class="text-warning fw-bold">

                                ★ 5.0

                            </span>

                        </div>


                        <p class="text-secondary">

                            Profissional dedicada à limpeza
                            e conservação residencial.

                        </p>


                        <button
                            class="btn mt-auto text-white"
                            style="background-color: #3F7C8C;"
                            data-bs-toggle="modal"
                            data-bs-target="#modalAgendamento">

                            Agendar

                        </button>


                    </div>

                </div>

            </div>



            <!-- PROFISSIONAL 4 -->

            <div class="col">

                <div
                    class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">

                    <img
                        src="img/inicio.jfif"
                        class="card-img-top"
                        style="height: 230px; object-fit: cover;"
                        alt="Profissional">

                    <div class="card-body d-flex flex-column">


                        <div
                            class="d-flex justify-content-between align-items-center mb-2">

                            <h5 class="fw-bold mb-0">

                                Carlos Souza

                            </h5>

                            <span
                                class="text-warning fw-bold">

                                ★ 4.7

                            </span>

                        </div>


                        <p class="text-secondary">

                            Profissional especializado em
                            limpeza residencial.

                        </p>


                        <button
                            class="btn mt-auto text-white"
                            style="background-color: #3F7C8C;"
                            data-bs-toggle="modal"
                            data-bs-target="#modalAgendamento">

                            Agendar

                        </button>


                    </div>

                </div>

            </div>



        </div>

    </section>



    <!-- =========================================
         MODAL DE AGENDAMENTO
    ========================================== -->

    <div
        class="modal fade"
        id="modalAgendamento"
        tabindex="-1">

        <div
            class="modal-dialog modal-dialog-centered">

            <div
                class="modal-content border-0 rounded-4">


                <div class="modal-header">

                    <h5
                        class="modal-title fw-bold"
                        style="color: #315F6B;">

                        Agendar serviço

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">

                    </button>

                </div>


                <div class="modal-body">


                    <p class="text-secondary">

                        Aqui futuramente será exibida
                        a agenda do profissional.

                    </p>


                    <div class="mb-3">

                        <label
                            class="form-label fw-semibold">

                            Data

                        </label>

                        <input
                            type="date"
                            class="form-control">

                    </div>


                    <div class="mb-3">

                        <label
                            class="form-label fw-semibold">

                            Horário

                        </label>

                        <select class="form-select">

                            <option>
                                Selecione um horário
                            </option>

                            <option>
                                08:00
                            </option>

                            <option>
                                10:00
                            </option>

                            <option>
                                14:00
                            </option>

                            <option>
                                16:00
                            </option>

                        </select>

                    </div>


                    <div
                        class="p-3 rounded-3"
                        style="background-color: #EAF3F5;">

                        <strong
                            style="color: #315F6B;">

                            Telefone do profissional

                        </strong>

                        <br>

                        <span class="text-secondary">

                            (14) 99999-9999

                        </span>

                    </div>


                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Cancelar

                    </button>


                    <button
                        type="button"
                        class="btn text-white"
                        style="background-color: #3F7C8C;">

                        Confirmar agendamento

                    </button>

                </div>


            </div>

        </div>

    </div>


</main>


<?php include "rodape.php"; ?>