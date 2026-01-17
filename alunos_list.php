<?php
// =================================================================
// 1. LÓGICA PHP: AUTENTICAÇÃO, PAGINAÇÃO, FILTRO E CONSULTA
// =================================================================

require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$alunos = []; 
$message = ''; 
$has_foto = false;

// 1.2. CONFIGURAÇÃO DA PAGINAÇÃO
$limit = 15; 
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit; 

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
    
    $total_pages = ceil($total_alunos / $limit);

    // 1.6. CONSULTA FINAL (ADICIONANDO LIMIT E OFFSET)
    // A coluna valor_mensal não é usada, mas incluída para fins de referência
    $sql = "SELECT id, nome, data_nascimento, peso, kyu, telefone, email, valor_mensal, foto_path " . $sql_base . " ORDER BY nome ASC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    foreach ($params as $key => &$value) {
        if ($key != ':limit' && $key != ':offset') {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $alunos = $stmt->fetchAll();
    foreach ($alunos as $aluno_item) {
        if (!empty($aluno_item['foto_path'])) {
            $has_foto = true;
            break;
        }
    }

} catch (Exception $e) {
    $message = '<p class="error">Erro ao carregar a lista de alunos: ' . $e->getMessage() . '</p>';
}

function get_filter_url($page) {
    $query = $_GET;
    unset($query['p']); 
    $query['p'] = $page;
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
    <link rel="icon" href="assets/favicon.png">
</head>

<body>

    <div class="main-wrapper">

        <?php include './includes/sidebar.php'; ?>

        <div class="content-area">

            <div class="container">

                <h1>Lista de Alunos Cadastrados</h1>

                <?php echo $message; ?>

                <div style="text-align: right; margin-bottom: 20px;">
                    <a href="import_alunos.php" class="btn-submit"
                        style="width: auto; padding: 10px 20px; background-color: var(--color-primary); margin-right: 10px;">
                        Importar Alunos (CSV)
                    </a>
                    <a href="export_alunos.php" class="btn-submit"
                        style="width: auto; padding: 10px 20px; background-color: var(--color-success);">
                        Exportar Tabela Completa (CSV)
                    </a>
                </div>

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
                            <option value="Branca" <?php if ($search_kyu == 'Branca') echo 'selected'; ?>>Branca
                            </option>
                            <option value="Cinza" <?php if ($search_kyu == 'Cinza') echo 'selected'; ?>>Cinza</option>
                            <option value="Azul" <?php if ($search_kyu == 'Azul') echo 'selected'; ?>>Azul</option>
                            <option value="Amarela" <?php if ($search_kyu == 'Amarela') echo 'selected'; ?>>Amarela
                            </option>
                            <option value="Laranja" <?php if ($search_kyu == 'Laranja') echo 'selected'; ?>>Laranja
                            </option>
                            <option value="Verde" <?php if ($search_kyu == 'Verde') echo 'selected'; ?>>Verde</option>
                            <option value="Roxa" <?php if ($search_kyu == 'Roxa') echo 'selected'; ?>>Roxa</option>
                            <option value="Marrom" <?php if ($search_kyu == 'Marrom') echo 'selected'; ?>>Marrom
                            </option>
                            <option value="Preta" <?php if ($search_kyu == 'Preta') echo 'selected'; ?>>Preta (Shodan)
                            </option>
                        </select>
                    </div>

                    <button type="submit">Pesquisar/Filtrar</button>
                    <?php if ($search_nome !== '' || $search_kyu !== ''): ?>
                    <a href="alunos_list.php?p=<?php echo $page; ?>" class="btn-clear"
                        style="padding: 10px 15px; text-decoration: none;">Limpar Filtro</a>
                    <?php endif; ?>
                </form>

                <p style="text-align: center; margin-bottom: 5px;">
                    Total de alunos encontrados: **<?php echo $total_alunos; ?>**
                </p>

                <?php if (count($alunos) > 0): ?>
                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <?php if ($has_foto): ?>
                            <th>Foto</th>
                            <?php endif; ?>
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
                            <?php if ($has_foto): ?>
                            <td>
                                <?php if (!empty($aluno['foto_path'])): ?>
                                <a href="<?php echo htmlspecialchars($aluno['foto_path']); ?>" target="_blank">
                                    <img class="thumb" src="<?php echo htmlspecialchars($aluno['foto_path']); ?>"
                                        alt="Foto do aluno">
                                </a>
                                <?php else: ?>
                                ---
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td><a
                                    href="curriculo_judoca.php?id=<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome']); ?></a>
                            </td>
                            <td><?php echo htmlspecialchars($aluno['kyu']); ?></td>
                            <td><?php echo htmlspecialchars($aluno['peso']); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($aluno['data_nascimento']))); ?>
                            </td>
                            <td><?php echo htmlspecialchars($aluno['telefone']); ?></td>
                            <td><?php echo htmlspecialchars($aluno['email']); ?></td>
                            <td>
                                <a href="editar_aluno.php?id=<?php echo $aluno['id']; ?>"
                                    class="btn-acao editar">Editar</a> |
                                <a href="excluir_aluno.php?id=<?php echo $aluno['id']; ?>" class="btn-acao excluir"
                                    onclick="return confirm('Tem certeza que deseja excluir este aluno?');">Excluir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; padding: 20px; background-color: #fff3cd; border: 1px solid #ffeeba;">
                    Nenhum aluno encontrado com o filtro aplicado.</p>
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
        </div>
    </div>
</body>

</html>
