<?php
// =================================================================
// 1. LÓGICA PHP: CONEXÃO, FILTRO E CONSULTA AO BANCO DE DADOS
// =================================================================

require 'includes/db_connect.php'; 

$alunos = []; 
$message = ''; 

// Variáveis para armazenar o estado atual do filtro
$search_nome = '';
$search_kyu = '';
$where_clauses = [];
$params = [];

// 1.1. Verifica e Captura os Dados do Filtro (se vierem da URL)
if (isset($_GET['nome']) && $_GET['nome'] !== '') {
    $search_nome = trim($_GET['nome']);
    // Adiciona a condição WHERE para buscar por nome. O LIKE permite busca parcial.
    $where_clauses[] = "nome LIKE :nome";
    $params[':nome'] = '%' . $search_nome . '%';
}

if (isset($_GET['kyu']) && $_GET['kyu'] !== '') {
    $search_kyu = $_GET['kyu'];
    // Adiciona a condição WHERE para buscar pela faixa exata.
    $where_clauses[] = "kyu = :kyu";
    $params[':kyu'] = $search_kyu;
}

// 1.2. Constrói a Query SQL
$sql = "SELECT id, nome, data_nascimento, peso, kyu, telefone, email FROM alunos";

// Se houver alguma cláusula WHERE (se o filtro foi aplicado)
if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// Ordena a lista
$sql .= " ORDER BY nome ASC";

try {
    // 1.3. Prepara e Executa a Consulta
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // 1.4. Busca todos os resultados
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
    /* Estilos adicionais específicos para a tabela e o filtro */
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

    .form-filtro {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        padding: 15px;
        background-color: #e9ecef;
        border-radius: 4px;
        align-items: flex-end;
    }

    .form-filtro input,
    .form-filtro select {
        flex-grow: 1;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .form-filtro button {
        padding: 8px 15px;
        background-color: #004d99;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
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

        <form method="GET" action="alunos_list.php" class="form-filtro">
            <div>
                <label for="nome">Filtrar por Nome:</label>
                <input type="text" id="nome" name="nome" placeholder="Digite o nome do aluno"
                    value="<?php echo htmlspecialchars($search_nome); ?>">
            </div>

            <div>
                <label for="kyu">Filtrar por Faixa (Kyu):</label>
                <select id="kyu" name="kyu">
                    <option value="">Todas as Faixas</option>
                    <option value="Branca" <?php if ($search_kyu == 'Branca') echo 'selected'; ?>>Branca</option>
                    <option value="Cinza" <?php if ($search_kyu == 'Cinza') echo 'selected'; ?>>Cinza</option>
                    <option value="Azul" <?php if ($search_kyu == 'Azul') echo 'selected'; ?>>Azul</option>
                    <option value="Amarela" <?php if ($search_kyu == 'Amarela') echo 'selected'; ?>>Amarela</option>
                    <option value="Laranja" <?php if ($search_kyu == 'Laranja') echo 'selected'; ?>>Laranja</option>
                    <option value="Verde" <?php if ($search_kyu == 'Verde') echo 'selected'; ?>>Verde</option>
                    <option value="Roxa" <?php if ($search_kyu == 'Roxa') echo 'selected'; ?>>Roxa</option>
                    <option value="Marrom" <?php if ($search_kyu == 'Marrom') echo 'selected'; ?>>Marrom</option>
                    <option value="Preta" <?php if ($search_kyu == 'Preta') echo 'selected'; ?>>Preta (Shodan)</option>
                </select>
            </div>

            <button type="submit">Pesquisar/Filtrar</button>
            <?php if ($search_nome !== '' || $search_kyu !== ''): ?>
            <a href="alunos_list.php" class="btn-clear"
                style="padding: 8px 15px; background-color: #dc3545; color: white; border-radius: 4px; text-decoration: none;">Limpar
                Filtro</a>
            <?php endif; ?>
        </form>


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
                    <th>Ações</th>
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
                    <td>
                        <a href="editar_aluno.php?id=<?php echo $aluno['id']; ?>" class="btn-acao editar">Editar</a> |
                        <a href="excluir_aluno.php?id=<?php echo $aluno['id']; ?>" class="btn-acao excluir"
                            onclick="return confirm('Tem certeza que deseja excluir este aluno?');">Excluir</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; padding: 20px; background-color: #fff3cd; border: 1px solid #ffeeba;">Nenhum aluno
            encontrado com o filtro aplicado.</p>
        <?php endif; ?>

    </div>

</body>

</html>