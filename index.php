<?php
// =================================================================
// LÓGICA PHP: CADASTRO DE ALUNO COM VALOR DE MENSALIDADE
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$message = ''; 
$nome = $data_nascimento = $peso = $kyu = $telefone = $email = $valor_mensal = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Coleta e Sanitização dos Dados
    $nome             = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $data_nascimento  = filter_input(INPUT_POST, 'data_nascimento', FILTER_SANITIZE_STRING);
    $peso             = filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT);
    $kyu              = filter_input(INPUT_POST, 'kyu', FILTER_SANITIZE_STRING);
    $telefone         = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $email            = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    // Coleta do valor, permitindo casas decimais.
    $valor_mensal     = filter_input(INPUT_POST, 'valor_mensal', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Se o campo não foi preenchido, define o padrão
    if (empty($valor_mensal) && $valor_mensal !== 0.00 && $valor_mensal !== '0') {
        $valor_mensal = 100.00; // Valor padrão
    }

    // 2. Validação Básica
    if (!$nome || !$data_nascimento || !$kyu || !$email) {
        $message = '<p class="error">Por favor, preencha todos os campos obrigatórios corretamente.</p>';
    } else {
        try {
            // 3. Prepara a Query SQL (agora inclui valor_mensal)
            $sql = "INSERT INTO alunos (nome, data_nascimento, peso, kyu, telefone, email, valor_mensal) 
                    VALUES (:nome, :nascimento, :peso, :kyu, :telefone, :email, :valor_mensal)";
            
            $stmt= $pdo->prepare($sql);
            
            // 4. Executa a Inserção com todos os parâmetros
            $stmt->execute([
                ':nome'           => $nome,
                ':nascimento'     => $data_nascimento,
                ':peso'           => $peso,
                ':kyu'            => $kyu,
                ':telefone'       => $telefone,
                ':email'          => $email,
                ':valor_mensal'   => $valor_mensal 
            ]);

            $message = '<p class="success">🥋 Aluno **' . htmlspecialchars($nome) . '** cadastrado com sucesso!</p>';
            
            // Limpa as variáveis para resetar o formulário após o sucesso
            $nome = $data_nascimento = $peso = $kyu = $telefone = $email = $valor_mensal = '';
            
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

        <?php include './includes/sidebar.php'; ?>

        <div class="content-area">

            <div class="container">

                <h1>Cadastro de Alunos</h1>

                <?php 
                echo $message; 
                ?>

                <h2>Novo Cadastro</h2>
                <form method="POST" action="index.php" class="form-cadastro">

                    <div class="form-row">
                        <div class="form-group" style="flex: 2 1 350px;"> <label for="nome">Nome Completo:</label>
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="data_nascimento">Data de Nascimento:</label>
                            <input type="date" id="data_nascimento" name="data_nascimento"
                                value="<?php echo htmlspecialchars($data_nascimento); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="peso">Peso (kg):</label>
                            <input type="number" id="peso" name="peso" step="0.1" min="1"
                                value="<?php echo htmlspecialchars($peso); ?>" placeholder="Ex: 75.5">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="kyu">Faixa (Kyu):</label>
                            <select id="kyu" name="kyu" required>
                                <option value="">Selecione a Faixa</option>
                                <option value="Branca" <?php if ($kyu == 'Branca') echo 'selected'; ?>>Branca</option>
                                <option value="Cinza" <?php if ($kyu == 'Cinza') echo 'selected'; ?>>Cinza</option>
                                <option value="Azul" <?php if ($kyu == 'Azul') echo 'selected'; ?>>Azul</option>
                                <option value="Amarela" <?php if ($kyu == 'Amarela') echo 'selected'; ?>>Amarela
                                </option>
                                <option value="Laranja" <?php if ($kyu == 'Laranja') echo 'selected'; ?>>Laranja
                                </option>
                                <option value="Verde" <?php if ($kyu == 'Verde') echo 'selected'; ?>>Verde</option>
                                <option value="Roxa" <?php if ($kyu == 'Roxa') echo 'selected'; ?>>Roxa</option>
                                <option value="Marrom" <?php if ($kyu == 'Marrom') echo 'selected'; ?>>Marrom</option>
                                <option value="Preta" <?php if ($kyu == 'Preta') echo 'selected'; ?>>Preta (Shodan)
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="telefone">Telefone:</label>
                            <input type="tel" id="telefone" name="telefone"
                                value="<?php echo htmlspecialchars($telefone); ?>" placeholder="(XX) XXXXX-XXXX">
                        </div>

                        <div class="form-group" style="flex: 2 1 300px;"> <label for="email">E-mail:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                                required placeholder="aluno@exemplo.com">
                        </div>
                    </div>

                    <div class="form-row" style="align-items: flex-end;">
                        <div class="form-group" style="flex: 0 1 200px;"> <label for="valor_mensal">Valor da Mensalidade
                                (R$)</label>
                            <input type="number" step="0.01" min="0" id="valor_mensal" name="valor_mensal"
                                value="<?php echo htmlspecialchars($valor_mensal); ?>" placeholder="100.00 (Opcional)">
                        </div>

                        <div class="form-group" style="flex: 0 1 200px;"> <button type="submit"
                                class="btn-submit">Cadastrar Aluno</button>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>

</html>