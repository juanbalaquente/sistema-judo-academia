<?php
// =================================================================
// LÓGICA PHP: GERENCIAMENTO DE INSCRIÇÕES
// =================================================================
require 'includes/auth_check.php';
require 'includes/db_connect.php';

$message = '';
$campeonato_id = filter_input(INPUT_GET, 'campeonato_id', FILTER_VALIDATE_INT);
$campeonato = null;
$inscritos = [];
$alunos_disponiveis = [];

if (!$campeonato_id) {
    header('Location: campeonatos.php');
    exit;
}

// =================================================================
// 1. FUNÇÕES DE CRUD
// =================================================================

// Processa Adicionar Nova Inscrição
function addInscricao($pdo, $campeonato_id) {
    $aluno_id = filter_input(INPUT_POST, 'aluno_id', FILTER_VALIDATE_INT);
    $status_pagamento = filter_input(INPUT_POST, 'status_pagamento', FILTER_SANITIZE_STRING);

    if (!$aluno_id || !in_array($status_pagamento, ['pago', 'pendente'])) {
        return '<p class="error">Dados de aluno ou pagamento inválidos.</p>';
    }

    try {
        $sql = "INSERT INTO inscricoes (campeonato_id, aluno_id, status_pagamento) VALUES (:cid, :aid, :status)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cid' => $campeonato_id, ':aid' => $aluno_id, ':status' => $status_pagamento]);
        
        return '<p class="success">Aluno inscrito com sucesso!</p>';
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
             return '<p class="error">Este aluno já está inscrito neste campeonato.</p>';
        }
        return '<p class="error">Erro ao inscrever aluno: ' . $e->getMessage() . '</p>';
    }
}

// Processa Atualizar Status de Pagamento
function updateStatusPagamento($pdo, $inscricao_id, $novo_status) {
    if (!in_array($novo_status, ['pago', 'pendente'])) {
        return '<p class="error">Status de pagamento inválido.</p>';
    }
    
    try {
        $sql = "UPDATE inscricoes SET status_pagamento = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':status' => $novo_status, ':id' => $inscricao_id]);
        return '<p class="success">Status de pagamento atualizado!</p>';
    } catch (Exception $e) {
        return '<p class="error">Erro ao atualizar pagamento: ' . $e->getMessage() . '</p>';
    }
}

// Processa Excluir Inscrição
function deleteInscricao($pdo, $inscricao_id) {
    try {
        $sql = "DELETE FROM inscricoes WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $inscricao_id]);
        
        if ($stmt->rowCount() > 0) {
            return '<p class="success">Inscrição excluída com sucesso!</p>';
        } else {
            return '<p class="error">Inscrição não encontrada ou já excluída.</p>';
        }
    } catch (Exception $e) {
        return '<p class="error">Erro ao excluir inscrição: ' . $e->getMessage() . '</p>';
    }
}

// NOVO: Processa a Atualização da Colocação
function updateColocacao($pdo, $inscricao_id, $colocacao) {
    try {
        // Aceita NULL, números ou strings (ex: '1º Lugar', 'Sem Classificação', NULL)
        $sql = "UPDATE inscricoes SET colocacao = :colocacao WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        // Filtra para garantir que o input seja tratado como string, se não for nulo
        $colocacao_value = !empty($colocacao) ? htmlspecialchars(trim($colocacao)) : NULL;
        $stmt->bindParam(':colocacao', $colocacao_value);
        $stmt->bindParam(':id', $inscricao_id);
        $stmt->execute();
        
        return '<p class="success">Colocação atualizada!</p>';
    } catch (Exception $e) {
        return '<p class="error">Erro ao atualizar colocação: ' . $e->getMessage() . '</p>';
    }
}


// =================================================================
// 2. PROCESSAMENTO POST
// =================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'add_inscricao') {
        $message = addInscricao($pdo, $campeonato_id);
    } elseif (isset($_POST['action']) && $_POST['action'] == 'update_status') {
        $inscricao_id = filter_input(INPUT_POST, 'inscricao_id', FILTER_VALIDATE_INT);
        $novo_status = filter_input(INPUT_POST, 'novo_status', FILTER_SANITIZE_STRING);
        if ($inscricao_id) {
            $message = updateStatusPagamento($pdo, $inscricao_id, $novo_status);
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'delete_inscricao') {
        $inscricao_id = filter_input(INPUT_POST, 'inscricao_id', FILTER_VALIDATE_INT);
        if ($inscricao_id) {
            $message = deleteInscricao($pdo, $inscricao_id);
        } else {
             $message = '<p class="error">ID de inscrição inválido para exclusão.</p>';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'update_colocacao') { // NOVO: AÇÃO DE COLOCAÇÃO
        $inscricao_id = filter_input(INPUT_POST, 'inscricao_id', FILTER_VALIDATE_INT);
        $colocacao = filter_input(INPUT_POST, 'colocacao', FILTER_SANITIZE_STRING);
        if ($inscricao_id) {
            $message = updateColocacao($pdo, $inscricao_id, $colocacao);
        }
    }
}

// =================================================================
// 3. BUSCA DOS DADOS NECESSÁRIOS
// =================================================================

// A) Busca os dados do Campeonato
try {
    $sql = "SELECT id, nome, data_evento, local, taxa, status FROM campeonatos WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $campeonato_id]);
    $campeonato = $stmt->fetch();

    if (!$campeonato) {
        $message .= '<p class="error">Campeonato não encontrado.</p>';
        $campeonato_id = null;
    }
} catch (Exception $e) {
    $message .= '<p class="error">Erro ao carregar dados do campeonato.</p>';
}

// B) Busca os Alunos Inscritos (AGORA INCLUI colocacao)
if ($campeonato_id) {
    try {
        $sql = "SELECT 
                    i.id as inscricao_id, 
                    a.nome,          
                    a.kyu,           
                    i.status_pagamento,
                    i.data_inscricao,
                    i.colocacao      /* NOVO: Colocação */
                FROM inscricoes i
                JOIN alunos a ON i.aluno_id = a.id
                WHERE i.campeonato_id = :cid
                ORDER BY a.nome ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cid' => $campeonato_id]);
        $inscritos = $stmt->fetchAll();
        
        // C) Busca os Alunos Disponíveis
        $sql_disponiveis = "SELECT id, nome FROM alunos 
                            WHERE id NOT IN (SELECT aluno_id FROM inscricoes WHERE campeonato_id = :cid)
                            ORDER BY nome ASC";
        $stmt_disponiveis = $pdo->prepare($sql_disponiveis);
        $stmt_disponiveis->execute([':cid' => $campeonato_id]);
        
        $alunos_disponiveis = array_map(function($aluno) {
            return ['id' => $aluno['id'], 'nome_completo' => $aluno['nome']];
        }, $stmt_disponiveis->fetchAll());
        
    } catch (Exception $e) {
        $message .= '<p class="error">Erro ao carregar lista de inscritos/alunos. (Detalhe: ' . $e->getMessage() . ')</p>';
    }
}

function format_currency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Inscrições - <?php echo htmlspecialchars($campeonato['nome'] ?? 'Campeonato'); ?></title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="icon" href="assets/favicon.png">
    <style>
    .header-info-box {
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 15px;
        margin-bottom: 25px;
        background-color: #f7f7f7;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1.1em;
    }

    .header-info-box p {
        margin: 0;
        padding: 0 10px;
    }

    .cadastrar-inscricao-box {
        padding: 20px;
        margin-bottom: 30px;
        border: 1px solid var(--color-primary);
        border-radius: 5px;
        background-color: #e0f7fa;
    }

    .cadastrar-inscricao-box h2 {
        margin-top: 0;
        color: var(--color-primary);
    }

    .colocacao-form {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .colocacao-form input {
        width: 80px;
        padding: 5px;
        text-align: center;
        font-size: 0.9em;
    }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">

                <p><a href="campeonatos.php" class="voltar-link">&larr; Voltar para a Lista de Campeonatos</a></p>

                <?php if ($campeonato): ?>
                <h1>Inscrições: **<?php echo htmlspecialchars($campeonato['nome']); ?>**</h1>
                <div class="header-info-box">
                    <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($campeonato['data_evento'])); ?></p>
                    <p><strong>Local:</strong> <?php echo htmlspecialchars($campeonato['local']); ?></p>
                    <p><strong>Taxa:</strong> <?php echo format_currency($campeonato['taxa']); ?></p>
                </div>

                <?php echo $message; ?>

                <div class="cadastrar-inscricao-box">
                    <h2>Adicionar Judoca</h2>
                    <form method="POST" action="gerenciar_inscricoes.php?campeonato_id=<?php echo $campeonato_id; ?>"
                        class="form-cadastro">
                        <input type="hidden" name="action" value="add_inscricao">

                        <div class="form-row">
                            <div class="form-group" style="flex: 2 1 300px;">
                                <label for="aluno_id">Selecionar Aluno</label>
                                <select id="aluno_id" name="aluno_id" required>
                                    <?php if (count($alunos_disponiveis) > 0): ?>
                                    <option value="">-- Selecione o Judoca --</option>
                                    <?php foreach ($alunos_disponiveis as $aluno): ?>
                                    <option value="<?php echo $aluno['id']; ?>">
                                        <?php echo htmlspecialchars($aluno['nome_completo']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <option value="" disabled>Todos os alunos já estão inscritos!</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status_pagamento">Status do Pagamento</label>
                                <select id="status_pagamento" name="status_pagamento" required>
                                    <option value="pendente">Pendente</option>
                                    <option value="pago">Pago</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" style="width: 250px;"
                            <?php echo (count($alunos_disponiveis) == 0) ? 'disabled' : ''; ?>>
                            Inscrever Judoca
                        </button>
                    </form>
                </div>


                <h2>Judocas Inscritos (<?php echo count($inscritos); ?>)</h2>

                <?php if (count($inscritos) > 0): ?>
                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <th>Nome do Judoca</th>
                            <th>Faixa</th>
                            <th>Status Pagamento</th>
                            <th>Colocação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscritos as $inscrito): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($inscrito['nome']); ?></td>
                            <td><?php echo htmlspecialchars($inscrito['kyu']); ?></td>
                            <td
                                class="<?php echo ($inscrito['status_pagamento'] == 'pago') ? 'status-success' : 'status-danger'; ?>">
                                <?php echo ucfirst($inscrito['status_pagamento']); ?>
                            </td>

                            <td>
                                <form method="POST"
                                    action="gerenciar_inscricoes.php?campeonato_id=<?php echo $campeonato_id; ?>"
                                    class="colocacao-form">
                                    <input type="hidden" name="action" value="update_colocacao">
                                    <input type="hidden" name="inscricao_id"
                                        value="<?php echo $inscrito['inscricao_id']; ?>">
                                    <input type="text" name="colocacao"
                                        value="<?php echo htmlspecialchars($inscrito['colocacao'] ?? ''); ?>"
                                        placeholder="Ex: 1º ou Bronze">
                                    <button type="submit" class="btn-acao editar"
                                        style="background-color: #3498db; color: white; padding: 5px 8px;">Salvar</button>
                                </form>
                            </td>

                            <td>
                                <?php if ($inscrito['status_pagamento'] == 'pendente'): ?>
                                <form method="POST"
                                    action="gerenciar_inscricoes.php?campeonato_id=<?php echo $campeonato_id; ?>"
                                    style="display:inline;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="inscricao_id"
                                        value="<?php echo $inscrito['inscricao_id']; ?>">
                                    <input type="hidden" name="novo_status" value="pago">
                                    <button type="submit" class="btn-acao editar"
                                        style="background-color: var(--color-success);">Marcar Pago</button>
                                </form>
                                <?php else: ?>
                                <form method="POST"
                                    action="gerenciar_inscricoes.php?campeonato_id=<?php echo $campeonato_id; ?>"
                                    style="display:inline;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="inscricao_id"
                                        value="<?php echo $inscrito['inscricao_id']; ?>">
                                    <input type="hidden" name="novo_status" value="pendente">
                                    <button type="submit" class="btn-acao excluir">Marcar Pendente</button>
                                </form>
                                <?php endif; ?>

                                <form method="POST"
                                    action="gerenciar_inscricoes.php?campeonato_id=<?php echo $campeonato_id; ?>"
                                    style="display:inline; margin-left: 5px;">
                                    <input type="hidden" name="action" value="delete_inscricao">
                                    <input type="hidden" name="inscricao_id"
                                        value="<?php echo $inscrito['inscricao_id']; ?>">
                                    <button type="submit" class="btn-acao excluir"
                                        onclick="return confirm('Tem certeza que deseja EXCLUIR a inscrição de <?php echo htmlspecialchars($inscrito['nome']); ?>?');">
                                        Excluir
                                    </button>
                                </form>

                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="error">Nenhum aluno inscrito neste campeonato ainda.</p>
                <?php endif; ?>

                <?php else: ?>
                <p class="error">Detalhes do campeonato não puderam ser carregados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>