<?php
// =================================================================
// LÓGICA PHP: EDIÇÃO DE ALUNO COM VALOR DE MENSALIDADE
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$aluno = null;
$message = '';

// 1. CARREGAR DADOS DO ALUNO EXISTENTE
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: alunos_list.php');
    exit;
}
$aluno_id = $_GET['id'];

// Busca os dados atuais do aluno (AGORA INCLUI valor_mensal)
try {
    $sql_select = "SELECT id, nome, data_nascimento, peso, kyu, telefone, email, valor_mensal,
                          numero_zempo, numero_fmj, academia_id,
                          tipo_sanguineo, nome_pai, nome_mae, telefone_pai, telefone_mae
                   FROM alunos WHERE id = :id";
    $stmt_select = $pdo->prepare($sql_select);
    $stmt_select->execute([':id' => $aluno_id]);
    $aluno = $stmt_select->fetch();

    if (!$aluno) {
        $message = '<p class="error">Aluno não encontrado!</p>';
    }
} catch (Exception $e) {
    $message = '<p class="error">Erro ao buscar dados: ' . $e->getMessage() . '</p>';
}


// 2. PROCESSAR O FORMULÁRIO DE EDIÇÃO (UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $aluno) {
    
    // 2.1. Filtra e Sanitiza os Dados
    $nome             = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $data_nascimento  = filter_input(INPUT_POST, 'data_nascimento', FILTER_SANITIZE_STRING);
    $peso             = filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT);
    $kyu              = filter_input(INPUT_POST, 'kyu', FILTER_SANITIZE_STRING);
    $telefone         = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $email            = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $numero_zempo     = filter_input(INPUT_POST, 'numero_zempo', FILTER_SANITIZE_STRING);
    $numero_fmj       = filter_input(INPUT_POST, 'numero_fmj', FILTER_SANITIZE_STRING);
    $tipo_sanguineo   = filter_input(INPUT_POST, 'tipo_sanguineo', FILTER_SANITIZE_STRING);
    $nome_pai         = filter_input(INPUT_POST, 'nome_pai', FILTER_SANITIZE_STRING);
    $nome_mae         = filter_input(INPUT_POST, 'nome_mae', FILTER_SANITIZE_STRING);
    $telefone_pai     = filter_input(INPUT_POST, 'telefone_pai', FILTER_SANITIZE_STRING);
    $telefone_mae     = filter_input(INPUT_POST, 'telefone_mae', FILTER_SANITIZE_STRING);
    // NOVO: Coleta e valida o novo valor da mensalidade
    $valor_mensal     = filter_input(INPUT_POST, 'valor_mensal', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION); 
    
    $aluno_id_post    = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT); 

    if ($aluno_id_post != $aluno_id) {
        $message = '<p class="error">Erro de segurança: ID do formulário inválido.</p>';
    }
    
    // 2.2. Validação básica
    if (!$nome || !$data_nascimento || !$kyu || !$email || $valor_mensal === false || $valor_mensal < 0) {
        $message = '<p class="error">Por favor, preencha todos os campos obrigatórios e o valor corretamente.</p>';
    } else {
        try {
            // 2.3. Prepara a Query SQL para UPDATE (AGORA INCLUI valor_mensal)
            $sql_update = "UPDATE alunos SET 
                            nome = :nome, 
                            data_nascimento = :nascimento, 
                            peso = :peso, 
                            kyu = :kyu, 
                            telefone = :telefone, 
                            email = :email,
                            valor_mensal = :valor_mensal,
                            numero_zempo = :numero_zempo,
                            numero_fmj = :numero_fmj,
                            tipo_sanguineo = :tipo_sanguineo,
                            nome_pai = :nome_pai,
                            nome_mae = :nome_mae,
                            telefone_pai = :telefone_pai,
                            telefone_mae = :telefone_mae
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
                ':valor_mensal'   => $valor_mensal,
                ':numero_zempo'   => $numero_zempo,
                ':numero_fmj'     => $numero_fmj,
                ':tipo_sanguineo' => $tipo_sanguineo,
                ':nome_pai'       => $nome_pai,
                ':nome_mae'       => $nome_mae,
                ':telefone_pai'   => $telefone_pai,
                ':telefone_mae'   => $telefone_mae,
                ':id'             => $aluno_id
            ]);

            $message = '<p class="success">OK Dados do aluno **' . htmlspecialchars($nome) . '** atualizados com sucesso!</p>';
            
            // Atualiza o array $aluno para que o formulário reflita a mudança
            $aluno['nome'] = $nome; 
            $aluno['data_nascimento'] = $data_nascimento;
            $aluno['peso'] = $peso;
            $aluno['kyu'] = $kyu;
            $aluno['telefone'] = $telefone;
            $aluno['email'] = $email;
            $aluno['valor_mensal'] = $valor_mensal;
            $aluno['numero_zempo'] = $numero_zempo;
            $aluno['numero_fmj'] = $numero_fmj;
            $aluno['tipo_sanguineo'] = $tipo_sanguineo;
            $aluno['nome_pai'] = $nome_pai;
            $aluno['nome_mae'] = $nome_mae;
            $aluno['telefone_pai'] = $telefone_pai;
            $aluno['telefone_mae'] = $telefone_mae;


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
    <link rel="icon" href="assets/favicon.png">
</head>

<body>

    <div class="main-wrapper">

        <?php include './includes/sidebar.php'; ?>

        <div class="content-area">

            <div class="container">

                <h1>Editar Aluno</h1>

                <?php 
                echo $message; 
                
                if ($aluno): 
                ?>

                <form method="POST" action="editar_aluno.php?id=<?php echo $aluno['id']; ?>" class="form-cadastro">

                    <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">

                    <div class="form-row">
                        <div class="form-group" style="flex: 2 1 350px;">
                            <label for="nome">Nome Completo:</label>
                            <input type="text" id="nome" name="nome"
                                value="<?php echo htmlspecialchars($aluno['nome']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="data_nascimento">Data de Nascimento:</label>
                            <input type="date" id="data_nascimento" name="data_nascimento"
                                value="<?php echo htmlspecialchars($aluno['data_nascimento']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="peso">Peso (kg):</label>
                            <input type="number" id="peso" name="peso" step="0.1" min="1"
                                value="<?php echo htmlspecialchars($aluno['peso']); ?>" placeholder="Ex: 75.5">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="kyu">Faixa (Kyu):</label>
                            <select id="kyu" name="kyu" required>
                                <option value="Branca" <?php if ($aluno['kyu'] == 'Branca') echo 'selected'; ?>>Branca</option>
                                <option value="Branca e Rosa" <?php if ($aluno['kyu'] == 'Branca e Rosa') echo 'selected'; ?>>Branca e Rosa</option>
                                <option value="Cinza" <?php if ($aluno['kyu'] == 'Cinza') echo 'selected'; ?>>Cinza</option>
                                <option value="Azul" <?php if ($aluno['kyu'] == 'Azul') echo 'selected'; ?>>Azul</option>
                                <option value="Amarela" <?php if ($aluno['kyu'] == 'Amarela') echo 'selected'; ?>>Amarela</option>
                                <option value="Laranja" <?php if ($aluno['kyu'] == 'Laranja') echo 'selected'; ?>>Laranja</option>
                                <option value="Verde" <?php if ($aluno['kyu'] == 'Verde') echo 'selected'; ?>>Verde</option>
                                <option value="Roxa" <?php if ($aluno['kyu'] == 'Roxa') echo 'selected'; ?>>Roxa</option>
                                <option value="Marrom" <?php if ($aluno['kyu'] == 'Marrom') echo 'selected'; ?>>Marrom</option>
                                <option value="Preta" <?php if ($aluno['kyu'] == 'Preta') echo 'selected'; ?>>Preta (Shodan)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="telefone">Telefone:</label>
                            <input type="tel" id="telefone" name="telefone"
                                value="<?php echo htmlspecialchars($aluno['telefone']); ?>" placeholder="(XX) XXXXX-XXXX">
                        </div>

                        <div class="form-group" style="flex: 2 1 300px;">
                            <label for="email">E-mail:</label>
                            <input type="email" id="email" name="email"
                                value="<?php echo htmlspecialchars($aluno['email']); ?>" required placeholder="aluno@exemplo.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="numero_zempo">Número do Zempo:</label>
                            <input type="text" id="numero_zempo" name="numero_zempo"
                                value="<?php echo htmlspecialchars($aluno['numero_zempo'] ?? ''); ?>"
                                placeholder="Ex: 123456">
                        </div>
                        <div class="form-group">
                            <label for="numero_fmj">Número da FMJ:</label>
                            <input type="text" id="numero_fmj" name="numero_fmj"
                                value="<?php echo htmlspecialchars($aluno['numero_fmj'] ?? ''); ?>"
                                placeholder="Ex: 987654">
                        </div>
                        <div class="form-group">
                            <label for="tipo_sanguineo">Tipo sanguineo:</label>
                            <input type="text" id="tipo_sanguineo" name="tipo_sanguineo"
                                value="<?php echo htmlspecialchars($aluno['tipo_sanguineo'] ?? ''); ?>" placeholder="Ex: O+">
                        </div>
                        <div class="form-group">
                            <label for="nome_pai">Nome do pai:</label>
                            <input type="text" id="nome_pai" name="nome_pai"
                                value="<?php echo htmlspecialchars($aluno['nome_pai'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="telefone_pai">Telefone do pai:</label>
                            <input type="tel" id="telefone_pai" name="telefone_pai"
                                value="<?php echo htmlspecialchars($aluno['telefone_pai'] ?? ''); ?>"
                                placeholder="(XX) XXXXX-XXXX">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome_mae">Nome da mae:</label>
                            <input type="text" id="nome_mae" name="nome_mae"
                                value="<?php echo htmlspecialchars($aluno['nome_mae'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="telefone_mae">Telefone da mae:</label>
                            <input type="tel" id="telefone_mae" name="telefone_mae"
                                value="<?php echo htmlspecialchars($aluno['telefone_mae'] ?? ''); ?>"
                                placeholder="(XX) XXXXX-XXXX">
                        </div>
                        <div class="form-group" style="flex: 0 1 200px;">
                            <label for="valor_mensal">Valor da Mensalidade (R$)</label>
                            <input type="number" step="0.01" min="0" id="valor_mensal" name="valor_mensal"
                                value="<?php echo htmlspecialchars($aluno['valor_mensal']); ?>" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Salvar Edições</button>

                    <p class="link-lista"><a href="alunos_list.php">Cancelar e Voltar para a Lista</a></p>
                </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>
