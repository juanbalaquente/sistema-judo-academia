<?php
session_start();
require 'includes/db_connect.php'; 

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    try {
        // 1. Busca o usuário pelo username
        $sql = "SELECT id, password_hash, nome FROM usuarios WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Sucesso: Inicia a Sessão
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['nome']; 
            
            header('Location: alunos_list.php'); 
            exit;
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
    <link rel="icon" href="assets/favicon.png">
</head>

<body>

    <div class="login-wrapper">
        <div class="container container-login">
            <img class="logo" src="assets/logo.png" alt="Judo Sao Geraldo">
            <h1>Acesso Administrativo</h1>
            <?php echo $message; ?>

            <form method="POST" action="login.php" class="form-cadastro form-login">

                <div class="form-row">
                    <div class="form-group" style="flex: 1 1 100%;">
                        <label for="username">Usuário:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 1 1 100%;">
                        <label for="password">Senha:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit btn-login">Entrar</button>
            </form>
        </div>
    </div>
</body>

</html>
