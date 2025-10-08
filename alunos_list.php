<?php
// =================================================================
// 1. LÓGICA PHP: CONEXÃO E CONSULTA AO BANCO DE DADOS
// =================================================================

// Inclui o arquivo de conexão. A variável $pdo estará disponível aqui.
require 'includes/db_connect.php'; 

$alunos = []; // Array que armazenará os dados dos alunos
$message = ''; // Variável para mensagens de feedback

try {
    // 1.1. Prepara a Query SQL para selecionar todos os alunos
    // ORDER BY nome ASC ajuda a manter a lista organizada
    $sql = "SELECT id, nome, data_nascimento, peso, kyu, telefone, email FROM alunos ORDER BY nome ASC";
    
    // 1.2. Executa a consulta
    $stmt = $pdo->query($sql);

    // 1.3. Busca todos os resultados como um array associativo
    $alunos = $stmt->fetchAll();

} catch (Exception $e) {
    $message = '<p class="error">Erro ao carregar a lista de alunos: ' . $e->getMessage() . '</p>';
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Alunos - Judô</title>
    <link rel="stylesheet" href="styles/main.css">
    <style>
    /* Estilos adicionais específicos para a tabela (você pode colocar no main.css) */
    .tabela-alunos {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .tabela-alunos th,
    .tabela-alunos td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    .tabela-alunos th {
        background-color: #004d99;
        /* Azul do Judô */
        color: white;
    }

    .tabela-alunos tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    .tabela-alunos tr:hover {
        background-color: #ddd;
    }

    .link-cadastro {
        text-align: center;
        margin-bottom: 20px;
    }
    </style>
</head>

<body>

    <div class="container">
        <h1>Lista de Alunos Cadastrados</h1>

        <div class="link-cadastro">
            <a href="index.php">← Voltar para o Cadastro</a>
        </div>

        <?php 
        // Exibe mensagem de erro, se houver
        echo $message; 
        ?>

        <?php if (count($alunos) > 0): ?>
        <table class="tabela-alunos">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Faixa (Kyu)</th>
                    <th>Peso (kg)</th>
                    <th>Nascimento</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    // Loop PHP para percorrer o array $alunos e criar uma linha para cada um
                    foreach ($alunos as $aluno): 
                    ?>
                <tr>
                    <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                    <td><?php echo htmlspecialchars($aluno['kyu']); ?></td>
                    <td><?php echo htmlspecialchars($aluno['peso']); ?></td>
                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($aluno['data_nascimento']))); ?></td>
                    <td><?php echo htmlspecialchars($aluno['telefone']); ?></td>
                    <td><?php echo htmlspecialchars($aluno['email']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; padding: 20px; background-color: #fff3cd; border: 1px solid #ffeeba;">Nenhum aluno
            cadastrado ainda.</p>
        <?php endif; ?>

    </div>

</body>

</html>