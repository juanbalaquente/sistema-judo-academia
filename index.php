<?php
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$message = ''; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nome             = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $data_nascimento  = filter_input(INPUT_POST, 'data_nascimento', FILTER_SANITIZE_STRING);
    $peso             = filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT);
    $kyu              = filter_input(INPUT_POST, 'kyu', FILTER_SANITIZE_STRING);
    $telefone         = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $email            = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$nome || !$data_nascimento || !$kyu || !$email) {
        $message = '<p class="error">Por favor, preencha todos os campos obrigatórios corretamente.</p>';
    } else {
        try {
            $sql = "INSERT INTO alunos (nome, data_nascimento, peso, kyu, telefone, email) 
                    VALUES (:nome, :nascimento, :peso, :kyu, :telefone, :email)";
            
            $stmt= $pdo->prepare($sql);
            $stmt->execute([
                ':nome'       => $nome,
                ':nascimento' => $data_nascimento,
                ':peso'       => $peso,
                ':kyu'        => $kyu,
                ':telefone'   => $telefone,
                ':email'      => $email
            ]);

            $message = '<p class="success">🥋 Aluno **' . htmlspecialchars($nome) . '** cadastrado com sucesso!</p>';
            
        } catch (Exception $e) {
            if ($e->getCode() == 23000) {
                 $message = '<p class="error">Erro: O e-mail ou dado informado já existe no sistema.</p>';
            } else {
                 $message = '<p class="error">Erro ao cadastrar: ' . $e->getMessage() . '</p>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Alunos - Judô</title>
    <link rel="stylesheet" href="styles/main.css">
</head>

<body>

    <div class="main-wrapper">

        <?php include 'includes/sidebar.php'; ?>

        <div class="content-area">

            <div class="container">

                <h1>Cadastro de Alunos</h1>

                <?php 
                echo $message; 
                ?>

                <h2>Novo Cadastro</h2>
                <form method="POST" action="index.php" class="form-cadastro">

                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required>

                    <label for="data_nascimento">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" required>

                    <label for="peso">Peso (kg):</label>
                    <input type="number" id="peso" name="peso" step="0.1" min="1" placeholder="Ex: 75.5">

                    <label for="kyu">Faixa (Kyu):</label>
                    <select id="kyu" name="kyu" required>
                        <option value="">Selecione a Faixa</option>
                        <option value="Branca">Branca</option>
                        <option value="Cinza">Cinza</option>
                        <option value="Azul">Azul</option>
                        <option value="Amarela">Amarela</option>
                        <option value="Laranja">Laranja</option>
                        <option value="Verde">Verde</option>
                        <option value="Roxa">Roxa</option>
                        <option value="Marrom">Marrom</option>
                        <option value="Preta">Preta (Shodan)</option>
                    </select>

                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" placeholder="(XX) XXXXX-XXXX">

                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" required placeholder="aluno@exemplo.com">

                    <button type="submit" class="btn-submit">Cadastrar Aluno</button>
                </form>

            </div>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>

</html>