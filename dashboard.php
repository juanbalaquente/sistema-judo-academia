<?php
require 'includes/auth_check.php';
require 'includes/db_connect.php';

$kpis = [
    'alunos_total' => 0,
    'ativos_mes' => 0,
    'inadimplentes' => 0,
    'freq_media' => 0,
    'campeonatos_mes' => 0
];
$proximos = [];

try {
    $kpis['alunos_total'] = (int)$pdo->query("SELECT COUNT(*) FROM alunos")->fetchColumn();
    $kpis['ativos_mes'] = (int)$pdo->query("
        SELECT COUNT(DISTINCT aluno_id)
        FROM presencas
        WHERE status = 'presente'
          AND DATE_FORMAT(data_aula, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
    ")->fetchColumn();
    $kpis['inadimplentes'] = (int)$pdo->query("
        SELECT COUNT(DISTINCT aluno_id)
        FROM mensalidades
        WHERE status = 'atrasado'
    ")->fetchColumn();
    $total_presencas = (int)$pdo->query("
        SELECT COUNT(*)
        FROM presencas
        WHERE status = 'presente'
          AND DATE_FORMAT(data_aula, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
    ")->fetchColumn();
    $kpis['freq_media'] = $kpis['alunos_total'] > 0 ? round($total_presencas / $kpis['alunos_total'], 1) : 0;
    $kpis['campeonatos_mes'] = (int)$pdo->query("
        SELECT COUNT(*)
        FROM campeonatos
        WHERE DATE_FORMAT(data_evento, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
    ")->fetchColumn();

    $stmt = $pdo->query("
        SELECT nome, data_evento, local, status
        FROM campeonatos
        WHERE data_evento >= CURDATE()
        ORDER BY data_evento ASC
        LIMIT 5
    ");
    $proximos = $stmt->fetchAll();
} catch (Exception $e) {
    $message = '<p class="error">Erro ao carregar dashboard: ' . $e->getMessage() . '</p>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Judo</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="icon" href="assets/favicon.png">
    <style>
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }
    .kpi-card {
        background: #ffffff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }
    .kpi-card strong {
        display: block;
        font-size: 1.8em;
        margin-bottom: 4px;
        color: var(--color-dark);
    }
    @media (max-width: 1100px) {
        .kpi-grid { grid-template-columns: repeat(2, minmax(160px, 1fr)); }
    }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">
                <h1>Dashboard</h1>
                <?php if (!empty($message)) echo $message; ?>

                <div class="kpi-grid">
                    <div class="kpi-card">
                        <strong><?php echo $kpis['alunos_total']; ?></strong>
                        Alunos cadastrados
                    </div>
                    <div class="kpi-card">
                        <strong><?php echo $kpis['ativos_mes']; ?></strong>
                        Ativos no mes
                    </div>
                    <div class="kpi-card">
                        <strong><?php echo $kpis['inadimplentes']; ?></strong>
                        Inadimplentes
                    </div>
                    <div class="kpi-card">
                        <strong><?php echo $kpis['freq_media']; ?></strong>
                        Frequencia media
                    </div>
                    <div class="kpi-card">
                        <strong><?php echo $kpis['campeonatos_mes']; ?></strong>
                        Campeonatos do mes
                    </div>
                </div>

                <h2>Proximos Campeonatos</h2>
                <?php if (count($proximos) > 0): ?>
                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Data</th>
                            <th>Local</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proximos as $c): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($c['nome']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($c['data_evento'])); ?></td>
                            <td><?php echo htmlspecialchars($c['local']); ?></td>
                            <td><?php echo ucfirst($c['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="info">Nenhum campeonato futuro cadastrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>
