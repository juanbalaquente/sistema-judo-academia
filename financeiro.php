<?php
// =================================================================
// LÓGICA PHP: PAINEL FINANCEIRO E LISTAGEM DE PENDÊNCIAS E PAGOS
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$message = '';
$pendencias = [];
$pagos = []; // NOVO: Array para armazenar os pagamentos quitados
$hoje = date('Y-m-d');
$mes_atual = date('Y-m-01'); // Início do mês atual (para filtragem)

// Variáveis para lançamento rápido de pagamento
$aluno_id_lancamento = filter_input(INPUT_POST, 'aluno_id_lancamento', FILTER_SANITIZE_NUMBER_INT);
$data_vencimento_lancamento = filter_input(INPUT_POST, 'data_vencimento_lancamento', FILTER_SANITIZE_STRING);

// 1. PROCESSAMENTO RÁPIDO DE PAGAMENTO (Se o formulário for submetido)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $aluno_id_lancamento && $data_vencimento_lancamento) {
    try {
        $valor_mensal = 100.00; // Valor Padrão.
        $registrado_por = $_SESSION['username'] ?? 'Sistema';

        // 1.1. Tenta atualizar um status PENDENTE/ATRASADO para PAGO
        $sql = "UPDATE mensalidades SET 
                    status = 'pago', 
                    data_pagamento = :hoje,
                    registrado_por = :registrado_por
                WHERE aluno_id = :aluno_id AND data_vencimento = :data_vencimento AND status != 'pago'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':hoje' => $hoje,
            ':registrado_por' => $registrado_por,
            ':aluno_id' => $aluno_id_lancamento,
            ':data_vencimento' => $data_vencimento_lancamento
        ]);

        if ($stmt->rowCount() > 0) {
             $message = '<p class="success">✅ Pagamento registrado com sucesso!</p>';
        } else {
             // 1.2. Se não encontrou pendência, insere um novo registro de pagamento antecipado (Status PAGO)
             $sql_insert = "INSERT INTO mensalidades (aluno_id, valor, data_vencimento, data_pagamento, status, registrado_por) 
                            VALUES (:aluno_id, :valor, :data_vencimento, :data_pagamento, 'pago', :registrado_por)";
             
             $stmt_insert = $pdo->prepare($sql_insert);
             $stmt_insert->execute([
                 ':aluno_id' => $aluno_id_lancamento,
                 ':valor' => $valor_mensal,
                 ':data_vencimento' => $data_vencimento_lancamento,
                 ':data_pagamento' => $hoje,
                 ':registrado_por' => $registrado_por
             ]);

             $message = '<p class="success">✅ Pagamento antecipado registrado com sucesso!</p>';
        }


    } catch (Exception $e) {
        if ($e->getCode() == 23000) {
            $message = '<p class="error">Atenção: Esta mensalidade já foi registrada como paga!</p>';
        } else {
            $message = '<p class="error">Erro ao registrar pagamento: ' . $e->getMessage() . '</p>';
        }
    }
}

// 2. BUSCA GERAL DE ALUNOS COM PENDÊNCIAS E PAGOS
try {
    // 2.1. Lógica para simular a criação automática dos registros do mês (Se não existirem)
    $pdo->exec("
        INSERT IGNORE INTO mensalidades (aluno_id, valor, data_vencimento, status)
        SELECT id, 100.00, '{$mes_atual}', 'pendente' FROM alunos
        WHERE NOT EXISTS (
            SELECT 1 FROM mensalidades 
            WHERE aluno_id = alunos.id AND data_vencimento = '{$mes_atual}'
        )
    ");
    
    // Atualiza status para 'atrasado' se a data de vencimento for no passado e ainda for 'pendente'
    $pdo->exec("
        UPDATE mensalidades SET status = 'atrasado' 
        WHERE data_vencimento < '{$hoje}' AND status = 'pendente'
    ");


    // 2.2. Busca Alunos com Pagamentos PENDENTES ou ATRASADOS
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

    // 2.3. NOVO: Busca Alunos com Pagamentos PAGOS para o MÊS ATUAL
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

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Financeiro - Judô</title>
    <link rel="stylesheet" href="styles/main.css">
    <style>
    .tabela-pendencias th {
        background-color: #f39c12;
    }

    /* Laranja para Finanças */
    .tabela-pagos th {
        background-color: #27ae60;
    }

    /* Verde para Pagos */
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

    .form-pagar {
        margin: 0;
        padding: 0;
        display: inline;
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

                <h2>Alunos com Pagamentos Pendentes/Atrasados (<?php echo count($pendencias); ?>)</h2>

                <?php if (count($pendencias) > 0): ?>
                <table class="tabela-alunos tabela-pendencias">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Vencimento (Ref.)</th>
                            <th>Valor (R$)</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendencias as $pendencia): ?>
                        <?php 
                                    $vencimento_formatado = date('m/Y', strtotime($pendencia['data_vencimento']));
                                    $row_class = $pendencia['status'] == 'atrasado' ? 'status-atrasado' : 'status-pendente';
                                ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo htmlspecialchars($pendencia['nome']); ?></td>
                            <td><?php echo $vencimento_formatado; ?></td>
                            <td><?php echo number_format($pendencia['valor'], 2, ',', '.'); ?></td>
                            <td><?php echo ucfirst($pendencia['status']); ?></td>
                            <td>
                                <form method="POST" action="financeiro.php" class="form-pagar"
                                    onsubmit="return confirm('Confirmar pagamento de <?php echo $pendencia['nome']; ?> (Ref. <?php echo $vencimento_formatado; ?>)?');">
                                    <input type="hidden" name="aluno_id_lancamento"
                                        value="<?php echo $pendencia['id']; ?>">
                                    <input type="hidden" name="data_vencimento_lancamento"
                                        value="<?php echo $pendencia['data_vencimento']; ?>">
                                    <button type="submit" class="btn-acao editar"
                                        style="background-color: var(--color-success);">Marcar Pago</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="success">✅ Nenhum aluno com pendências no momento!</p>
                <?php endif; ?>

                <div class="section-separator">
                    <h2>Mensalidades Pagas (Ref. Mês Atual: <?php echo date('m/Y'); ?>) (<?php echo count($pagos); ?>)
                    </h2>

                    <?php if (count($pagos) > 0): ?>
                    <table class="tabela-alunos tabela-pagos">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Valor (R$)</th>
                                <th>Data Pagamento</th>
                                <th>Mês de Referência</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $pago): ?>
                            <tr class="status-pago">
                                <td><?php echo htmlspecialchars($pago['nome']); ?></td>
                                <td><?php echo number_format($pago['valor'], 2, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($pago['data_pagamento'])); ?></td>
                                <td><?php echo date('m/Y', strtotime($pago['data_vencimento'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="info">Ainda não há pagamentos registrados para o mês de **<?php echo date('m/Y'); ?>**.
                    </p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>

</html>