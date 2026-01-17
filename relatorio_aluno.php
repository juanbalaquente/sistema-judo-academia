<?php
require 'includes/auth_check.php';
require 'includes/db_connect.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$aluno = null;
$presencas = [];
$mensalidades = [];
$graduacoes = [];
$campeonatos = [];

try {
    if ($id) {
        $stmt = $pdo->prepare("SELECT nome, kyu, data_nascimento, email, telefone, valor_mensal FROM alunos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $aluno = $stmt->fetch();

        $stmt_p = $pdo->prepare("
            SELECT YEAR(data_aula) AS ano, MONTH(data_aula) AS mes, COUNT(*) AS total
            FROM presencas
            WHERE aluno_id = :id AND status = 'presente'
            GROUP BY ano, mes
            ORDER BY ano DESC, mes DESC
        ");
        $stmt_p->execute([':id' => $id]);
        $presencas = $stmt_p->fetchAll();

        $stmt_m = $pdo->prepare("
            SELECT valor, status, data_vencimento, data_pagamento
            FROM mensalidades
            WHERE aluno_id = :id
            ORDER BY data_vencimento DESC
        ");
        $stmt_m->execute([':id' => $id]);
        $mensalidades = $stmt_m->fetchAll();

        $stmt_g = $pdo->prepare("SELECT faixa, data_exame, requisitos, observacoes FROM graduacoes WHERE aluno_id = :id ORDER BY data_exame DESC");
        $stmt_g->execute([':id' => $id]);
        $graduacoes = $stmt_g->fetchAll();

        $stmt_c = $pdo->prepare("
            SELECT c.nome, c.data_evento, c.local, i.status_pagamento, i.colocacao
            FROM inscricoes i
            JOIN campeonatos c ON c.id = i.campeonato_id
            WHERE i.aluno_id = :id
            ORDER BY c.data_evento DESC
        ");
        $stmt_c->execute([':id' => $id]);
        $campeonatos = $stmt_c->fetchAll();
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
    <title>Relatorio do Aluno</title>
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

                <h1>Relatorio do Aluno</h1>
                <?php if (!empty($message)) echo $message; ?>

                <?php if ($aluno): ?>
                <div class="info-card">
                    <h2><?php echo htmlspecialchars($aluno['nome']); ?></h2>
                    <p><strong>Faixa:</strong> <?php echo htmlspecialchars($aluno['kyu']); ?></p>
                    <p><strong>Nascimento:</strong> <?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?></p>
                    <p><strong>Contato:</strong> <?php echo htmlspecialchars($aluno['telefone']); ?> | <?php echo htmlspecialchars($aluno['email']); ?></p>
                    <p><strong>Mensalidade:</strong> R$ <?php echo number_format($aluno['valor_mensal'], 2, ',', '.'); ?></p>
                </div>

                <h2>Presenca por Mes</h2>
                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <th>Mes/Ano</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presencas as $p): ?>
                        <tr>
                            <td><?php echo str_pad($p['mes'], 2, '0', STR_PAD_LEFT) . '/' . $p['ano']; ?></td>
                            <td style="text-align:center;"><?php echo (int)$p['total']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2>Mensalidades</h2>
                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <th>Referencia</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Pagamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mensalidades as $m): ?>
                        <tr class="<?php echo $m['status'] === 'pago' ? 'status-pago' : ($m['status'] === 'atrasado' ? 'status-atrasado' : 'status-pendente'); ?>">
                            <td><?php echo date('m/Y', strtotime($m['data_vencimento'])); ?></td>
                            <td>R$ <?php echo number_format($m['valor'], 2, ',', '.'); ?></td>
                            <td><?php echo ucfirst($m['status']); ?></td>
                            <td><?php echo $m['data_pagamento'] ? date('d/m/Y', strtotime($m['data_pagamento'])) : '---'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2>Graduacoes</h2>
                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <th>Faixa</th>
                            <th>Data</th>
                            <th>Requisitos</th>
                            <th>Obs</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($graduacoes as $g): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($g['faixa']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($g['data_exame'])); ?></td>
                            <td><?php echo htmlspecialchars($g['requisitos'] ?? '---'); ?></td>
                            <td><?php echo htmlspecialchars($g['observacoes'] ?? '---'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2>Campeonatos</h2>
                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <th>Campeonato</th>
                            <th>Data</th>
                            <th>Local</th>
                            <th>Pagamento</th>
                            <th>Colocacao</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campeonatos as $c): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['nome']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($c['data_evento'])); ?></td>
                            <td><?php echo htmlspecialchars($c['local']); ?></td>
                            <td><?php echo ucfirst($c['status_pagamento']); ?></td>
                            <td><?php echo htmlspecialchars($c['colocacao'] ?? '---'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="error">Aluno nao encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>
