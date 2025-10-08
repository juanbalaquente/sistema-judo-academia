<?php
// =================================================================
// 1. LÓGICA PHP: INCLUSÃO E PROCESSAMENTO DO FORMULÁRIO
// =================================================================
$message = ''; // Variável para armazenar mensagens de feedback

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Inclui o arquivo de conexão. A variável $pdo estará disponível aqui.
    require 'includes/db_connect.php'; 

    // 1.1. Filtra e Sanitiza os Dados
    // Usamos filter_input para uma coleta mais segura dos dados.
    $nome             = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $data_nascimento  = filter_input(INPUT_POST, 'data_nascimento', FILTER_SANITIZE_STRING);
    $peso             = filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT);
    $kyu              = filter_input(INPUT_POST, 'kyu', FILTER_SANITIZE_STRING);
    $telefone         = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $email            = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    // 1.2. Validação Básica (PHP Backend)
    if (!$nome || !$data_nascimento || !$kyu || !$email) {
        $message = '<p class="error">Por favor, preencha todos os campos obrigatórios corretamente.</p>';
    } else {
        try {
            // 1.3. Prepara a Query SQL (Obrigatório para segurança - Prepared Statements com PDO)
            $sql = "INSERT INTO alunos (nome, data_nascimento, peso, kyu, telefone, email) 
                    VALUES (:nome, :nascimento, :peso, :kyu, :telefone, :email)";
            
            $stmt = $pdo->prepare($sql);
            
            // 1.4. Executa a Inserção
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
            // Verifica se é um erro de duplicidade (ex: e-mail já existe)
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

    <div class="container">
        <h1>Sistema de Cadastro de Alunos - Judô</h1>

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

            <p class="link-lista"><a href="alunos_list.php">Ver Lista de Alunos</a></p>
        </form>
    </div>

    <scaript src="js/script.js">
        </script>
</body>
<?php
// =================================================================
// 1. LÓGICA PHP: INCLUSÃO E PROCESSAMENTO DO FORMULÁRIO
// =================================================================
$message = ''; // Variável para armazenar mensagens de feedback

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Inclui o arquivo de conexão. A variável $pdo estará disponível aqui.
    require 'includes/db_connect.php'; 

    // 1.1. Filtra e Sanitiza os Dados
    // Usamos filter_input para uma coleta mais segura dos dados.
    $nome             = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $data_nascimento  = filter_input(INPUT_POST, 'data_nascimento', FILTER_SANITIZE_STRING);
    $peso             = filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT);
    $kyu              = filter_input(INPUT_POST, 'kyu', FILTER_SANITIZE_STRING);
    $telefone         = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $email            = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    // 1.2. Validação Básica (PHP Backend)
    if (!$nome || !$data_nascimento || !$kyu || !$email) {
        $message = '<p class="error">Por favor, preencha todos os campos obrigatórios corretamente.</p>';
    } else {
        try {
            // 1.3. Prepara a Query SQL (Obrigatório para segurança - Prepared Statements com PDO)
            $sql = "INSERT INTO alunos (nome, data_nascimento, peso, kyu, telefone, email) 
                    VALUES (:nome, :nascimento, :peso, :kyu, :telefone, :email)";
            
            $stmt = $pdo->prepare($sql);
            
            // 1.4. Executa a Inserção
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
            // Verifica se é um erro de duplicidade (ex: e-mail já existe)
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

    <div class="container">
        <h1>Sistema de Cadastro de Alunos - Judô</h1>

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

            <p class="link-lista"><a href="alunos_list.php">Ver Lista de Alunos</a></p>
        </form>
    </div>

    <script src="js/script.js"></script>
</body>

</html>

</html>