<?php
require 'includes/auth_check.php';
require 'includes/db_connect.php';

$mes = filter_input(INPUT_GET, 'mes', FILTER_SANITIZE_STRING) ?: date('Y-m');
$registros = [];
$totais = ['pago' => 0, 'pendente' => 0, 'atrasado' => 0];

try {
    $sql = "
        SELECT a.nome, m.valor, m.status, m.data_vencimento, m.data_pagamento
        FROM mensalidades m
        JOIN alunos a ON a.id = m.aluno_id
        WHERE DATE_FORMAT(m.data_vencimento, '%Y-%m') = :mes
        ORDER BY a.nome ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':mes' => $mes]);
    $registros = $stmt->fetchAll();

    foreach ($registros as $r) {
        if (isset($totais[$r['status']])) {
            $totais[$r['status']] += (float)$r['valor'];
        }
    }
} catch (Exception $e) {
    $message = '<p class="error">Erro ao gerar relatorio: ' . $e->getMessage() . '</p>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatorio de Mensalidades</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="icon" href="assets/favicon.png">
    <style>
    .print-actions { margin-bottom: 20px; }
    @media print {
        .sidebar, .print-actions { display: none; }
        .content-area { padding: 0; }
        .container { box-shadow: none; border: none; }
    }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">
                <div class="print-actions">
                    <button class="btn-submit" onclick="window.print()">Imprimir / Salvar PDF</button>
                </div>
                <h1>Relatorio de Mensalidades</h1>
                <p>Referencia: <strong><?php echo htmlspecialchars($mes); ?></strong></p>
                <?php if (!empty($message)) echo $message; ?>

                <div class="form-row" style="margin-top: 10px;">
                    <div class="form-group">
                        <strong>Total Pago:</strong> R$ <?php echo number_format($totais['pago'], 2, ',', '.'); ?>
                    </div>
                    <div class="form-group">
                        <strong>Total Pendente:</strong> R$ <?php echo number_format($totais['pendente'], 2, ',', '.'); ?>
                    </div>
                    <div class="form-group">
                        <strong>Total Atrasado:</strong> R$ <?php echo number_format($totais['atrasado'], 2, ',', '.'); ?>
                    </div>
                </div>

                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Vencimento</th>
                            <th>Pagamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                        <tr class="<?php echo $r['status'] === 'pago' ? 'status-pago' : ($r['status'] === 'atrasado' ? 'status-atrasado' : 'status-pendente'); ?>">
                            <td><?php echo htmlspecialchars($r['nome']); ?></td>
                            <td>R$ <?php echo number_format($r['valor'], 2, ',', '.'); ?></td>
                            <td><?php echo ucfirst($r['status']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($r['data_vencimento'])); ?></td>
                            <td><?php echo $r['data_pagamento'] ? date('d/m/Y', strtotime($r['data_pagamento'])) : '---'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
