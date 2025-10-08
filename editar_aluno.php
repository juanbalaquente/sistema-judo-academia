<?php

require 'includes/auth_check.php'; 
// require 'includes/auth_check.php'; // Adicionar se estiver usando a autenticação

require 'includes/db_connect.php'; 

$aluno = null;
$message = '';

// =================================================================
// PARTE 1: CARREGAR DADOS DO ALUNO EXISTENTE
// =================================================================

// 1.1. Verifica o ID na URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: alunos_list.php');
    exit;
}
$aluno_id = $_GET['id'];

// 1.2. Busca os dados atuais do aluno
try {
    $sql_select = "SELECT * FROM alunos WHERE id = :id";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->execute([':id' => $aluno_id]);
    $aluno = $stmt_select->fetch();

    if (!$aluno) {
        $message = '<p class="error">Aluno não encontrado!</p>';
    }
} catch (Exception $e) {
    $message = '<p class="error">Erro ao buscar dados: ' . $e->getMessage() . '</p>';
}


// =================================================================
// PARTE 2: PROCESSAR O FORMULÁRIO DE EDIÇÃO (UPDATE)
// =================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST" && $aluno) {
    // 2.1. Filtra e Sanitiza os Dados
    $nome             = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $data_nascimento  = filter_input(INPUT_POST, 'data_nascimento', FILTER_SANITIZE_STRING);
    $peso             = filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT);
    $kyu              = filter_input(INPUT_POST, 'kyu', FILTER_SANITIZE_STRING);
    $telefone         = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $email            = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    $aluno_id_post    = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT); 

    if ($aluno_id_post != $aluno_id) {
        $message = '<p class="error">Erro de segurança: ID do formulário inválido.</p>';
    }
    
    // 2.2. Validação básica
    if (!$nome || !$data_nascimento || !$kyu || !$email) {
        $message = '<p class="error">Por favor, preencha todos os campos obrigatórios corretamente.</p>';
    } else {
        try {
            // 2.3. Prepara a Query SQL para UPDATE
            $sql_update = "UPDATE alunos SET 
                            nome = :nome, 
                            data_nascimento = :nascimento, 
                            peso = :peso, 
                            kyu = :kyu, 
                            telefone = :telefone, 
                            email = :email 
                           WHERE id = :id";
            
            $stmt_update = $pdo->prepare($sql_update);
            
            // 2.4. Executa o UPDATE
            $stmt_update->execute([
                ':nome'       => $nome,
                ':nascimento' => $data_nascimento,
                ':peso'       => $peso,
                ':kyu'        => $kyu,
                ':telefone'   => $telefone,
                ':email'      => $email,
                ':id'         => $aluno_id
            ]);

            $message = '<p class="success">✅ Dados do aluno **' . htmlspecialchars($nome) . '** atualizados com sucesso!</p>';
            
            // Atualiza o objeto $aluno com os novos dados para que o formulário reflita a mudança após o UPDATE
            $aluno['nome'] = $nome; 
            $aluno['data_nascimento'] = $data_nascimento;
            $aluno['peso'] = $peso;
            $aluno['kyu'] = $kyu;
            $aluno['telefone'] = $telefone;
            $aluno['email'] = $email;


        } catch (Exception $e) {
            $message = '<p class="error">Erro ao atualizar: ' . $e->getMessage() . '</p>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Aluno - Judô</title>
    <link rel="stylesheet" href="styles/main.css">
</head>

<body>

    <div class="container">
        <h1>Editar Aluno</h1>

        <?php 
        echo $message; 
        
        if ($aluno): 
        ?>

        <form method="POST" action="editar_aluno.php?id=<?php echo $aluno['id']; ?>" class="form-cadastro">

            <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">

            <label for="nome">Nome Completo:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($aluno['nome']); ?>" required>

            <label for="data_nascimento">Data de Nascimento:</label>
            <input type="date" id="data_nascimento" name="data_nascimento"
                value="<?php echo htmlspecialchars($aluno['data_nascimento']); ?>" required>

            <label for="peso">Peso (kg):</label>
            <input type="number" id="peso" name="peso" step="0.1" min="1"
                value="<?php echo htmlspecialchars($aluno['peso']); ?>" placeholder="Ex: 75.5">

            <label for="kyu">Faixa (Kyu):</label>
            <select id="kyu" name="kyu" required>
                <option value="Branca" <?php if ($aluno['kyu'] == 'Branca') echo 'selected'; ?>>Branca</option>
                <option value="Cinza" <?php if ($aluno['kyu'] == 'Cinza') echo 'selected'; ?>>Cinza</option>
                <option value="Azul" <?php if ($aluno['kyu'] == 'Azul') echo 'selected'; ?>>Azul</option>
                <option value="Amarela" <?php if ($aluno['kyu'] == 'Amarela') echo 'selected'; ?>>Amarela</option>
                <option value="Laranja" <?php if ($aluno['kyu'] == 'Laranja') echo 'selected'; ?>>Laranja</option>
                <option value="Verde" <?php if ($aluno['kyu'] == 'Verde') echo 'selected'; ?>>Verde</option>
                <option value="Roxa" <?php if ($aluno['kyu'] == 'Roxa') echo 'selected'; ?>>Roxa</option>
                <option value="Marrom" <?php if ($aluno['kyu'] == 'Marrom') echo 'selected'; ?>>Marrom</option>
                <option value="Preta" <?php if ($aluno['kyu'] == 'Preta') echo 'selected'; ?>>Preta (Shodan)</option>
            </select>

            <label for="telefone">Telefone:</label>
            <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($aluno['telefone']); ?>"
                placeholder="(XX) XXXXX-XXXX">

            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($aluno['email']); ?>"
                required placeholder="aluno@exemplo.com">

            <button type="submit" class="btn-submit">Salvar Edições</button>

            <p class="link-lista"><a href="alunos_list.php">Cancelar e Voltar para a Lista</a></p>
        </form>

        <?php endif; ?>
    </div>
</body>

</html>