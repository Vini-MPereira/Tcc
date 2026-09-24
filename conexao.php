<?php

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "plataforma_servicos";

$conexao = mysqli_connect(
    $host,
    $usuario,
    $senha,
    $banco
);

if (!$conexao) {

    die(
        "Erro ao conectar ao banco de dados: "
        . mysqli_connect_error()
    );

}

mysqli_set_charset($conexao, "utf8mb4");

?>
