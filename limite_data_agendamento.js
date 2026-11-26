
// LIMPA LAR - LIMITE DE DATA PARA AGENDAMENTO
// Bloqueia hoje a partir das 14h de Bauru.

document.addEventListener('DOMContentLoaded', function () {

    const campo = document.getElementById('dataServico');

    if (!campo) {
        return;
    }

    function dataEmBauru() {

        const partes = new Intl.DateTimeFormat(
            'en-US',
            {
                timeZone: 'America/Sao_Paulo',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hourCycle: 'h23'
            }
        ).formatToParts(new Date());

        function valor(nome) {
            return partes.find(
                p => p.type === nome
            )?.value;
        }

        const hoje =
            `${valor('year')}-${valor('month')}-${valor('day')}`;

        const hora = Number(valor('hour'));

        const amanha = new Date(
            `${hoje}T12:00:00Z`
        );

        amanha.setUTCDate(
            amanha.getUTCDate() + 1
        );

        return {
            minima: hora >= 14
                ? amanha.toISOString().slice(0, 10)
                : hoje
        };
    }

    function aplicarLimite() {

        const { minima } = dataEmBauru();

        campo.min = minima;

        if (campo.value && campo.value < minima) {

            campo.value = '';

            campo.dispatchEvent(
                new Event('change', {
                    bubbles: true
                })
            );
        }
    }

    aplicarLimite();

    campo.addEventListener(
        'focus',
        aplicarLimite
    );

    document.addEventListener(
        'visibilitychange',
        function () {

            if (!document.hidden) {
                aplicarLimite();
            }
        }
    );

    setInterval(
        aplicarLimite,
        60000
    );

});
    