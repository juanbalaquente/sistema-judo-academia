<?php
// =================================================================
// LÓGICA PHP: PAINEL FINANCEIRO, LISTAGEM DE PENDÊNCIAS E LANÇAMENTO DE PAGAMENTO
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$message = '';
$pendencias = [];
$pagos = []; 
$hoje = date('Y-m-d');
$mes_atual = date('Y-m-01');

// 1. Variáveis para o formulário de lançamento Detalhado (via GET ou POST)
$launch_aluno_id = filter_input(INPUT_GET, 'aluno_id_lancamento', FILTER_SANITIZE_NUMBER_INT);
$launch_data_vencimento = filter_input(INPUT_GET, 'data_vencimento_lancamento', FILTER_SANITIZE_STRING);

// Variáveis para o FORMULÁRIO DE AÇÃO (preenchimento automático)
$aluno_para_lancamento = null;
$valor_padrao_mensal = 100.00; // Valor de fallback (se o aluno não tiver valor_mensal definido, embora o SQL já defina 100.00)

// 2. PROCESSAMENTO FINAL DO PAGAMENTO (Confirmado pelo Formulário Detalhado - POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_payment'])) {
    
    $aluno_id = filter_input(INPUT_POST, 'aluno_id', FILTER_SANITIZE_NUMBER_INT);
    $data_vencimento = filter_input(INPUT_POST, 'data_vencimento', FILTER_SANITIZE_STRING);
    // Deve ser um float para o banco de dados
    $valor_pago = filter_input(INPUT_POST, 'valor_pago', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION); 
    $data_pagamento = filter_input(INPUT_POST, 'data_pagamento', FILTER_SANITIZE_STRING) ?: $hoje;

    if ($valor_pago === false) {
        $message = '<p class="error">Valor do pagamento inválido. Use ponto para decimais, se necessário (Ex: 100.00)</p>';
    } else {
        try {
            $registrado_por = $_SESSION['username'] ?? 'Sistema';
            $status = 'pago';
            
            // 2.1. Tenta atualizar um status PENDENTE/ATRASADO para PAGO com o NOVO VALOR
            $sql = "UPDATE mensalidades SET 
                        status = :status, 
                        valor = :valor_pago,
                        data_pagamento = :data_pagamento,
                        registrado_por = :registrado_por
                    WHERE aluno_id = :aluno_id AND data_vencimento = :data_vencimento AND status != 'pago'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':status' => $status,
                ':valor_pago' => $valor_pago,
                ':data_pagamento' => $data_pagamento,
                ':registrado_por' => $registrado_por,
                ':aluno_id' => $aluno_id,
                ':data_vencimento' => $data_vencimento
            ]);

            if ($stmt->rowCount() > 0) {
                $message = '<p class="success">OK Pagamento de **R$ ' . number_format($valor_pago, 2, ',', '.') . '** registrado com sucesso!</p>';
            } else {
                // 2.2. Se não encontrou pendência, insere um novo registro (Pagamento antecipado)
                $sql_insert = "INSERT INTO mensalidades (aluno_id, valor, data_vencimento, data_pagamento, status, registrado_por) 
                                VALUES (:aluno_id, :valor, :data_vencimento, :data_pagamento, :status, :registrado_por)";
                
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->execute([
                    ':aluno_id' => $aluno_id,
                    ':valor' => $valor_pago,
                    ':data_vencimento' => $data_vencimento,
                    ':data_pagamento' => $data_pagamento,
                    ':status' => $status,
                    ':registrado_por' => $registrado_por
                ]);

                $message = '<p class="success">OK Pagamento antecipado de **R$ ' . number_format($valor_pago, 2, ',', '.') . '** registrado com sucesso!</p>';
            }

            // Limpa as variáveis GET para fechar o formulário de lançamento
            $launch_aluno_id = null;


        } catch (Exception $e) {
            if ($e->getCode() == 23000) {
                $message = '<p class="error">Atenção: Esta mensalidade já foi registrada como paga ou a data de referência está duplicada!</p>';
            } else {
                $message = '<p class="error">Erro ao registrar pagamento: ' . $e->getMessage() . '</p>';
            }
        }
    }
}

// 2.1. PROCESSAMENTO DE ISENCAO (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_exempt'])) {
    $aluno_id = filter_input(INPUT_POST, 'aluno_id', FILTER_SANITIZE_NUMBER_INT);
    $data_vencimento = filter_input(INPUT_POST, 'data_vencimento', FILTER_SANITIZE_STRING);
    $valor_pago = 0.00;
    $data_pagamento = $hoje;

    try {
        $registrado_por = $_SESSION['username'] ?? 'Sistema';
        $status = 'pago';

        $sql = "UPDATE mensalidades SET 
                    status = :status, 
                    valor = :valor_pago,
                    data_pagamento = :data_pagamento,
                    registrado_por = :registrado_por
                WHERE aluno_id = :aluno_id AND data_vencimento = :data_vencimento AND status != 'pago'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':status' => $status,
            ':valor_pago' => $valor_pago,
            ':data_pagamento' => $data_pagamento,
            ':registrado_por' => $registrado_por,
            ':aluno_id' => $aluno_id,
            ':data_vencimento' => $data_vencimento
        ]);

        if ($stmt->rowCount() == 0) {
            $sql_insert = "INSERT INTO mensalidades (aluno_id, valor, data_vencimento, data_pagamento, status, registrado_por) 
                            VALUES (:aluno_id, :valor, :data_vencimento, :data_pagamento, :status, :registrado_por)";
            
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                ':aluno_id' => $aluno_id,
                ':valor' => $valor_pago,
                ':data_vencimento' => $data_vencimento,
                ':data_pagamento' => $data_pagamento,
                ':status' => $status,
                ':registrado_por' => $registrado_por
            ]);
        }

        $message = '<p class="success">OK Mensalidade marcada como isenta.</p>';
        $launch_aluno_id = null;
    } catch (Exception $e) {
        $message = '<p class="error">Erro ao marcar isencao: ' . $e->getMessage() . '</p>';
    }
}


// 3. PRÉ-PREENCHIMENTO DO FORMULÁRIO DE LANÇAMENTO (GET)
if ($launch_aluno_id && $launch_data_vencimento) {
    try {
        // Busca os dados do aluno e o valor que está registrado na tabela de mensalidades
        $sql_aluno = "
            SELECT 
                a.nome, 
                a.valor_mensal, 
                m.valor as valor_previsto 
            FROM alunos a
            JOIN mensalidades m ON a.id = m.aluno_id
            WHERE a.id = :id AND m.data_vencimento = :data_vencimento
        ";
        $stmt_aluno = $pdo->prepare($sql_aluno);
        $stmt_aluno->execute([
            ':id' => $launch_aluno_id,
            ':data_vencimento' => $launch_data_vencimento
        ]);
        $aluno_para_lancamento = $stmt_aluno->fetch();

        if ($aluno_para_lancamento) {
            // Usa o valor PREVISTO da tabela 'mensalidades' (que veio do valor_mensal) para o formulário
            $valor_padrao_mensal = $aluno_para_lancamento['valor_previsto'] ?? $aluno_para_lancamento['valor_mensal'] ?? 100.00;
        }

    } catch (Exception $e) {
        // Se houver erro, ignora e o formulário de lançamento não será exibido
        $message = '<p class="error">Erro ao carregar dados para lançamento: ' . $e->getMessage() . '</p>';
        $launch_aluno_id = null;
    }
}


// 4. BUSCA GERAL DE PENDÊNCIAS E PAGOS
try {
    // 4.1. Lógica para simular a criação automática dos registros do mês (Se não existirem)
    // O valor do INSERT IGNORE agora puxa o valor_mensal da tabela alunos.
    $pdo->exec("
        INSERT IGNORE INTO mensalidades (aluno_id, valor, data_vencimento, status)
        SELECT id, valor_mensal, '{$mes_atual}', 'pendente' FROM alunos
        WHERE NOT EXISTS (
            SELECT 1 FROM mensalidades 
            WHERE aluno_id = alunos.id AND data_vencimento = '{$mes_atual}'
        )
    ");
    
    // Atualiza status para 'atrasado'
    $pdo->exec("
        UPDATE mensalidades SET status = 'atrasado' 
        WHERE data_vencimento < '{$hoje}' AND status = 'pendente'
    ");


    // 4.2. Busca Alunos com Pagamentos PENDENTES ou ATRASADOS
    $sql_pendencias = "
        SELECT 
            a.id, 
            a.nome, 
            m.data_vencimento, 
            m.valor, 
            m.status 
        FROM alunos a
        JOIN mensalidades m ON a.id = m.aluno_id
        WHERE m.status IN ('pendente', 'atrasado') 
        ORDER BY m.data_vencimento ASC, a.nome ASC
    ";
    $stmt_pendencias = $pdo->query($sql_pendencias);
    $pendencias = $stmt_pendencias->fetchAll();

    // 4.3. Busca Alunos com Pagamentos PAGOS para o MÊS ATUAL
    $sql_pagos = "
        SELECT 
            a.nome, 
            m.valor, 
            m.data_pagamento,
            m.data_vencimento
        FROM alunos a
        JOIN mensalidades m ON a.id = m.aluno_id
        WHERE m.status = 'pago' AND m.data_vencimento = '{$mes_atual}'
        ORDER BY a.nome ASC
    ";
    $stmt_pagos = $pdo->query($sql_pagos);
    $pagos = $stmt_pagos->fetchAll();


} catch (Exception $e) {
    $message = '<p class="error">Erro ao carregar dados financeiros: ' . $e->getMessage() . '</p>';
}

// Funções de formatação
function format_currency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Financeiro - Judô</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="icon" href="assets/favicon.png">
    <style>
    .tabela-pendencias th {
        background-color: #f39c12;
    }

    .tabela-pagos th {
        background-color: #27ae60;
    }

    .status-atrasado {
        background-color: #f8d7da;
        color: var(--color-danger);
        font-weight: bold;
    }

    .status-pendente {
        background-color: #fff3cd;
        color: #856404;
        font-weight: bold;
    }

    .status-pago {
        background-color: #d4edda;
        color: #155724;
    }
    .status-isento {
        background-color: #e2e3e5;
        color: #383d41;
        font-weight: bold;
    }

    .form-pagar-detalhado {
        background-color: #f9f9f9;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .form-pagar-detalhado .form-group {
        display: inline-block;
        margin-right: 20px;
    }

    .form-pagar-detalhado label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }

    .form-pagar-detalhado input[type="number"],
    .form-pagar-detalhado input[type="date"] {
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 3px;
    }

    .section-separator {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 2px solid #ccc;
    }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">
                <h1>Controle de Mensalidades</h1>
                <?php echo $message; ?>

                <?php if ($launch_aluno_id && $launch_data_vencimento && $aluno_para_lancamento): ?>
                <div class="form-pagar-detalhado">
                    <h2>Confirmar Pagamento: **<?php echo htmlspecialchars($aluno_para_lancamento['nome']); ?>**</h2>
                    <p>Referência: **<?php echo date('m/Y', strtotime($launch_data_vencimento)); ?>** (Vencimento:
                        <?php echo date('d/m/Y', strtotime($launch_data_vencimento)); ?>)</p>

                    <form method="POST" action="financeiro.php">
                        <input type="hidden" name="confirm_payment" value="1">
                        <input type="hidden" name="aluno_id" value="<?php echo $launch_aluno_id; ?>">
                        <input type="hidden" name="data_vencimento" value="<?php echo $launch_data_vencimento; ?>">

                        <div class="form-group">
                            <label for="valor_pago">Valor Pago (R$)</label>
                            <input type="number" step="0.01" min="0" id="valor_pago" name="valor_pago"
                                value="<?php echo htmlspecialchars(number_format($valor_padrao_mensal, 2, '.', '')); ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="data_pagamento">Data do Pagamento</label>
                            <input type="date" id="data_pagamento" name="data_pagamento" value="<?php echo $hoje; ?>"
                                required>
                        </div>

                        <button type="submit" class="btn-submit"
                            style="background-color: var(--color-success); margin-left: 20px; margin-top: 10px;">
                            Confirmar Lançamento
                        </button>
                        <a href="financeiro.php" class="btn-clear"
                            style="margin-left: 10px; margin-top: 10px; padding: 10px 15px; text-decoration: none;">Cancelar</a>
                    </form>
                </div>
                <?php endif; ?>

                <h2>Alunos com Pagamentos Pendentes/Atrasados (<?php echo count($pendencias); ?>)</h2>

                <?php if (count($pendencias) > 0): ?>
                <table class="tabela-alunos tabela-pendencias">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Referência</th>
                            <th>Vencimento</th>
                            <th>Valor Previsto (R$)</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendencias as $pendencia): ?>
                        <?php 
                                    $data_ref = date('m/Y', strtotime($pendencia['data_vencimento']));
                                    $vencimento_data = date('d/m/Y', strtotime($pendencia['data_vencimento']));
                                    $row_class = $pendencia['status'] == 'atrasado' ? 'status-atrasado' : 'status-pendente';
                                ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo htmlspecialchars($pendencia['nome']); ?></td>
                            <td><?php echo $data_ref; ?></td>
                            <td><?php echo $vencimento_data; ?></td>
                            <td><?php echo format_currency($pendencia['valor']); ?></td>
                            <td><?php echo ucfirst($pendencia['status']); ?></td>
                            <td>
                                <a href="?aluno_id_lancamento=<?php echo $pendencia['id']; ?>&data_vencimento_lancamento=<?php echo $pendencia['data_vencimento']; ?>"
                                    class="btn-acao editar" style="background-color: var(--color-success);">
                                    Lançar Pagamento
                                </a>
                                <form method="POST" action="financeiro.php" style="display:inline; margin-left: 6px;">
                                    <input type="hidden" name="mark_exempt" value="1">
                                    <input type="hidden" name="aluno_id" value="<?php echo $pendencia['id']; ?>">
                                    <input type="hidden" name="data_vencimento"
                                        value="<?php echo $pendencia['data_vencimento']; ?>">
                                    <button type="submit" class="btn-acao excluir"
                                        onclick="return confirm('Confirmar isencao desta mensalidade?');">
                                        Isentar
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="success">OK Nenhum aluno com pendências no momento!</p>
                <?php endif; ?>

                <div class="section-separator">
                    <h2>Mensalidades Pagas/Isentas (Ref. Mês Atual: <?php echo date('m/Y'); ?>) (<?php echo count($pagos); ?>)
                    </h2>

                    <?php if (count($pagos) > 0): ?>
                    <table class="tabela-alunos tabela-pagos">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Valor Pago (R$)</th>
                                <th>Data Pagamento</th>
                                <th>Mês de Referência</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $pago): ?>
                            <?php $is_isento = ((float)$pago['valor'] == 0.0); ?>
                            <tr class="<?php echo $is_isento ? 'status-isento' : 'status-pago'; ?>">
                                <td><?php echo htmlspecialchars($pago['nome']); ?></td>
                                <td><?php echo format_currency($pago['valor']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($pago['data_pagamento'])); ?></td>
                                <td><?php echo date('m/Y', strtotime($pago['data_vencimento'])); ?></td>
                                <td><?php echo $is_isento ? 'Isento' : 'Pago'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="info">Ainda não há pagamentos/isencoes registrados para o mês de **<?php echo date('m/Y'); ?>**.
                    </p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>

</html>
