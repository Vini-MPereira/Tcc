
/*
 * LIMPA LAR - EDITOR DE FOTO
 *
 * Aceita fotos originais de até 30 MB.
 * Permite arrastar, ampliar e girar.
 * Redimensiona para 600 x 600 pixels.
 * Comprime em JPEG antes de enviar ao PHP.
 *
 * Funciona em:
 * - cadastrar.php
 * - minha_foto.php
 */

document.addEventListener("DOMContentLoaded", function () {

    const campo = document.getElementById("foto");

    const preview = document.getElementById("previewFoto")
        || document.getElementById("fotoNova");

    const avatar = document.getElementById("previewVazio")
        || document.getElementById("fotoVazia");

    const fotoAtual = document.getElementById("fotoAtual");

    const remover = document.getElementById("btnRemoverFoto");

    if (!campo || !preview || !remover) {
        return;
    }

    // ==========================================
    // CONFIGURAÇÕES
    // ==========================================

    const MAX_ORIGINAL = 30 * 1024 * 1024;
    const MAX_FINAL = 2 * 1024 * 1024;

    const TAMANHO_EDITOR = 320;
    const TAMANHO_FINAL = 600;

    const TIPOS = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    let imagem = null;
    let arquivoEmEdicao = null;

    let arquivoPronto = null;
    let originalConfirmado = null;

    let urlImagem = null;
    let urlPreview = null;

    let zoom = 1;
    let rotacao = 0;
    let posX = 0;
    let posY = 0;

    let arrastando = false;
    let ultimoX = 0;
    let ultimoY = 0;

    let processando = false;

    // ==========================================
    // ESTILOS
    // ==========================================

    const estilo = document.createElement("style");

    estilo.textContent = `
        .limpa-editor-fundo {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: rgba(0, 0, 0, 0.82);
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .limpa-editor-fundo.aberto {
            display: flex;
        }

        .limpa-editor-caixa {
            width: 100%;
            max-width: 440px;
            max-height: 95dvh;
            overflow-y: auto;
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 15px 50px #0005;
        }

        .limpa-editor-caixa h2 {
            font-size: 24px;
            font-weight: 700;
            color: #126E68;
        }

        .limpa-editor-caixa p {
            font-size: 14px;
            color: #617477;
        }

        .limpa-editor-canvas {
            display: block;
            width: min(100%, 320px);
            height: auto;
            aspect-ratio: 1 / 1;
            margin: 18px auto;
            border-radius: 50%;
            border: 4px solid #18A999;
            background: #E7F5F1;
            cursor: grab;
            touch-action: none;
        }

        .limpa-editor-canvas.arrastando {
            cursor: grabbing;
        }

        .limpa-editor-zoom {
            width: 100%;
            accent-color: #18A999;
            margin-bottom: 15px;
        }

        .limpa-editor-botoes {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .limpa-editor-botoes button {
            flex: 1;
            min-width: 110px;
            padding: 11px;
            border: 0;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .limpa-editor-verde {
            color: #fff;
            background: #18A999;
        }

        .limpa-editor-cinza {
            color: #23403D;
            background: #E8ECEB;
        }

        .limpa-editor-botoes button:disabled {
            opacity: 0.5;
            cursor: wait;
        }
    `;

    document.head.appendChild(estilo);

    // ==========================================
    // CRIAR BOTÃO AJUSTAR
    // ==========================================

    const ajustar = document.createElement("button");

    ajustar.type = "button";

    ajustar.className =
        "btn btn-outline-success btn-sm mt-3 me-2 d-none";

    ajustar.textContent = "✂ Ajustar foto";

    remover.parentNode.insertBefore(ajustar, remover);

    if (fotoAtual) {
        ajustar.textContent = "✂ Ajustar foto atual";
        ajustar.classList.remove("d-none");
    }

    // ==========================================
    // CRIAR EDITOR
    // ==========================================

    const fundo = document.createElement("div");

    fundo.className = "limpa-editor-fundo";

    fundo.innerHTML = `
        <div
            class="limpa-editor-caixa"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tituloEditor"
            tabindex="-1"
        >

            <h2 id="tituloEditor">
                ✂ Ajustar foto de perfil
            </h2>

            <p>
                Arraste para centralizar.
                Use o zoom ou gire a foto
                antes de confirmar.
            </p>

            <canvas
                class="limpa-editor-canvas"
                width="320"
                height="320"
                aria-label="Prévia do enquadramento"
            ></canvas>

            <label
                for="controleZoom"
                class="form-label fw-semibold"
            >
                🔍 Zoom
            </label>

            <input
                id="controleZoom"
                class="limpa-editor-zoom"
                type="range"
                min="1"
                max="3"
                value="1"
                step="0.01"
            >

            <div class="limpa-editor-botoes">

                <button
                    type="button"
                    class="limpa-editor-cinza"
                    id="girarImagem"
                >
                    ↻ Girar 90°
                </button>

                <button
                    type="button"
                    class="limpa-editor-cinza"
                    id="cancelarImagem"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    class="limpa-editor-verde"
                    id="salvarRecorte"
                >
                    ✓ Usar esta foto
                </button>

            </div>

        </div>
    `;

    document.body.appendChild(fundo);

    const caixa = fundo.querySelector(
        ".limpa-editor-caixa"
    );

    const canvas = fundo.querySelector("canvas");
    const contexto = canvas.getContext("2d");

    const controleZoom = fundo.querySelector(
        "#controleZoom"
    );

    const botaoGirar = fundo.querySelector(
        "#girarImagem"
    );

    const botaoCancelar = fundo.querySelector(
        "#cancelarImagem"
    );

    const botaoConfirmar = fundo.querySelector(
        "#salvarRecorte"
    );

    let overflowAnterior = "";
    let focoAnterior = null;

    // ==========================================
    // RESTAURAR ARQUIVO PRONTO NO INPUT
    // ==========================================

    function restaurarInput() {

        if (!arquivoPronto) {
            campo.value = "";
            return;
        }

        const transferencia = new DataTransfer();

        transferencia.items.add(arquivoPronto);

        campo.files = transferencia.files;
    }

    // ==========================================
    // ATUALIZAR PRÉVIA DO PERFIL
    // ==========================================

    function atualizarPreview() {

        if (urlPreview) {
            URL.revokeObjectURL(urlPreview);
            urlPreview = null;
        }

        if (!arquivoPronto) {

            preview.removeAttribute("src");
            preview.style.display = "none";

            if (fotoAtual) {
                fotoAtual.style.display = "inline-block";
            }

            if (avatar) {
                avatar.style.display = fotoAtual
                    ? "none"
                    : "flex";
            }

            remover.classList.add("d-none");

            if (fotoAtual) {
                ajustar.textContent =
                    "✂ Ajustar foto atual";

                ajustar.classList.remove("d-none");
            } else {
                ajustar.classList.add("d-none");
            }

            return;
        }

        urlPreview = URL.createObjectURL(
            arquivoPronto
        );

        preview.src = urlPreview;
        preview.style.display = "block";

        if (fotoAtual) {
            fotoAtual.style.display = "none";
        }

        if (avatar) {
            avatar.style.display = "none";
        }

        remover.classList.remove("d-none");

        ajustar.textContent = "✂ Ajustar foto";
        ajustar.classList.remove("d-none");
    }

    // ==========================================
    // REMOVER FOTO ESCOLHIDA
    // ==========================================

    remover.addEventListener("click", function () {

        arquivoPronto = null;
        originalConfirmado = null;

        campo.value = "";

        atualizarPreview();
    });

    // ==========================================
    // DIMENSÕES DE ACORDO COM ROTAÇÃO
    // ==========================================

    function dimensoes() {

        if (rotacao % 180 === 0) {
            return [
                imagem.naturalWidth,
                imagem.naturalHeight
            ];
        }

        return [
            imagem.naturalHeight,
            imagem.naturalWidth
        ];
    }

    // ==========================================
    // LIMITAR MOVIMENTAÇÃO DA FOTO
    // ==========================================

    function limitarPosicao() {

        if (!imagem) {
            return;
        }

        const [largura, altura] = dimensoes();

        const escala = Math.max(
            TAMANHO_EDITOR / largura,
            TAMANHO_EDITOR / altura
        ) * zoom;

        const limiteX = Math.max(
            0,
            (largura * escala - TAMANHO_EDITOR) / 2
        );

        const limiteY = Math.max(
            0,
            (altura * escala - TAMANHO_EDITOR) / 2
        );

        posX = Math.max(
            -limiteX,
            Math.min(limiteX, posX)
        );

        posY = Math.max(
            -limiteY,
            Math.min(limiteY, posY)
        );
    }

    // ==========================================
    // DESENHAR O RECORTE
    // ==========================================

    function desenhar(ctx, tamanho) {

        if (!imagem) {
            return;
        }

        const [largura, altura] = dimensoes();

        const escala = Math.max(
            tamanho / largura,
            tamanho / altura
        ) * zoom;

        const proporcao = tamanho / TAMANHO_EDITOR;

        ctx.clearRect(0, 0, tamanho, tamanho);

        ctx.fillStyle = "#FFFFFF";
        ctx.fillRect(0, 0, tamanho, tamanho);

        ctx.save();

        ctx.translate(
            tamanho / 2 + posX * proporcao,
            tamanho / 2 + posY * proporcao
        );

        ctx.rotate(
            rotacao * Math.PI / 180
        );

        ctx.scale(escala, escala);

        ctx.drawImage(
            imagem,
            -imagem.naturalWidth / 2,
            -imagem.naturalHeight / 2
        );

        ctx.restore();
    }

    function redesenhar() {

        limitarPosicao();

        desenhar(contexto, TAMANHO_EDITOR);
    }

    // ==========================================
    // FECHAR EDITOR
    // ==========================================

    function fecharEditor() {

        if (processando) {
            return;
        }

        fundo.classList.remove("aberto");

        document.body.style.overflow =
            overflowAnterior;

        arrastando = false;

        if (urlImagem) {
            URL.revokeObjectURL(urlImagem);
            urlImagem = null;
        }

        imagem = null;
        arquivoEmEdicao = null;

        restaurarInput();

        if (
            focoAnterior &&
            typeof focoAnterior.focus === "function"
        ) {
            focoAnterior.focus();
        }
    }

    // ==========================================
    // ABRIR EDITOR
    // ==========================================

    function abrirEditor(arquivo) {

        if (!arquivo) {
            return;
        }

        if (!TIPOS.includes(arquivo.type)) {

            alert(
                "Formato não aceito. Escolha uma foto JPG, PNG ou WebP."
            );

            restaurarInput();
            return;
        }

        // A foto original pode ser grande.
        // O arquivo enviado ao servidor será reduzido.

        if (
            arquivo.size === 0 ||
            arquivo.size > MAX_ORIGINAL
        ) {

            alert(
                "Escolha uma foto original de até 30 MB."
            );

            restaurarInput();
            return;
        }

        const novaImagem = new Image();

        const url = URL.createObjectURL(
            arquivo
        );

        novaImagem.onload = function () {

            if (
                novaImagem.naturalWidth < 1 ||
                novaImagem.naturalHeight < 1 ||
                novaImagem.naturalWidth > 14000 ||
                novaImagem.naturalHeight > 14000
            ) {

                URL.revokeObjectURL(url);

                alert(
                    "As dimensões dessa imagem não são suportadas."
                );

                restaurarInput();
                return;
            }

            imagem = novaImagem;
            arquivoEmEdicao = arquivo;
            urlImagem = url;

            zoom = 1;
            rotacao = 0;

            posX = 0;
            posY = 0;

            controleZoom.value = "1";

            redesenhar();

            overflowAnterior =
                document.body.style.overflow;

            focoAnterior = document.activeElement;

            document.body.style.overflow = "hidden";

            fundo.classList.add("aberto");

            caixa.focus();
        };

        novaImagem.onerror = function () {

            URL.revokeObjectURL(url);

            alert(
                "Não foi possível abrir a foto. Tente uma imagem JPG ou PNG."
            );

            restaurarInput();
        };

        novaImagem.src = url;
    }

    // ==========================================
    // QUANDO SELECIONAR UMA FOTO
    // ==========================================

    campo.addEventListener("change", function () {

        const arquivo = campo.files[0];

        if (!arquivo) {
            restaurarInput();
            return;
        }

        // IMPORTANTE:
        // Não deixa o arquivo original grande
        // no campo de upload enquanto é editado.

        restaurarInput();

        abrirEditor(arquivo);
    });

    // ==========================================
    // AJUSTAR FOTO NOVAMENTE
    // ==========================================

    ajustar.addEventListener("click", async function () {

        if (originalConfirmado) {

            abrirEditor(originalConfirmado);
            return;
        }

        if (!fotoAtual) {
            return;
        }

        try {

            const endereco = new URL(
                fotoAtual.src,
                window.location.href
            );

            if (
                endereco.origin !== window.location.origin
            ) {
                throw new Error("Imagem externa.");
            }

            const resposta = await fetch(
                endereco.href,
                {
                    credentials: "same-origin"
                }
            );

            if (!resposta.ok) {
                throw new Error("Foto não encontrada.");
            }

            const blob = await resposta.blob();

            const arquivo = new File(
                [blob],
                "foto-atual",
                {
                    type: blob.type || "image/jpeg"
                }
            );

            abrirEditor(arquivo);

        } catch (erro) {

            alert(
                "Não foi possível abrir a foto atual."
            );
        }
    });

    // ==========================================
    // ZOOM
    // ==========================================

    controleZoom.addEventListener("input", function () {

        zoom = Number(controleZoom.value);

        redesenhar();
    });

    // ==========================================
    // GIRAR A IMAGEM
    // ==========================================

    botaoGirar.addEventListener("click", function () {

        rotacao = (rotacao + 90) % 360;

        posX = 0;
        posY = 0;

        redesenhar();
    });

    // ==========================================
    // ARRASTAR COM MOUSE OU DEDO
    // ==========================================

    canvas.addEventListener("pointerdown", function (evento) {

        if (!imagem) {
            return;
        }

        arrastando = true;

        ultimoX = evento.clientX;
        ultimoY = evento.clientY;

        canvas.classList.add("arrastando");

        canvas.setPointerCapture(
            evento.pointerId
        );
    });

    canvas.addEventListener("pointermove", function (evento) {

        if (!arrastando) {
            return;
        }

        const larguraVisual = canvas
            .getBoundingClientRect()
            .width;

        const fator = TAMANHO_EDITOR / larguraVisual;

        posX += (
            evento.clientX - ultimoX
        ) * fator;

        posY += (
            evento.clientY - ultimoY
        ) * fator;

        ultimoX = evento.clientX;
        ultimoY = evento.clientY;

        redesenhar();
    });

    function pararArrasto() {

        arrastando = false;

        canvas.classList.remove("arrastando");
    }

    canvas.addEventListener("pointerup", pararArrasto);

    canvas.addEventListener(
        "pointercancel",
        pararArrasto
    );

    canvas.addEventListener(
        "lostpointercapture",
        pararArrasto
    );

    // ==========================================
    // CANCELAR
    // ==========================================

    botaoCancelar.addEventListener(
        "click",
        fecharEditor
    );

    fundo.addEventListener("click", function (evento) {

        if (evento.target === fundo) {
            fecharEditor();
        }
    });

    document.addEventListener("keydown", function (evento) {

        if (
            evento.key === "Escape" &&
            fundo.classList.contains("aberto")
        ) {
            fecharEditor();
        }
    });

    // ==========================================
    // REDUZIR A FOTO AUTOMATICAMENTE
    // ==========================================

    function gerarBlob(canvasSaida, qualidade) {

        return new Promise(function (resolver) {

            canvasSaida.toBlob(
                resolver,
                "image/jpeg",
                qualidade
            );

        });
    }

    // ==========================================
    // CONFIRMAR O RECORTE
    // ==========================================

    botaoConfirmar.addEventListener(
        "click",
        async function () {

            if (!imagem || processando) {
                return;
            }

            processando = true;

            botaoConfirmar.disabled = true;

            botaoConfirmar.textContent =
                "Preparando foto...";

            try {

                // Gera um arquivo quadrado de 600x600.

                const saida = document.createElement(
                    "canvas"
                );

                saida.width = TAMANHO_FINAL;
                saida.height = TAMANHO_FINAL;

                desenhar(
                    saida.getContext("2d"),
                    TAMANHO_FINAL
                );

                // Tenta salvar com qualidade alta.
                let blob = await gerarBlob(
                    saida,
                    0.85
                );

                // Se necessário, comprime mais.
                if (blob && blob.size > MAX_FINAL) {

                    blob = await gerarBlob(
                        saida,
                        0.65
                    );
                }

                if (!blob || blob.size > MAX_FINAL) {

                    throw new Error(
                        "Não foi possível reduzir a imagem."
                    );
                }

                // Cria o arquivo leve.
                const fotoReduzida = new File(
                    [blob],
                    "perfil_" + Date.now() + ".jpg",
                    {
                        type: "image/jpeg"
                    }
                );

                // Coloca o arquivo reduzido no input.
                const transferencia = new DataTransfer();

                transferencia.items.add(
                    fotoReduzida
                );

                campo.files = transferencia.files;

                arquivoPronto = fotoReduzida;

                originalConfirmado = arquivoEmEdicao;

                atualizarPreview();

                processando = false;

                fecharEditor();

            } catch (erro) {

                console.error(erro);

                alert(
                    "Não conseguimos preparar essa foto. Escolha outra imagem."
                );

                restaurarInput();

            } finally {

                processando = false;

                botaoConfirmar.disabled = false;

                botaoConfirmar.textContent =
                    "✓ Usar esta foto";
            }

        }
    );

    // Evita envio enquanto a foto está sendo editada.
    const formulario = campo.closest("form");

    if (formulario) {

        formulario.addEventListener(
            "submit",
            function (evento) {

                if (
                    fundo.classList.contains("aberto") ||
                    processando
                ) {

                    evento.preventDefault();

                    alert(
                        "Confirme o ajuste da foto antes de continuar."
                    );
                }

            }
        );
    }

});
