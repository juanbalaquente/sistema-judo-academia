<?php
session_start();
session_unset(); // Remove todas as variáveis de sessão
session_destroy(); // Destrói a sessão
header('Location: login.php'); // Redireciona para a tela de login
exit;
?>