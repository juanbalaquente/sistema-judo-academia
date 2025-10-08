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
$alunos_disponiveis = []; // Lista de alunos que AINDA NÃO estão inscritos

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
        // Erro 23000/1062 é violação de chave única (aluno já inscrito)
        if ($e->getCode() == '23000' && strpos($e->getMessage(), 'uk_inscricao_unica') !== false) {
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

// B) Busca os Alunos Inscritos
if ($campeonato_id) {
    try {
        $sql = "SELECT 
                    i.id as inscricao_id, 
                    a.nome_completo, 
                    a.faixa, 
                    i.status_pagamento,
                    i.data_inscricao
                FROM inscricoes i
                JOIN alunos a ON i.aluno_id = a.id
                WHERE i.campeonato_id = :cid
                ORDER BY a.nome_completo ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cid' => $campeonato_id]);
        $inscritos = $stmt->fetchAll();
        
        // C) Busca os Alunos Disponíveis (que não estão inscritos)
        // Isso é feito para popular o campo de seleção de novos alunos.
        $sql_disponiveis = "SELECT id, nome_completo FROM alunos 
                            WHERE id NOT IN (SELECT aluno_id FROM inscricoes WHERE campeonato_id = :cid)
                            ORDER BY nome_completo ASC";
        $stmt_disponiveis = $pdo->prepare($sql_disponiveis);
        $stmt_disponiveis->execute([':cid' => $campeonato_id]);
        $alunos_disponiveis = $stmt_disponiveis->fetchAll();
        
    } catch (Exception $e) {
        $message .= '<p class="error">Erro ao carregar lista de inscritos/alunos.</p>';
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

                        <button type="submit" class="btn-submit"
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
                            <th>Inscrição em</th>
                            <th>Status de Pagamento</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inscritos as $inscrito): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($inscrito['nome_completo']); ?></td>
                            <td><?php echo htmlspecialchars($inscrito['faixa']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($inscrito['data_inscricao'])); ?></td>
                            <td
                                class="<?php echo ($inscrito['status_pagamento'] == 'pago') ? 'status-success' : 'status-danger'; ?>">
                                <?php echo ucfirst($inscrito['status_pagamento']); ?>
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
                                    <button type="submit" class="btn-acao editar">Marcar como Pago</button>
                                </form>
                                <?php else: ?>
                                <form method="POST"
                                    action="gerenciar_inscricoes.php?campeonato_id=<?php echo $campeonato_id; ?>"
                                    style="display:inline;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="inscricao_id"
                                        value="<?php echo $inscrito['inscricao_id']; ?>">
                                    <input type="hidden" name="novo_status" value="pendente">
                                    <button type="submit" class="btn-acao excluir">Marcar como Pendente</button>
                                </form>
                                <?php endif; ?>
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