<?php
// includes/auth_check.php
session_start();

// Verifica se a variável de sessão 'loggedin' não está definida ou não é true
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Se o usuário não estiver logado, ele é redirecionado para a página de login
    header('Location: login.php');
    exit;
}

// Se o código chegar até aqui, o usuário está logado e pode continuar
// Você pode adicionar informações úteis, como o nome de usuário, ao escopo global
// $current_user_name = $_SESSION['username']; 
?>




<!-- // INSERT INTO usuarios (username, password_hash, nome)
VALUES ('admin', 'COLE A STRING LONGA DO HASH AQUI', 'Seu Nome Admin'); -->