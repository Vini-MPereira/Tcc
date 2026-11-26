
// LIMPA LAR - ATUALIZAÇÃO AUTOMÁTICA DE FAXINAS

document.addEventListener('DOMContentLoaded', function () {

    const chaveAba = 'limpalar_aba_faxinas';

    const botoes = document.querySelectorAll(
        '#abasFaxinas [data-bs-toggle="pill"]'
    );

    // Mantém a aba que estava aberta após atualizar.
    if (
        botoes.length &&
        window.bootstrap &&
        bootstrap.Tab
    ) {

        const selecionada =
            new URLSearchParams(location.search).get('aba') ||
            sessionStorage.getItem(chaveAba);

        const id = selecionada === 'recebi'
            ? 'aba-recebi'
            : selecionada === 'contratei'
                ? 'aba-contratei'
                : null;

        if (id && document.getElementById(id)) {

            bootstrap.Tab.getOrCreateInstance(
                document.getElementById(id)
            ).show();
        }

        botoes.forEach(function (botao) {

            botao.addEventListener('shown.bs.tab', function () {

                sessionStorage.setItem(
                    chaveAba,
                    botao.id === 'aba-recebi'
                        ? 'recebi'
                        : 'contratei'
                );
            });
        });
    }

    let versaoAnterior = null;
    let consultando = false;

    async function verificarAlteracoes(
        verificacaoInicial = false
    ) {

        if (
            (document.hidden && !verificacaoInicial) ||
            consultando
        ) {
            return;
        }

        consultando = true;

        try {

            const resposta = await fetch(
                'status_faxinas.php',
                {
                    credentials: 'same-origin',
                    cache: 'no-store'
                }
            );

            if (!resposta.ok) {
                return;
            }

            const dados = await resposta.json();

            if (!dados.versao) {
                return;
            }

            // Se o banco mudou, atualiza a tela.
            if (
                versaoAnterior !== null &&
                versaoAnterior !== dados.versao
            ) {

                location.reload();
                return;
            }

            versaoAnterior = dados.versao;

        } catch (erro) {

            // A próxima consulta tenta novamente.

        } finally {

            consultando = false;
        }
    }

    // Consulta inicialmente e depois a cada 8 segundos.
    verificarAlteracoes(true);

    setInterval(
        verificarAlteracoes,
        8000
    );

    // Verifica também quando o usuário volta à aba.
    document.addEventListener(
        'visibilitychange',
        verificarAlteracoes
    );

});
