
<?php
// =====================================================
// LIMPA LAR - PÁGINA INICIAL
// =====================================================
// cabecalho.php já abre a tag <main>.

include __DIR__ . '/cabecalho.php';
?>

<style>
    .inicio-limpalar {
        --inicio-verde: #126E68;
        --inicio-destaque: #18A999;
        --inicio-suave: #E7F5F1;
        --inicio-texto: #243438;
        --inicio-muted: #617477;
    }

    .inicio-limpalar .texto-verde {
        color: var(--inicio-verde);
    }

    .inicio-limpalar .texto-suave {
        color: var(--inicio-muted);
    }

    .inicio-limpalar .btn-verde {
        background: var(--inicio-destaque);
        border: 1px solid var(--inicio-destaque);
        color: #fff;
        border-radius: 12px;
        font-weight: 600;
    }

    .inicio-limpalar .btn-verde:hover,
    .inicio-limpalar .btn-verde:focus-visible {
        background: var(--inicio-verde);
        border-color: var(--inicio-verde);
        color: #fff;
    }

    .inicio-limpalar .btn-verde-contorno {
        background: transparent;
        border: 1px solid var(--inicio-verde);
        color: var(--inicio-verde);
        border-radius: 12px;
        font-weight: 600;
    }

    .inicio-limpalar .btn-verde-contorno:hover {
        background: var(--inicio-suave);
        color: var(--inicio-verde);
    }

    .inicio-limpalar .imagem-principal {
        width: 100%;
        height: 420px;
        object-fit: cover;
        border-radius: 24px;
    }

    .inicio-limpalar .secao-suave {
        background: var(--inicio-suave);
    }

    .inicio-limpalar .card-etapa,
    .inicio-limpalar .card-publico,
    .inicio-limpalar .card-galeria {
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(18, 110, 104, .07);
    }

    .inicio-limpalar .numero-etapa {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        width: 54px;
        height: 54px;
        border-radius: 16px;
        background: var(--inicio-suave);
        color: var(--inicio-verde);
        font-size: 1.4rem;
        font-weight: 800;
    }

    .inicio-limpalar .imagem-galeria {
        width: 100%;
        height: 210px;
        object-fit: cover;
    }

    .inicio-limpalar .chamada-final {
        background: var(--inicio-verde);
        color: #fff;
        border-radius: 22px;
    }

    .inicio-limpalar .chamada-final p {
        color: #E7F5F1;
    }

    @media (max-width: 767.98px) {
        .inicio-limpalar .imagem-principal {
            height: 280px;
        }
    }
</style>

<div class="inicio-limpalar">

    <!-- =========================================
         APRESENTAÇÃO DO SITE
    ========================================== -->

    <section
        class="container py-5"
        aria-labelledby="titulo-inicio">

        <div class="row align-items-center g-4 g-lg-5">

            <!-- TEXTO PRINCIPAL -->

            <div class="col-12 col-lg-6 text-center text-lg-start">

                <h1
                    id="titulo-inicio"
                    class="display-5 fw-bold texto-verde mb-3">

                    Sua casa em boas mãos,
                    sua rotina mais leve.

                </h1>

                <p
                    class="lead mb-3"
                    style="color:#36494D;">

                    No LimpaLar, você encontra profissionais
                    de limpeza, escolhe uma data e envia
                    sua solicitação de faxina.

                </p>

                <p class="texto-suave mb-4">

                    Após a aceitação, cliente e profissional
                    podem conversar diretamente para combinar
                    os detalhes e o valor do serviço.

                </p>

                <!-- BOTÕES PRINCIPAIS -->

                <div
                    class="d-flex flex-wrap gap-3
                           justify-content-center
                           justify-content-lg-start">

                    <a
                        href="profissionais.php"
                        class="btn btn-verde btn-lg px-4 py-3">

                        🔎 Encontrar profissionais

                    </a>

                    <a
                        href="cadastrar_anuncio.php"
                        class="btn btn-verde-contorno btn-lg px-4 py-3">

                        🧹 Oferecer meu serviço

                    </a>

                </div>

            </div>

            <!-- IMAGEM PRINCIPAL -->

            <div class="col-12 col-lg-6">

                <img
                    src="img/inicio.jfif"
                    class="imagem-principal shadow-sm"
                    alt="Imagem ilustrativa de um ambiente residencial limpo">

            </div>

        </div>

    </section>


    <!-- =========================================
         COMO FUNCIONA
    ========================================== -->

    <section
        class="secao-suave py-5"
        aria-labelledby="titulo-como-funciona">

        <div class="container py-2">

            <div class="text-center mb-5">

                <h2
                    id="titulo-como-funciona"
                    class="fw-bold texto-verde">

                    Como funciona o LimpaLar?

                </h2>

                <p class="texto-suave mb-0">

                    Da escolha do profissional
                    até a avaliação do atendimento.

                </p>

            </div>

            <div class="row g-4">

                <!-- ETAPA 1 -->

                <div class="col-12 col-md-6 col-xl-3">

                    <article class="card card-etapa h-100">

                        <div class="card-body p-4">

                            <span class="numero-etapa mb-3">
                                1
                            </span>

                            <h3 class="h5 fw-bold">
                                Encontre
                            </h3>

                            <p class="texto-suave mb-0">

                                Consulte profissionais,
                                descrições e avaliações
                                disponíveis.

                            </p>

                        </div>

                    </article>

                </div>

                <!-- ETAPA 2 -->

                <div class="col-12 col-md-6 col-xl-3">

                    <article class="card card-etapa h-100">

                        <div class="card-body p-4">

                            <span class="numero-etapa mb-3">
                                2
                            </span>

                            <h3 class="h5 fw-bold">
                                Solicite
                            </h3>

                            <p class="texto-suave mb-0">

                                Escolha data, horário e
                                informe o endereço da faxina.

                            </p>

                        </div>

                    </article>

                </div>

                <!-- ETAPA 3 -->

                <div class="col-12 col-md-6 col-xl-3">

                    <article class="card card-etapa h-100">

                        <div class="card-body p-4">

                            <span class="numero-etapa mb-3">
                                3
                            </span>

                            <h3 class="h5 fw-bold">
                                Combine
                            </h3>

                            <p class="texto-suave mb-0">

                                O profissional aceita ou
                                recusa. Se aceitar, vocês
                                combinam valor e detalhes
                                diretamente.

                            </p>

                        </div>

                    </article>

                </div>

                <!-- ETAPA 4 -->

                <div class="col-12 col-md-6 col-xl-3">

                    <article class="card card-etapa h-100">

                        <div class="card-body p-4">

                            <span class="numero-etapa mb-3">
                                4
                            </span>

                            <h3 class="h5 fw-bold">
                                Avalie
                            </h3>

                            <p class="texto-suave mb-0">

                                Após a finalização da
                                faxina pelo profissional,
                                o cliente avalia o atendimento.

                            </p>

                        </div>

                    </article>

                </div>

            </div>

        </div>

    </section>


    <!-- =========================================
         PARA CLIENTES E PROFISSIONAIS
    ========================================== -->

    <section
        class="container py-5"
        aria-labelledby="titulo-para-voce">

        <div class="text-center mb-4">

            <h2
                id="titulo-para-voce"
                class="fw-bold texto-verde">

                O LimpaLar é para você

            </h2>

            <p class="texto-suave">

                Escolha o que deseja fazer
                na plataforma.

            </p>

        </div>

        <div class="row g-4">

            <!-- CLIENTES -->

            <div class="col-12 col-md-6">

                <div class="card card-publico h-100">

                    <div class="card-body p-4 p-lg-5">

                        <div class="fs-1 mb-2" aria-hidden="true">
                            🏠
                        </div>

                        <h3 class="h4 fw-bold texto-verde">

                            Preciso de uma faxina

                        </h3>

                        <p class="texto-suave">

                            Veja perfis e horários
                            e envie um pedido para
                            um profissional.

                        </p>

                        <a
                            href="profissionais.php"
                            class="btn btn-verde px-4">

                            Ver profissionais

                        </a>

                    </div>

                </div>

            </div>

            <!-- PROFISSIONAIS -->

            <div class="col-12 col-md-6">

                <div class="card card-publico h-100">

                    <div class="card-body p-4 p-lg-5">

                        <div class="fs-1 mb-2" aria-hidden="true">
                            🧹
                        </div>

                        <h3 class="h4 fw-bold texto-verde">

                            Quero oferecer meus serviços

                        </h3>

                        <p class="texto-suave">

                            Publique seu anúncio,
                            informe sua disponibilidade
                            e receba solicitações.

                        </p>

                        <a
                            href="cadastrar_anuncio.php"
                            class="btn btn-verde-contorno px-4">

                            Publicar meu serviço

                        </a>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =========================================
         GALERIA ILUSTRATIVA
    ========================================== -->

    <section
        class="container pb-5"
        aria-labelledby="titulo-cuidados">

        <div class="text-center mb-4">

            <h2
                id="titulo-cuidados"
                class="fw-bold texto-verde">

                Limpeza e cuidado com sua casa

            </h2>

            <p class="texto-suave mb-0">

                Imagens ilustrativas de cuidados
                com ambientes residenciais.

            </p>

        </div>

        <div class="row g-4">

            <!-- IMAGEM 1 -->

            <div class="col-12 col-md-4">

                <article class="card card-galeria h-100">

                    <img
                        src="img/limpeza.jfif"
                        class="imagem-galeria"
                        alt="Imagem ilustrativa de limpeza residencial"
                        loading="lazy">

                    <div class="card-body p-4">

                        <h3 class="h5 fw-bold">
                            Limpeza residencial
                        </h3>

                        <p class="texto-suave mb-0">

                            Cuidados com os espaços
                            utilizados no dia a dia.

                        </p>

                    </div>

                </article>

            </div>

            <!-- IMAGEM 2 -->

            <div class="col-12 col-md-4">

                <article class="card card-galeria h-100">

                    <img
                        src="img/organizacao.jfif"
                        class="imagem-galeria"
                        alt="Imagem ilustrativa de ambiente organizado"
                        loading="lazy">

                    <div class="card-body p-4">

                        <h3 class="h5 fw-bold">
                            Organização
                        </h3>

                        <p class="texto-suave mb-0">

                            Uma casa organizada torna
                            a rotina mais prática.

                        </p>

                    </div>

                </article>

            </div>

            <!-- IMAGEM 3 -->

            <div class="col-12 col-md-4">

                <article class="card card-galeria h-100">

                    <img
                        src="img/cuidado.jfif"
                        class="imagem-galeria"
                        alt="Imagem ilustrativa de cuidados com a casa"
                        loading="lazy">

                    <div class="card-body p-4">

                        <h3 class="h5 fw-bold">
                            Cuidado com o ambiente
                        </h3>

                        <p class="texto-suave mb-0">

                            Atenção aos detalhes para
                            um ambiente acolhedor.

                        </p>

                    </div>

                </article>

            </div>

        </div>

    </section>


    <!-- =========================================
         CHAMADA FINAL
    ========================================== -->

    <section
        class="container pb-4"
        aria-labelledby="titulo-chamada-final">

        <div class="chamada-final p-4 p-md-5">

            <div class="row align-items-center g-4">

                <!-- TEXTO -->

                <div
                    class="col-12 col-md-8
                           text-center text-md-start">

                    <h2
                        id="titulo-chamada-final"
                        class="fw-bold mb-2">

                        Sua próxima faxina começa aqui.

                    </h2>

                    <p class="mb-0">

                        Encontre profissionais em
                        Bauru e envie sua solicitação
                        pelo LimpaLar.

                    </p>

                </div>

                <!-- BOTÃO -->

                <div
                    class="col-12 col-md-4
                           text-center text-md-end">

                    <a
                        href="profissionais.php"
                        class="btn btn-light btn-lg px-4 fw-semibold"
                        style="color:#126E68;border-radius:12px;">

                        Encontrar profissionais

                    </a>

                </div>

            </div>

        </div>

    </section>

</div>

<!-- O MAIN FOI ABERTO PELO CABECALHO.PHP -->

</main>

<?php include __DIR__ . '/rodape.php'; ?>
