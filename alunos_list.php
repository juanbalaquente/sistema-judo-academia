<?php
// =================================================================
// 1. LÓGICA PHP: AUTENTICAÇÃO, PAGINAÇÃO, FILTRO E CONSULTA
// =================================================================

// 1.1. Inclui o 'guarda' de segurança. Redireciona para o login se não estiver logado.
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$alunos = []; 
$message = ''; 

// 1.2. CONFIGURAÇÃO DA PAGINAÇÃO
$limit = 15; // Número de alunos por página
// Captura a página atual (p), garantindo que seja um número inteiro e mínimo de 1.
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit; // Ponto de partida para a consulta (ex: (1-1)*15=0, (2-1)*15=15)

// Variáveis e arrays para a construção da consulta filtrada
$search_nome = '';
$search_kyu = '';
$where_clauses = [];
$params = [];

// 1.3. Verifica e Captura os Dados do Filtro
if (isset($_GET['nome']) && $_GET['nome'] !== '') {
    $search_nome = trim($_GET['nome']);
    $where_clauses[] = "nome LIKE :nome";
    $params[':nome'] = '%' . $search_nome . '%';
}

if (isset($_GET['kyu']) && $_GET['kyu'] !== '') {
    $search_kyu = $_GET['kyu'];
    $where_clauses[] = "kyu = :kyu";
    $params[':kyu'] = $search_kyu;
}

// 1.4. CONSTRUÇÃO DA QUERY BASE (COM FILTRO, MAS SEM LIMIT/OFFSET)
$sql_base = "FROM alunos";
if (count($where_clauses) > 0) {
    $sql_base .= " WHERE " . implode(' AND ', $where_clauses);
}

try {
    // 1.5. CALCULAR O TOTAL DE REGISTROS (para a paginação)
    $sql_count = "SELECT COUNT(id) AS total " . $sql_base;
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_alunos = $stmt_count->fetchColumn(); 
    
    // Calcula o número total de páginas
    $total_pages = ceil($total_alunos / $limit);

    // 1.6. CONSULTA FINAL (ADICIONANDO LIMIT E OFFSET)
    $sql = "SELECT id, nome, data_nascimento, peso, kyu, telefone, email " . $sql_base . " ORDER BY nome ASC LIMIT :limit OFFSET :offset";
    
    // Configura os parâmetros de LIMIT e OFFSET como INT
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    // Bind dos outros parâmetros do filtro (se existirem)
    foreach ($params as $key => &$value) {
        if ($key != ':limit' && $key != ':offset') {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $alunos = $stmt->fetchAll();

} catch (Exception $e) {
    $message = '<p class="error">Erro ao carregar a lista de alunos: ' . $e->getMessage() . '</p>';
}

// 1.7. Helper function para gerar o URL do filtro e paginação
function get_filter_url($page) {
    $query = $_GET;
    // Remove a página atual do array de query params, para não duplicar.
    unset($query['p']); 
    // Adiciona a nova página
    $query['p'] = $page;
    // Constrói a string da URL: ?nome=ABC&kyu=Branca&p=2
    return '?' . http_build_query($query);
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
    /* Estilos adicionais para a tabela, filtro e paginação */
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

    .btn-acao {
        text-decoration: none;
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 0.9em;
    }

    .editar {
        background-color: #ffc107;
        color: #333;
    }

    .excluir {
        background-color: #dc3545;
        color: white;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
        gap: 5px;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #ddd;
        text-decoration: none;
        color: #004d99;
        border-radius: 4px;
    }

    .pagination .current-page {
        background-color: #004d99;
        color: white;
        border-color: #004d99;
        font-weight: bold;
    }
    </style>
</head>

<body>

    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1>Lista de Alunos Cadastrados</h1>
            <p>
                Olá, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuário'); ?></strong>!
                | <a href="logout.php">Sair</a>
            </p>
        </div>

        <div class="link-cadastro">
            <a href="index.php">← Voltar para o Cadastro</a>
        </div>

        <?php echo $message; ?>

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
            <a href="alunos_list.php?p=<?php echo $page; ?>" class="btn-clear"
                style="padding: 8px 15px; background-color: #dc3545; color: white; border-radius: 4px; text-decoration: none;">Limpar
                Filtro</a>
            <?php endif; ?>
        </form>

        <p style="text-align: center; margin-bottom: 5px;">
            Total de alunos encontrados: **<?php echo $total_alunos; ?>**
        </p>

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

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="<?php echo get_filter_url($page - 1); ?>">Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
            <span class="current-page"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="<?php echo get_filter_url($i); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
            <a href="<?php echo get_filter_url($page + 1); ?>">Próxima</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</body>

</html>