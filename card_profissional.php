<?php

$foto = trim(
    $anuncio["FOTO"] ?? ""
);

$foto_usuario = trim(
    $anuncio["FOTO_USUARIO"] ?? ""
);

$descricao =
    $anuncio["DESCRICAO"] ?? "";

$descricao_curta =
    mb_strlen(
        $descricao,
        "UTF-8"
    ) > 140
    ?
    mb_substr(
        $descricao,
        0,
        140,
        "UTF-8"
    ) . "..."
    :
    $descricao;

$bairros_anuncio = [];

if (
    !empty($anuncio["BAIRRO"])
) {
    $bairros_anuncio =
        explode(
            ",",
            $anuncio["BAIRRO"]
        );
}

?>

<div class="col-md-6 col-lg-4">

    <div class="card-profissional">

        <?php if (
            $foto !== ""
            &&
            file_exists($foto)
        ): ?>

            <img
                src="<?= escapar($foto); ?>"
                class="foto-profissional"
                alt="Foto do serviço"
            >

        <?php elseif (
            $foto_usuario !== ""
            &&
            file_exists($foto_usuario)
        ): ?>

            <img
                src="<?= escapar($foto_usuario); ?>"
                class="foto-profissional"
                alt="Foto do profissional"
            >

        <?php else: ?>

            <div class="sem-foto">
                🧹
            </div>

        <?php endif; ?>



        <div class="p-4">

            <h5 class="nome-profissional">

                <?= escapar(
                    $anuncio["NOME"]
                ); ?>

            </h5>



            <div class="mb-3">

                <span class="badge text-bg-light">

                    🧹 Faxina

                </span>

            </div>



            <p class="descricao-profissional">

                <?= escapar(
                    $descricao_curta
                ); ?>

            </p>



            <?php if (
                !empty($bairros_anuncio)
            ): ?>

                <div class="mb-3">

                    <strong
                        style="
                            color:#126E68;
                            font-size:14px;
                        "
                    >

                        📍 Atende:

                    </strong>

                    <div class="mt-1">

                        <?php foreach (
                            $bairros_anuncio
                            as $bairro
                        ): ?>

                            <?php

                            $bairro = trim($bairro);

                            ?>

                            <?php if (
                                $bairro !== ""
                            ): ?>

                                <span class="bairro-tag">

                                    <?= escapar(
                                        $bairro
                                    ); ?>

                                </span>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>



            <?php if (
                $anuncio["PRECO"] !== null
                &&
                $anuncio["PRECO"] !== ""
            ): ?>

                <div class="preco mb-3">

                    A partir de R$

                    <?= number_format(
                        $anuncio["PRECO"],
                        2,
                        ",",
                        "."
                    ); ?>

                </div>

            <?php else: ?>

                <div class="preco mb-3">

                    💬 Preço a combinar

                </div>

            <?php endif; ?>



            <a
                href="entrar.php"
                class="btn btn-primary w-100"
            >

                👤 Ver profissional

            </a>

        </div>

    </div>

</div>