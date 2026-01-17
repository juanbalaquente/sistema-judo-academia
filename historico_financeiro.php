<?php
// =================================================================
// LÓGICA PHP: HISTÓRICO FINANCEIRO INDIVIDUAL DO ALUNO
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$aluno = null;
$historico_mensalidades = [];
$message = '';

// 1. VERIFICAÇÃO DO ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: alunos_list.php');
    exit;
}
$aluno_id = (int)$_GET['id'];

// Funções de formatação (repetidas aqui por segurança, mas o ideal seria ter um arquivo de helpers)
function format_currency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

try {
    // 2. BUSCA DADOS BÁSICOS DO ALUNO (INCLUINDO DADOS FINANCEIROS)
    $sql_aluno = "SELECT nome, kyu, data_nascimento, email, telefone, valor_mensal FROM alunos WHERE id = :id";
    $stmt_aluno = $pdo->prepare($sql_aluno);
    $stmt_aluno->execute([':id' => $aluno_id]);
    $aluno = $stmt_aluno->fetch();

    if (!$aluno) {
        $message = '<p class="error">Aluno não encontrado.</p>';
        
    } else {
        // 3. CONSULTA TODO O HISTÓRICO DE MENSALIDADES DO ALUNO
        $sql_historico = "
            SELECT 
                valor, 
                data_vencimento, 
                data_pagamento, 
                status
            FROM mensalidades
            WHERE aluno_id = :aluno_id 
            ORDER BY data_vencimento DESC
        ";
        $stmt_historico = $pdo->prepare($sql_historico);
        $stmt_historico->execute([':aluno_id' => $aluno_id]);
        $historico_mensalidades = $stmt_historico->fetchAll();
    }

} catch (Exception $e) {
    $message = '<p class="error">Erro ao carregar o histórico financeiro: ' . $e->getMessage() . '</p>';
}

// Helper para estilizar o status
function get_status_class($status) {
    switch ($status) {
        case 'pago': return 'status-pago';
        case 'atrasado': return 'status-atrasado';
        case 'pendente': return 'status-pendente';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro: <?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?></title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="icon" href="assets/favicon.png">
    <style>
    .profile-card {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #eee;
        margin-bottom: 25px;
    }

    .profile-card h2 {
        margin-top: 0;
        border-bottom: 1px dashed #ccc;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .profile-details p {
        margin: 5px 0;
        font-size: 1.1em;
    }

    .finance-status {
        background-color: #f0f8ff;
        padding: 15px;
        border-radius: 5px;
        margin-top: 15px;
    }

    .finance-status strong {
        color: var(--color-primary);
    }

    .status-pago {
        color: #155724;
    }

    .status-atrasado {
        color: var(--color-danger);
    }

    .status-pendente {
        color: #856404;
    }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">

                <?php if ($aluno): ?>
                <h1>Histórico Financeiro: **<?php echo htmlspecialchars($aluno['nome']); ?>**</h1>
                <p><a href="alunos_list.php">&larr; Voltar para a Lista de Alunos</a> | <a href="financeiro.php">&larr; Painel
                        Financeiro</a></p>

                <?php echo $message; ?>

                <div class="profile-card">
                    <h2>Informações do Aluno</h2>
                    <div class="profile-details">
                        <p><strong>Faixa:</strong> <?php echo htmlspecialchars($aluno['kyu']); ?></p>
                        <p><strong>Nascimento:</strong>
                            <?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?></p>
                        <p><strong>Contato:</strong> <?php echo htmlspecialchars($aluno['telefone']); ?>
                            (<?php echo htmlspecialchars($aluno['email']); ?>)</p>

                        <div class="finance-status">
                            <h3>Informações de Gestão</h3>
                            <p><strong>Valor Mensal Padrão:</strong> <span
                                    style="color: var(--color-success); font-weight: bold;"><?php echo format_currency($aluno['valor_mensal']); ?></span>
                            </p>
                            <p>
                                <strong>Último Status:</strong>
                                <?php if (!empty($historico_mensalidades)): ?>
                                <span
                                    class="<?php echo get_status_class($historico_mensalidades[0]['status']); ?>"><?php echo ucfirst($historico_mensalidades[0]['status']); ?></span>
                                (Ref.
                                <?php echo date('m/Y', strtotime($historico_mensalidades[0]['data_vencimento'])); ?>)
                                <?php else: ?>
                                <span style="color: gray;">Sem Histórico de Pagamentos.</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <h2>Histórico Completo de Mensalidades</h2>

                <?php if (count($historico_mensalidades) > 0): ?>
                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <th>Mês de Referência</th>
                            <th>Valor Cobrado/Pago</th>
                            <th>Data de Pagamento</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico_mensalidades as $registro): ?>
                        <tr class="<?php echo get_status_class($registro['status']); ?>">
                            <td><?php echo date('m/Y', strtotime($registro['data_vencimento'])); ?></td>
                            <td><?php echo format_currency($registro['valor']); ?></td>
                            <td><?php echo $registro['data_pagamento'] ? date('d/m/Y', strtotime($registro['data_pagamento'])) : '---'; ?>
                            </td>
                            <td><strong><?php echo ucfirst($registro['status']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="error">Nenhum registro de mensalidade encontrado para este aluno.</p>
                <?php endif; ?>

                <?php else: ?>
                <?php echo $message; ?>
                <p><a href="alunos_list.php">&larr; Voltar para a Lista</a></p>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>

</html>