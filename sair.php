<?php

session_start();


// Remove todas as informações da sessão
$_SESSION = array();


// Destrói a sessão
session_destroy();


// Volta para a página de login
header("Location: entrar.php");
exit;

?>
