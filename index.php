<?php
require 'includes/auth_check.php'; 
// ... restante do seu código PHP ...

// ... (Resto do PHP do cadastro) ...
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
                // Exibe a mensagem de feedback (sucesso ou erro)
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