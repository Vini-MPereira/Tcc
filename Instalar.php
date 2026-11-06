
<?php
// =====================================================
// LIMPA LAR - INSTALADOR DO BANCO DE DADOS
// =====================================================
// Pode ser executado novamente sem apagar os cadastros.
// NÃO restaura registros de outro computador.

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$usuario = "root";
$senha = "";
$nomeBanco = "plataforma_servicos";

$mensagens = [];
$avisos = [];
$contagens = [];
$erroInstalacao = "";

try {
    // =====================================================
    // CONECTA E CRIA O BANCO
    // =====================================================

    $conexao = new mysqli($host, $usuario, $senha);
    $conexao->set_charset("utf8mb4");

    $conexao->query("CREATE DATABASE IF NOT EXISTS `plataforma_servicos`
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $conexao->select_db($nomeBanco);

    $mensagens[] = "Banco de dados verificado.";

    // =====================================================
    // TABELA USUARIO
    // =====================================================

    $conexao->query(<<<SQL
        CREATE TABLE IF NOT EXISTS USUARIO (
            ID_USUARIO INT AUTO_INCREMENT PRIMARY KEY,
            NOME VARCHAR(150) NOT NULL,
            CPF VARCHAR(14) NOT NULL UNIQUE,
            TELEFONE VARCHAR(20),
            EMAIL VARCHAR(150) NOT NULL UNIQUE,
            SENHA VARCHAR(255) NOT NULL,
            FOTO VARCHAR(500),
            CEP VARCHAR(10),
            ESTADO VARCHAR(2),
            CIDADE VARCHAR(100),
            BAIRRO VARCHAR(100),
            ENDERECO VARCHAR(255),
            NUMERO VARCHAR(20),
            COMPLEMENTO VARCHAR(100),
            DATA_CADASTRO DATETIME DEFAULT CURRENT_TIMESTAMP,
            ATIVO BOOLEAN DEFAULT TRUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci
    SQL);

    // =====================================================
    // TABELA CATEGORIA
    // =====================================================

    $conexao->query(<<<SQL
        CREATE TABLE IF NOT EXISTS CATEGORIA (
            ID_CATEGORIA INT AUTO_INCREMENT PRIMARY KEY,
            NOME VARCHAR(100) NOT NULL UNIQUE,
            DESCRICAO TEXT,
            ATIVO BOOLEAN DEFAULT TRUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci
    SQL);

    // =====================================================
    // TABELA ANUNCIO
    // =====================================================

    $conexao->query(<<<SQL
        CREATE TABLE IF NOT EXISTS ANUNCIO (
            ID_ANUNCIO INT AUTO_INCREMENT PRIMARY KEY,
            ID_USUARIO INT NOT NULL,
            ID_CATEGORIA INT NOT NULL,
            TITULO VARCHAR(150) NOT NULL,
            DESCRICAO TEXT NOT NULL,
            PRECO DECIMAL(10,2) NULL,
            TIPO_PRECO ENUM('HORA', 'DIARIA', 'SERVICO')
                DEFAULT 'SERVICO',
            FOTO VARCHAR(500),
            CEP VARCHAR(10),
            ESTADO VARCHAR(2),
            CIDADE VARCHAR(100),
            BAIRRO VARCHAR(100),
            STATUS ENUM('ATIVO', 'PAUSADO', 'ENCERRADO')
                DEFAULT 'ATIVO',
            DATA_CADASTRO DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ID_USUARIO)
                REFERENCES USUARIO(ID_USUARIO)
                ON DELETE CASCADE,
            FOREIGN KEY (ID_CATEGORIA)
                REFERENCES CATEGORIA(ID_CATEGORIA)
                ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci
    SQL);

    // =====================================================
    // TABELA CONTRATACAO - SOLICITAÇÕES E FAXINAS
    // =====================================================
    // O preço é combinado fora do site.

    $conexao->query(<<<SQL
        CREATE TABLE IF NOT EXISTS CONTRATACAO (
            ID_CONTRATACAO INT AUTO_INCREMENT PRIMARY KEY,
            ID_ANUNCIO INT NOT NULL,
            ID_CONTRATANTE INT NOT NULL,
            ID_PROFISSIONAL INT NOT NULL,
            DATA_SERVICO DATE NOT NULL,
            HORA_SERVICO TIME,
            ENDERECO_SERVICO VARCHAR(255),
            NUMERO_SERVICO VARCHAR(20),
            BAIRRO_SERVICO VARCHAR(100),
            CIDADE_SERVICO VARCHAR(100),
            VALOR DECIMAL(10,2) NULL DEFAULT NULL,
            STATUS ENUM(
                'SOLICITADA',
                'ACEITA',
                'RECUSADA',
                'CANCELADA',
                'CONCLUIDA'
            ) DEFAULT 'SOLICITADA',
            OBSERVACAO TEXT,
            DATA_CONTRATACAO DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ID_ANUNCIO)
                REFERENCES ANUNCIO(ID_ANUNCIO)
                ON DELETE RESTRICT,
            FOREIGN KEY (ID_CONTRATANTE)
                REFERENCES USUARIO(ID_USUARIO)
                ON DELETE RESTRICT,
            FOREIGN KEY (ID_PROFISSIONAL)
                REFERENCES USUARIO(ID_USUARIO)
                ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci
    SQL);

    // =====================================================
    // TABELA AVALIACAO - NOTAS DE 1 A 5
    // =====================================================

    $conexao->query(<<<SQL
        CREATE TABLE IF NOT EXISTS AVALIACAO (
            ID_AVALIACAO INT AUTO_INCREMENT PRIMARY KEY,
            ID_CONTRATACAO INT NOT NULL,
            ID_AVALIADOR INT NOT NULL,
            ID_AVALIADO INT NOT NULL,
            NOTA TINYINT NOT NULL,
            COMENTARIO TEXT,
            DATA_AVALIACAO DATETIME DEFAULT CURRENT_TIMESTAMP,

            UNIQUE KEY uq_avaliacao_contratacao (
                ID_CONTRATACAO
            ),

            FOREIGN KEY (ID_CONTRATACAO)
                REFERENCES CONTRATACAO(ID_CONTRATACAO)
                ON DELETE CASCADE,

            FOREIGN KEY (ID_AVALIADOR)
                REFERENCES USUARIO(ID_USUARIO)
                ON DELETE RESTRICT,

            FOREIGN KEY (ID_AVALIADO)
                REFERENCES USUARIO(ID_USUARIO)
                ON DELETE RESTRICT,

            CONSTRAINT chk_nota_avaliacao
                CHECK (NOTA BETWEEN 1 AND 5)

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci
    SQL);

    // =====================================================
    // TABELA DISPONIBILIDADE - AGENDA DOS PROFISSIONAIS
    // =====================================================

    $conexao->query(<<<SQL
        CREATE TABLE IF NOT EXISTS DISPONIBILIDADE (
            ID_DISPONIBILIDADE INT AUTO_INCREMENT PRIMARY KEY,
            ID_USUARIO INT NOT NULL,

            DIA_SEMANA ENUM(
                'DOMINGO',
                'SEGUNDA',
                'TERCA',
                'QUARTA',
                'QUINTA',
                'SEXTA',
                'SABADO'
            ) NOT NULL,

            HORA_INICIO TIME NOT NULL,
            HORA_FIM TIME NOT NULL,

            ATIVO TINYINT(1) NOT NULL DEFAULT 1,

            INDEX idx_disp_usuario_dia (
                ID_USUARIO,
                DIA_SEMANA,
                ATIVO
            ),

            FOREIGN KEY (ID_USUARIO)
                REFERENCES USUARIO(ID_USUARIO)
                ON DELETE CASCADE,

            CONSTRAINT chk_horario_disponibilidade
                CHECK (HORA_FIM > HORA_INICIO)

        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci
    SQL);

    $mensagens[] =
        "As 6 tabelas foram criadas ou conferidas.";

    // =====================================================
    // COMPATIBILIDADE COM O BANCO ANTIGO
    // =====================================================
    // Permite preço a combinar, sem apagar valores antigos.

    $colunaValor = $conexao->query(
        "SHOW COLUMNS FROM CONTRATACAO LIKE 'VALOR'"
    );

    $definicaoValor = $colunaValor->fetch_assoc();

    if (!$definicaoValor) {
        throw new RuntimeException(
            "A tabela CONTRATACAO não possui a coluna VALOR."
        );
    }

    if (strtoupper($definicaoValor["Null"]) === "NO") {

        $conexao->query(
            "ALTER TABLE CONTRATACAO
             MODIFY COLUMN VALOR DECIMAL(10,2)
             NULL DEFAULT NULL"
        );

        $mensagens[] =
            "Campo VALOR atualizado para permitir preço a combinar.";
    }

    // =====================================================
    // PROTEÇÃO CONTRA AVALIAÇÃO DUPLICADA
    // =====================================================

    $indice = $conexao->query(
        "SHOW INDEX FROM AVALIACAO
         WHERE Key_name = 'uq_avaliacao_contratacao'"
    );

    if ($indice->num_rows === 0) {

        $duplicada = $conexao->query(
            "SELECT ID_CONTRATACAO
             FROM AVALIACAO
             GROUP BY ID_CONTRATACAO
             HAVING COUNT(*) > 1
             LIMIT 1"
        );

        if ($duplicada->num_rows === 0) {

            $conexao->query(
                "ALTER TABLE AVALIACAO
                 ADD UNIQUE KEY uq_avaliacao_contratacao
                 (ID_CONTRATACAO)"
            );

            $mensagens[] =
                "Proteção contra avaliação duplicada instalada.";

        } else {

            $avisos[] =
                "Existem avaliações duplicadas no banco antigo. " .
                "Elas foram preservadas; o índice único não foi adicionado.";
        }
    }

    // =====================================================
    // CADASTRA A CATEGORIA FAXINA
    // =====================================================
    // Só cadastra se ainda não existir.

    $conexao->query(
        "INSERT INTO CATEGORIA (NOME, DESCRICAO, ATIVO)
         SELECT
             'Faxina',
             'Serviços de limpeza residencial.',
             1
         WHERE NOT EXISTS (
             SELECT 1
             FROM CATEGORIA
             WHERE NOME = 'Faxina'
         )"
    );

    // =====================================================
    // CONFERE OS REGISTROS EXISTENTES
    // =====================================================

    $tabelas = [
        "USUARIO",
        "CATEGORIA",
        "ANUNCIO",
        "CONTRATACAO",
        "AVALIACAO",
        "DISPONIBILIDADE"
    ];

    foreach ($tabelas as $tabela) {

        $resultado = $conexao->query(
            "SELECT COUNT(*) AS total FROM `$tabela`"
        );

        $contagens[$tabela] =
            (int) $resultado->fetch_assoc()["total"];
    }

    $mensagens[] =
        "Instalação finalizada sem apagar os registros existentes.";

} catch (Throwable $erro) {

    error_log(
        "LimpaLar - Instalar.php: " . $erro->getMessage()
    );

    $erroInstalacao = $erro->getMessage();

} finally {

    if (
        isset($conexao) &&
        $conexao instanceof mysqli
    ) {
        $conexao->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Instalação do LimpaLar</title>

    <link
        rel="stylesheet"
        href="bootstrap/css/bootstrap.css">
</head>

<body style="background:#F4F9F7;color:#243438;">

    <main
        class="container py-5"
        style="max-width:850px;">

        <div class="card border-0 shadow-sm rounded-4">

            <div class="card-body p-4 p-md-5">

                <h1
                    class="fw-bold mb-2"
                    style="color:#126E68;">

                    🧹 Instalador do LimpaLar

                </h1>

                <p class="text-secondary">
                    Banco:
                    <strong>plataforma_servicos</strong>
                </p>

                <!-- RESULTADO DA INSTALAÇÃO -->

                <?php if ($erroInstalacao !== ""): ?>

                    <div
                        class="alert alert-danger"
                        role="alert">

                        <strong>
                            Não foi possível finalizar a instalação.
                        </strong>

                        <div class="mt-2">

                            <?= htmlspecialchars(
                                $erroInstalacao,
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>

                        </div>

                    </div>

                <?php else: ?>

                    <div
                        class="alert alert-success"
                        role="status">

                        <strong>
                            ✅ Banco pronto para usar!
                        </strong>

                        <div>
                            As seis tabelas estão disponíveis.
                        </div>

                    </div>

                <?php endif; ?>

                <!-- ETAPAS EXECUTADAS -->

                <?php foreach ($mensagens as $mensagem): ?>

                    <p class="mb-2">

                        ✅ <?= htmlspecialchars(
                            $mensagem,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>

                    </p>

                <?php endforeach; ?>

                <!-- AVISOS -->

                <?php foreach ($avisos as $aviso): ?>

                    <div
                        class="alert alert-warning"
                        role="alert">

                        <?= htmlspecialchars(
                            $aviso,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>

                    </div>

                <?php endforeach; ?>

                <!-- TABELAS E REGISTROS -->

                <?php if ($contagens): ?>

                    <h2 class="h5 mt-4">
                        Registros existentes no banco
                    </h2>

                    <div class="table-responsive">

                        <table
                            class="table table-striped align-middle">

                            <thead>

                                <tr>
                                    <th>Tabela</th>
                                    <th class="text-end">
                                        Registros
                                    </th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach (
                                    $contagens as $tabela => $total
                                ): ?>

                                    <tr>

                                        <td>
                                            <?= htmlspecialchars(
                                                $tabela,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>
                                        </td>

                                        <td class="text-end">
                                            <?= $total; ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

                <!-- INFORMAÇÃO IMPORTANTE -->

                <p class="small text-secondary mt-4">

                    O instalador cria a estrutura necessária,
                    mas não recupera usuários, anúncios ou
                    avaliações salvos em outro computador.

                    Para isso, guarde um backup SQL separado,
                    fora do GitHub público.

                </p>

                <!-- ABRIR O SITE -->

                <?php if ($erroInstalacao === ""): ?>

                    <a
                        class="btn text-white px-4"
                        style="background:#18A999;"
                        href="index.php">

                        Abrir LimpaLar

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </main>

</body>
</html>
