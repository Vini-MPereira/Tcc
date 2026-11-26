
<?php
// Reutilizado no cadastro e na troca de foto de perfil.

function salvar_foto_perfil(array $arquivo): string
{
    if (!isset($arquivo['error']) || is_array($arquivo['error'])) {
        throw new RuntimeException('Envio de imagem inválido.');
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            'Não foi possível receber a foto. Tente novamente.'
        );
    }

    $tamanho = (int) ($arquivo['size'] ?? 0);

    if ($tamanho < 1 || $tamanho > 3 * 1024 * 1024) {
        throw new RuntimeException(
            'A foto deve ter no máximo 3 MB.'
        );
    }

    $temporario = (string) ($arquivo['tmp_name'] ?? '');

    if ($temporario === '' || !is_uploaded_file($temporario)) {
        throw new RuntimeException(
            'O arquivo enviado não é válido.'
        );
    }

    $informacoes = @getimagesize($temporario);

    if (
        $informacoes === false ||
        $informacoes[0] < 1 ||
        $informacoes[1] < 1
    ) {
        throw new RuntimeException(
            'Selecione uma imagem válida.'
        );
    }

    if (
        $informacoes[0] > 6000 ||
        $informacoes[1] > 6000
    ) {
        throw new RuntimeException(
            'A imagem tem dimensões muito grandes.'
        );
    }

    $tipo = (new finfo(FILEINFO_MIME_TYPE))->file(
        $temporario
    );

    $extensoes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (
        !isset($extensoes[$tipo]) ||
        $informacoes['mime'] !== $tipo
    ) {
        throw new RuntimeException(
            'Envie uma foto JPG, PNG ou WebP.'
        );
    }

    // Cria automaticamente a pasta onde as fotos ficam.
    $diretorio = __DIR__ . '/uploads/perfis';

    if (
        !is_dir($diretorio) &&
        !mkdir($diretorio, 0755, true) &&
        !is_dir($diretorio)
    ) {
        throw new RuntimeException(
            'Não foi possível criar a pasta de fotos.'
        );
    }

    // Nome aleatório para evitar arquivos com nomes repetidos.
    $nome = bin2hex(random_bytes(16))
        . '.'
        . $extensoes[$tipo];

    $destino = $diretorio . '/' . $nome;

    if (!move_uploaded_file($temporario, $destino)) {
        throw new RuntimeException(
            'Não foi possível salvar a foto.'
        );
    }

    // Esse caminho será guardado no MySQL.
    return 'uploads/perfis/' . $nome;
}

// Remove somente fotos geradas por esse sistema.
function apagar_foto_perfil_local(string $caminho): void
{
    if (
        !preg_match(
            '~^uploads/perfis/[a-f0-9]{32}\.(jpg|png|webp)$~',
            $caminho
        )
    ) {
        return;
    }

    $arquivo = __DIR__ . '/' . $caminho;

    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}

