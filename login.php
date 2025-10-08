<?php
session_start();
require 'includes/db_connect.php'; 

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    try {
        // 1. Busca o usuário pelo username (com Prepared Statement)
        $sql = "SELECT id, password_hash, nome FROM usuarios WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            // 2. Verifica se a senha fornecida corresponde ao hash
            if (password_verify($password, $user['password_hash'])) {
                
                // Sucesso: Inicia a Sessão
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['nome']; // Armazena o nome para dar as boas-vindas
                
                // Redireciona para a página principal
                header('Location: alunos_list.php'); 
                exit;
            } else {
                $message = '<p class="error">Usuário ou senha incorretos.</p>';
            }
        } else {
            $message = '<p class="error">Usuário ou senha incorretos.</p>';
        }
    } catch (Exception $e) {
        $message = '<p class="error">Erro no sistema de login.</p>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Judô Admin</title>
    <link rel="stylesheet" href="styles/main.css">
    <style>
    /* Estilos específicos para o formulário de login */
    .container {
        max-width: 400px;
        margin-top: 100px;
        border-top: 5px solid #004d99;
        /* Detalhe azul do Judô */
    }

    .form-cadastro {
        padding: 0;
    }

    .form-login input {
        margin-bottom: 10px;
    }

    .form-login button {
        background-color: #004d99;
    }
    </style>
</head>

<body>
    <div class="container">
        <h1>Acesso Administrativo</h1>
        <?php echo $message; ?>

        <form method="POST" action="login.php" class="form-cadastro form-login">
            <label for="username">Usuário:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn-submit">Entrar</button>
        </form>
    </div>
</body>

</html>