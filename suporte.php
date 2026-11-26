
<?php
// =====================================================
// LIMPA LAR - CENTRAL DE AJUDA
// =====================================================

// Perguntas e respostas oficiais do sistema.
$perguntas = [

    [
        "pergunta" => "O que é o LimpaLar e como funciona?",
        "resposta" => "O LimpaLar é uma plataforma que conecta clientes a profissionais de limpeza residencial em Bauru. Os profissionais publicam seus serviços, e os clientes podem conhecer seus perfis, consultar avaliações e enviar solicitações de faxina. O profissional decide se aceita ou recusa cada solicitação."
    ],

    [
        "pergunta" => "Preciso criar uma conta para contratar uma faxina?",
        "resposta" => "Sim. Para solicitar uma faxina, é necessário criar uma conta gratuita e entrar no LimpaLar. O cadastro permite acompanhar suas solicitações e avaliar os serviços realizados."
    ],

    [
        "pergunta" => "Como encontro um profissional?",
        "resposta" => "Acesse a página Profissionais. Você poderá visualizar os anúncios disponíveis, conferir as fotos, conhecer a descrição dos serviços e consultar as avaliações. Depois, clique em Ver profissional para conhecer melhor o perfil e solicitar uma faxina."
    ],

    [
        "pergunta" => "Como faço para solicitar uma faxina?",
        "resposta" => "Escolha um profissional, clique em Ver profissional, selecione uma data futura e escolha o período desejado. Depois, informe o endereço onde o serviço será realizado e envie a solicitação. O pedido será encaminhado ao profissional escolhido."
    ],

    [
        "pergunta" => "Quais são os horários disponíveis?",
        "resposta" => "O LimpaLar trabalha com dois períodos de atendimento: Manhã, das 06h às 12h, e Tarde, das 13h às 17h. O cliente escolhe o período desejado no momento da solicitação. A realização do serviço depende da aceitação do profissional."
    ],

    [
        "pergunta" => "O agendamento é confirmado automaticamente?",
        "resposta" => "Não. Ao solicitar uma faxina, o pedido fica aguardando a resposta do profissional. Ele poderá aceitar ou recusar a solicitação. Você pode acompanhar o andamento na área Minhas faxinas."
    ],

    [
        "pergunta" => "Como entro em contato com o profissional?",
        "resposta" => "Após a aceitação da solicitação, os dados de contato ficam disponíveis na área de acompanhamento, conforme as informações cadastradas. Assim, cliente e profissional podem conversar pelo WhatsApp para acertar os detalhes do atendimento."
    ],

    [
        "pergunta" => "Quanto custa contratar uma faxina?",
        "resposta" => "O LimpaLar não determina um preço fixo para as faxinas. O valor é combinado diretamente entre o cliente e o profissional, considerando o serviço solicitado, o endereço e os detalhes do atendimento. A plataforma não realiza pagamentos."
    ],

    [
        "pergunta" => "Como posso oferecer meus serviços?",
        "resposta" => "Crie sua conta, adicione uma foto de perfil e acesse a opção Oferecer meu serviço. Escreva uma descrição do seu trabalho e publique o anúncio. Depois disso, os clientes poderão encontrar seu perfil e enviar solicitações."
    ],

    [
        "pergunta" => "Preciso escolher os bairros onde faço faxinas?",
        "resposta" => "Não. O LimpaLar atende atualmente a cidade de Bauru, e os profissionais não precisam selecionar bairros ao publicar seus anúncios. O endereço, o deslocamento e os detalhes do atendimento são combinados diretamente com o cliente."
    ],

    [
        "pergunta" => "Posso adicionar ou alterar minha foto de perfil?",
        "resposta" => "Sim. Ao criar uma conta, você pode enviar uma foto nos formatos JPG, PNG ou WebP, com até 3 MB. Também é possível ajustar o enquadramento antes de salvar. Para atualizar sua imagem depois do cadastro, acesse Minha foto de perfil."
    ],

    [
        "pergunta" => "Como funciona a avaliação dos profissionais?",
        "resposta" => "Depois que uma faxina é concluída, o cliente pode avaliar o profissional com uma nota de 1 a 5 estrelas e deixar um comentário. As avaliações ajudam outros usuários a conhecer a experiência de quem já contratou o serviço."
    ],

    [
        "pergunta" => "Como acompanho minhas solicitações?",
        "resposta" => "Entre na sua conta e acesse Minhas faxinas. Nessa área, você pode acompanhar o andamento das solicitações, verificar quando são aceitas ou recusadas e consultar os serviços realizados."
    ]

];

function faq_escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        'UTF-8'
    );
}

include __DIR__ . "/cabecalho.php";
?>

<!-- =====================================================
     ESTILOS DA CENTRAL DE AJUDA
====================================================== -->

<style>

    .ajuda-titulo {
        color: #126E68;
    }

    .ajuda-card {
        border: none;
        border-radius: 18px;
        transition: transform 0.2s ease,
                    box-shadow 0.2s ease;
    }

    .ajuda-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
    }

    .ajuda-icone {
        font-size: 44px;
        margin-bottom: 14px;
    }

    .ajuda-botao {
        background-color: #18A999;
        border-color: #18A999;
        color: #FFFFFF;
    }

    .ajuda-botao:hover {
        background-color: #126E68;
        border-color: #126E68;
        color: #FFFFFF;
    }

    .ajuda-accordion .accordion-item {
        border: 1px solid #E2EBE8;
        border-radius: 12px !important;
        overflow: hidden;
        margin-bottom: 12px;
    }

    .ajuda-accordion .accordion-button {
        font-weight: 600;
        color: #264744;
        background: #FFFFFF;
        padding: 20px;
        box-shadow: none;
    }

    .ajuda-accordion .accordion-button:not(.collapsed) {
        background: #E7F5F1;
        color: #126E68;
        box-shadow: none;
    }

    .ajuda-accordion .accordion-button:focus {
        box-shadow: 0 0 0 3px rgba(24,169,153,0.2);
    }

    .ajuda-accordion .accordion-body {
        color: #617477;
        line-height: 1.8;
        padding: 20px;
    }

</style>

<!-- =====================================================
     CABEÇALHO DA PÁGINA
====================================================== -->

<section class="container py-5">

    <div class="text-center mb-5">

        <h1 class="fw-bold ajuda-titulo">
            Como podemos ajudar?
        </h1>

        <p class="lead text-secondary">

            Encontre respostas para suas dúvidas
            e saiba como utilizar o LimpaLar.

        </p>

    </div>

    <!-- =================================================
         CARDS DE NAVEGAÇÃO
    ================================================== -->

    <div class="row g-4 justify-content-center">

        <!-- PERGUNTAS -->

        <div class="col-12 col-md-6 col-lg-4">

            <div class="card ajuda-card shadow-sm h-100 text-center">

                <div class="card-body p-4">

                    <div class="ajuda-icone">
                        ❓
                    </div>

                    <h2 class="h5 fw-bold ajuda-titulo">
                        Perguntas e respostas
                    </h2>

                    <p class="text-secondary">

                        Tire suas dúvidas sobre
                        o funcionamento do LimpaLar.

                    </p>

                    <a
                        href="#duvidas"
                        class="btn btn-outline-success"
                    >
                        Ver perguntas
                    </a>

                </div>

            </div>

        </div>

        <!-- AGENDAMENTO -->

        <div class="col-12 col-md-6 col-lg-4">

            <div class="card ajuda-card shadow-sm h-100 text-center">

                <div class="card-body p-4">

                    <div class="ajuda-icone">
                        📅
                    </div>

                    <h2 class="h5 fw-bold ajuda-titulo">
                        Agendamentos
                    </h2>

                    <p class="text-secondary">

                        Entenda como solicitar uma
                        faxina e acompanhar seu pedido.

                    </p>

                    <a
                        href="#agendamento"
                        class="btn btn-outline-success"
                    >
                        Saiba mais
                    </a>

                </div>

            </div>

        </div>

        <!-- PROFISSIONAIS -->

        <div class="col-12 col-md-6 col-lg-4">

            <div class="card ajuda-card shadow-sm h-100 text-center">

                <div class="card-body p-4">

                    <div class="ajuda-icone">
                        🧹
                    </div>

                    <h2 class="h5 fw-bold ajuda-titulo">
                        Oferecer serviços
                    </h2>

                    <p class="text-secondary">

                        Saiba como anunciar seus
                        serviços de faxina no site.

                    </p>

                    <a
                        href="#oferecer"
                        class="btn btn-outline-success"
                    >
                        Como anunciar
                    </a>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- =====================================================
     DÚVIDAS FREQUENTES
====================================================== -->

<section
    id="duvidas"
    class="py-5"
    style="background:#F1F9F6"
>

    <div class="container">

        <div class="text-center mb-5">

            <h2 class="fw-bold ajuda-titulo">
                Dúvidas frequentes
            </h2>

            <p class="text-secondary">

                Confira as respostas para as perguntas
                mais comuns sobre o LimpaLar.

            </p>

        </div>

        <div class="row justify-content-center">

            <div class="col-12 col-lg-9">

                <div
                    class="accordion ajuda-accordion"
                    id="accordionSuporte"
                >

                    <?php foreach ($perguntas as $indice => $item): ?>

                        <?php
                            $numero = $indice + 1;
                            $aberto = $indice === 0;
                        ?>

                        <div class="accordion-item">

                            <h3
                                class="accordion-header"
                                id="tituloPergunta<?= $numero ?>"
                            >

                                <button
                                    class="accordion-button <?= $aberto ? '' : 'collapsed' ?>"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#resposta<?= $numero ?>"
                                    aria-expanded="<?= $aberto ? 'true' : 'false' ?>"
                                    aria-controls="resposta<?= $numero ?>"
                                >

                                    <?= faq_escapar(
                                        $item['pergunta']
                                    ) ?>

                                </button>

                            </h3>

                            <div
                                id="resposta<?= $numero ?>"
                                class="accordion-collapse collapse <?= $aberto ? 'show' : '' ?>"
                                aria-labelledby="tituloPergunta<?= $numero ?>"
                                data-bs-parent="#accordionSuporte"
                            >

                                <div class="accordion-body">

                                    <?= faq_escapar(
                                        $item['resposta']
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- =====================================================
     AGENDAMENTO
====================================================== -->

<section
    id="agendamento"
    class="container py-5"
>

    <div class="row justify-content-center">

        <div class="col-12 col-lg-9">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">

                        <h2 class="fw-bold ajuda-titulo">
                            📅 Como solicitar uma faxina?
                        </h2>

                        <p class="text-secondary">

                            Solicitar um serviço no LimpaLar
                            é simples e rápido.

                        </p>

                    </div>

                    <div class="row g-4 mb-4">

                        <div class="col-12 col-md-4 text-center">

                            <div class="fs-1 mb-2">
                                👤
                            </div>

                            <h3 class="h6 fw-bold">
                                1. Escolha
                            </h3>

                            <p class="small text-secondary">

                                Conheça os profissionais,
                                suas fotos, descrições
                                e avaliações.

                            </p>

                        </div>

                        <div class="col-12 col-md-4 text-center">

                            <div class="fs-1 mb-2">
                                🗓️
                            </div>

                            <h3 class="h6 fw-bold">
                                2. Solicite
                            </h3>

                            <p class="small text-secondary">

                                Escolha uma data e o período
                                Manhã ou Tarde. Informe
                                o endereço do serviço.

                            </p>

                        </div>

                        <div class="col-12 col-md-4 text-center">

                            <div class="fs-1 mb-2">
                                ✅
                            </div>

                            <h3 class="h6 fw-bold">
                                3. Acompanhe
                            </h3>

                            <p class="small text-secondary">

                                Aguarde o profissional
                                aceitar ou recusar
                                sua solicitação.

                            </p>

                        </div>

                    </div>

                    <div
                        class="rounded-4 p-4 mb-4"
                        style="background:#E7F5F1"
                    >

                        <h3
                            class="h5 fw-bold"
                            style="color:#126E68"
                        >
                            Horários de atendimento
                        </h3>

                        <p class="mb-2">
                            ☀️ Manhã: 06h às 12h
                        </p>

                        <p class="mb-0">
                            🌤️ Tarde: 13h às 17h
                        </p>

                    </div>

                    <p class="text-secondary text-center">

                        A solicitação não significa
                        confirmação automática.
                        Aguarde a resposta do profissional.

                    </p>

                    <div class="text-center">

                        <a
                            href="profissionais.php"
                            class="btn ajuda-botao btn-lg px-4"
                        >
                            Encontrar profissionais
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- =====================================================
     OFERECER SERVIÇOS
====================================================== -->

<section
    id="oferecer"
    class="py-5"
    style="background:#E7F5F1"
>

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-12 col-lg-9">

                <div
                    class="card border-0 shadow-sm rounded-4"
                >

                    <div class="card-body p-4 p-md-5">

                        <h2
                            class="fw-bold text-center mb-3"
                            style="color:#126E68"
                        >
                            🧹 Quer oferecer seus serviços?
                        </h2>

                        <p class="text-secondary text-center">

                            Você também pode divulgar
                            seu trabalho no LimpaLar.

                        </p>

                        <div class="mb-4">

                            <p>
                                <strong>1.</strong>
                                Crie sua conta ou entre
                                com seu cadastro.
                            </p>

                            <p>
                                <strong>2.</strong>
                                Adicione uma foto de perfil.
                            </p>

                            <p>
                                <strong>3.</strong>
                                Acesse a página
                                Oferecer meu serviço.
                            </p>

                            <p>
                                <strong>4.</strong>
                                Descreva como você realiza
                                suas faxinas e publique
                                seu anúncio.
                            </p>

                            <p>
                                <strong>5.</strong>
                                Acompanhe as solicitações
                                recebidas e decida quais
                                deseja aceitar.
                            </p>

                        </div>

                        <div class="text-center">

                            <a
                                href="cadastrar_anuncio.php"
                                class="btn ajuda-botao btn-lg px-4"
                            >
                                Oferecer meu serviço
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<!-- =====================================================
     INFORMAÇÕES IMPORTANTES
====================================================== -->

<section class="container py-5">

    <div class="row justify-content-center">

        <div class="col-12 col-lg-9">

            <div class="text-center mb-4">

                <h2 class="fw-bold ajuda-titulo">
                    Informações importantes
                </h2>

            </div>

            <div class="row g-4">

                <div class="col-12 col-md-6">

                    <div
                        class="card border-0 shadow-sm rounded-4 h-100"
                    >

                        <div class="card-body p-4">

                            <h3 class="h5 fw-bold ajuda-titulo">
                                📍 Área de atendimento
                            </h3>

                            <p class="text-secondary mb-0">

                                O LimpaLar atende atualmente
                                a cidade de Bauru, São Paulo.

                                O deslocamento até o endereço
                                deve ser combinado entre
                                cliente e profissional.

                            </p>

                        </div>

                    </div>

                </div>

                <div class="col-12 col-md-6">

                    <div
                        class="card border-0 shadow-sm rounded-4 h-100"
                    >

                        <div class="card-body p-4">

                            <h3 class="h5 fw-bold ajuda-titulo">
                                💬 Valores e pagamentos
                            </h3>

                            <p class="text-secondary mb-0">

                                O valor da faxina é combinado
                                diretamente entre as partes.

                                O LimpaLar conecta clientes
                                e profissionais, mas não
                                processa pagamentos.

                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

</main>

<?php include __DIR__ . "/rodape.php"; ?>
