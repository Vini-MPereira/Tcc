
<?php
// =====================================================
// LIMPA LAR - RODAPÉ COMPARTILHADO
// =====================================================

// O cabeçalho já inicia a sessão.
// Apenas consultamos se existe um usuário logado.

$rodape_usuario_logado = !empty(
    $_SESSION["ID_USUARIO"]
);

$ano_atual = date("Y");
?>

<!-- =====================================================
     ESTILOS DO RODAPÉ
====================================================== -->

<style>
    .rodape-limpalar {
        background-color: #126E68;
        color: #FFFFFF;
    }

    .rodape-limpalar .rodape-marca {
        color: #FFFFFF;
        font-size: 1.7rem;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .rodape-limpalar .rodape-descricao {
        color: #D9F2ED;
        line-height: 1.7;
        font-size: 0.95rem;
    }

    .rodape-limpalar .rodape-titulo {
        color: #FFFFFF;
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: 1.25rem;
    }

    .rodape-limpalar .rodape-link {
        color: #D9F2ED;
        text-decoration: none;
        display: inline-block;
        padding: 3px 0;
        transition: color 0.2s ease;
    }

    .rodape-limpalar .rodape-link:hover,
    .rodape-limpalar .rodape-link:focus-visible {
        color: #FFFFFF;
        text-decoration: underline;
        text-underline-offset: 4px;
    }

    .rodape-limpalar .rodape-informacao {
        color: #D9F2ED;
        font-size: 0.95rem;
        line-height: 1.7;
    }

    .rodape-limpalar .rodape-aviso {
        padding: 16px;
        background: rgba(255, 255, 255, 0.09);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        color: #E8FAF6;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .rodape-limpalar .rodape-divisor {
        border: 0;
        border-top: 1px solid rgba(255, 255, 255, 0.28);
        opacity: 1;
    }

    .rodape-limpalar .rodape-copyright {
        color: #D9F2ED;
        font-size: 0.85rem;
    }

    .rodape-limpalar .voltar-topo {
        color: #FFFFFF;
        text-decoration: none;
        border: 1px solid rgba(255, 255, 255, 0.35);
        padding: 9px 15px;
        border-radius: 10px;
        font-size: 0.85rem;
        transition: background-color 0.2s ease;
    }

    .rodape-limpalar .voltar-topo:hover {
        background-color: rgba(255, 255, 255, 0.13);
        color: #FFFFFF;
    }

    .rodape-limpalar .voltar-topo:focus-visible {
        outline: 2px solid #FFFFFF;
        outline-offset: 3px;
    }

    @media (max-width: 767.98px) {
        .rodape-limpalar {
            text-align: center;
        }

        .rodape-limpalar .rodape-titulo {
            margin-bottom: 0.75rem;
        }

        .rodape-limpalar .rodape-aviso {
            text-align: left;
        }
    }
</style>

<!-- =====================================================
     RODAPÉ PRINCIPAL
====================================================== -->

<footer
    id="rodape-limpalar"
    class="rodape-limpalar mt-5">

    <div class="container py-5">

        <div class="row g-4 g-lg-5">

            <!-- =========================================
                 1. SOBRE O LIMPA LAR
            ========================================== -->

            <div class="col-12 col-md-6 col-lg-4">

                <div class="mb-3">

                    <span class="rodape-marca">
                        🧹 LimpaLar
                    </span>

                </div>

                <p class="rodape-descricao mb-3">

                    Cuidado que transforma seu lar.

                    Uma plataforma que conecta
                    clientes a profissionais de
                    limpeza residencial em Bauru.

                </p>

                <p class="rodape-descricao mb-0">

                    Encontre profissionais,
                    solicite faxinas e avalie
                    os atendimentos realizados.

                </p>

            </div>

            <!-- =========================================
                 2. ACESSO RÁPIDO
            ========================================== -->

            <div class="col-12 col-md-6 col-lg-4">

                <h2 class="rodape-titulo">
                    Acesso rápido
                </h2>

                <ul class="list-unstyled mb-0">

                    <li class="mb-2">

                        <a
                            href="index.php"
                            class="rodape-link">

                            Início

                        </a>

                    </li>

                    <li class="mb-2">

                        <a
                            href="profissionais.php"
                            class="rodape-link">

                            Encontrar profissionais

                        </a>

                    </li>

                    <!-- LINKS PARA USUÁRIOS LOGADOS -->

                    <?php if ($rodape_usuario_logado): ?>

                        <li class="mb-2">

                            <a
                                href="painel.php"
                                class="rodape-link">

                                Meu painel

                            </a>

                        </li>

                        <li class="mb-2">

                            <a
                                href="concluir_faxina.php"
                                class="rodape-link">

                                Minhas faxinas

                            </a>

                        </li>

                        <li class="mb-2">

                            <a
                                href="avaliacoes.php"
                                class="rodape-link">

                                Minhas avaliações

                            </a>

                        </li>

                        <li class="mb-2">

                            <a
                                href="cadastrar_anuncio.php"
                                class="rodape-link">

                                Oferecer meu serviço

                            </a>

                        </li>

                    <?php else: ?>

                        <li class="mb-2">

                            <a
                                href="entrar.php"
                                class="rodape-link">

                                Entrar na minha conta

                            </a>

                        </li>

                        <li class="mb-2">

                            <a
                                href="cadastrar.php"
                                class="rodape-link">

                                Criar uma conta

                            </a>

                        </li>

                    <?php endif; ?>

                </ul>

            </div>

            <!-- =========================================
                 3. INFORMAÇÕES E SUPORTE
            ========================================== -->

            <div class="col-12 col-md-12 col-lg-4">

                <h2 class="rodape-titulo">
                    Informações e suporte
                </h2>

                <p class="rodape-informacao mb-3">

                    📍 Área de atendimento:
                    <strong>Bauru - SP</strong>

                </p>

                <p class="rodape-informacao mb-3">

                    Precisa de ajuda ou encontrou
                    algum problema no sistema?

                </p>

                <a
                    href="suporte.php"
                    class="rodape-link fw-semibold mb-3">

                    💬 Acessar central de suporte

                </a>

                <!-- INFORMAÇÃO SOBRE A CONTRATAÇÃO -->

                <div class="rodape-aviso mt-2">

                    <strong class="d-block mb-2">

                        🤝 Como funciona a contratação?

                    </strong>

                    O contato entre cliente e profissional
                    é liberado após a aceitação da
                    solicitação.

                    <div class="mt-2">

                        Os valores são combinados
                        diretamente entre as partes.

                        O LimpaLar não processa
                        pagamentos.

                    </div>

                </div>

            </div>

        </div>

        <!-- =========================================
             LINHA DIVISÓRIA
        ========================================== -->

        <hr class="rodape-divisor my-4">

        <!-- =========================================
             PARTE INFERIOR
        ========================================== -->

        <div
            class="d-flex flex-column flex-md-row
                   justify-content-between
                   align-items-center gap-3">

            <div class="rodape-copyright">

                © <?= (int) $ano_atual; ?>
                LimpaLar.

                Projeto acadêmico de
                Desenvolvimento de Sistemas.

            </div>

            <!-- VOLTAR AO TOPO -->

            <a
                href="#"
                class="voltar-topo">

                ↑ Voltar ao topo

            </a>

        </div>

    </div>

</footer>

<!-- =====================================================
     BOOTSTRAP JAVASCRIPT LOCAL
====================================================== -->

<script src="bootstrap/js/bootstrap.bundle.js"></script>

</body>
</html>