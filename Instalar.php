<?php

// =====================================================
// CONFIGURAÇÕES DO MYSQL
// =====================================================

$host = "localhost";
$usuario = "root";
$senha = "";

$nomeBanco = "plataforma_servicos";


// =====================================================
// CONEXÃO COM O MYSQL
// =====================================================

$conexao = mysqli_connect($host, $usuario, $senha);

if (!$conexao) {
    die("Erro ao conectar ao MySQL: " . mysqli_connect_error());
}


// =====================================================
// CRIA O BANCO DE DADOS
// =====================================================

$sql = "CREATE DATABASE IF NOT EXISTS `$nomeBanco`
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci";

if (!mysqli_query($conexao, $sql)) {
    die("Erro ao criar o banco de dados: " . mysqli_error($conexao));
}


// =====================================================
// SELECIONA O BANCO
// =====================================================

if (!mysqli_select_db($conexao, $nomeBanco)) {
    die("Erro ao selecionar o banco de dados: " . mysqli_error($conexao));
}


// Define UTF-8
mysqli_set_charset($conexao, "utf8mb4");


// =====================================================
// TABELA USUARIO
// =====================================================

$sql = "CREATE TABLE IF NOT EXISTS USUARIO (

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

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci";


if (!mysqli_query($conexao, $sql)) {
    die("Erro ao criar tabela USUARIO: " . mysqli_error($conexao));
}


// =====================================================
// TABELA CATEGORIA
// =====================================================

$sql = "CREATE TABLE IF NOT EXISTS CATEGORIA (

    ID_CATEGORIA INT AUTO_INCREMENT PRIMARY KEY,

    NOME VARCHAR(100) NOT NULL UNIQUE,

    DESCRICAO TEXT,

    ATIVO BOOLEAN DEFAULT TRUE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci";


if (!mysqli_query($conexao, $sql)) {
    die("Erro ao criar tabela CATEGORIA: " . mysqli_error($conexao));
}


// =====================================================
// TABELA ANUNCIO
// =====================================================

$sql = "CREATE TABLE IF NOT EXISTS ANUNCIO (

    ID_ANUNCIO INT AUTO_INCREMENT PRIMARY KEY,

    ID_USUARIO INT NOT NULL,

    ID_CATEGORIA INT NOT NULL,

    TITULO VARCHAR(150) NOT NULL,

    DESCRICAO TEXT NOT NULL,

    PRECO DECIMAL(10,2),

    TIPO_PRECO ENUM(
        'HORA',
        'DIARIA',
        'SERVICO'
    ) DEFAULT 'SERVICO',

    FOTO VARCHAR(500),

    CEP VARCHAR(10),

    ESTADO VARCHAR(2),

    CIDADE VARCHAR(100),

    BAIRRO VARCHAR(100),

    STATUS ENUM(
        'ATIVO',
        'PAUSADO',
        'ENCERRADO'
    ) DEFAULT 'ATIVO',

    DATA_CADASTRO DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ID_USUARIO)
        REFERENCES USUARIO(ID_USUARIO)
        ON DELETE CASCADE,

    FOREIGN KEY (ID_CATEGORIA)
        REFERENCES CATEGORIA(ID_CATEGORIA)
        ON DELETE RESTRICT

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci";


if (!mysqli_query($conexao, $sql)) {
    die("Erro ao criar tabela ANUNCIO: " . mysqli_error($conexao));
}


// =====================================================
// TABELA CONTRATACAO
// =====================================================

$sql = "CREATE TABLE IF NOT EXISTS CONTRATACAO (

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

    VALOR DECIMAL(10,2) NOT NULL,

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

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci";


if (!mysqli_query($conexao, $sql)) {
    die("Erro ao criar tabela CONTRATACAO: " . mysqli_error($conexao));
}


// =====================================================
// TABELA AVALIACAO
// =====================================================

$sql = "CREATE TABLE IF NOT EXISTS AVALIACAO (

    ID_AVALIACAO INT AUTO_INCREMENT PRIMARY KEY,

    ID_CONTRATACAO INT NOT NULL,

    ID_AVALIADOR INT NOT NULL,

    ID_AVALIADO INT NOT NULL,

    NOTA TINYINT NOT NULL,

    COMENTARIO TEXT,

    DATA_AVALIACAO DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ID_CONTRATACAO)
        REFERENCES CONTRATACAO(ID_CONTRATACAO)
        ON DELETE CASCADE,

    FOREIGN KEY (ID_AVALIADOR)
        REFERENCES USUARIO(ID_USUARIO)
        ON DELETE RESTRICT,

    FOREIGN KEY (ID_AVALIADO)
        REFERENCES USUARIO(ID_USUARIO)
        ON DELETE RESTRICT,

    CHECK (NOTA >= 1 AND NOTA <= 5)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci";


if (!mysqli_query($conexao, $sql)) {
    die("Erro ao criar tabela AVALIACAO: " . mysqli_error($conexao));
}


// =====================================================
// FINALIZA
// =====================================================

echo "<h2>Instalação concluída!</h2>";

echo "<p>Banco de dados: <strong>$nomeBanco</strong></p>";

echo "<p>Tabelas criadas/verificadas:</p>";

echo "<ul>";
echo "<li>USUARIO</li>";
echo "<li>CATEGORIA</li>";
echo "<li>ANUNCIO</li>";
echo "<li>CONTRATACAO</li>";
echo "<li>AVALIACAO</li>";
echo "</ul>";

echo "<p>O sistema está pronto para ser utilizado.</p>";


mysqli_close($conexao);

?>
