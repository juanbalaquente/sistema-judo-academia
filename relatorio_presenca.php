<?php
require 'includes/auth_check.php';
require 'includes/db_connect.php';

$mes = filter_input(INPUT_GET, 'mes', FILTER_SANITIZE_STRING) ?: date('Y-m');
$registros = [];

try {
    $sql = "
        SELECT a.nome, a.kyu, COUNT(p.id) AS total_presencas
        FROM presencas p
        JOIN alunos a ON a.id = p.aluno_id
        WHERE p.status = 'presente'
          AND DATE_FORMAT(p.data_aula, '%Y-%m') = :mes
        GROUP BY a.id, a.nome, a.kyu
        ORDER BY total_presencas DESC, a.nome ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':mes' => $mes]);
    $registros = $stmt->fetchAll();
} catch (Exception $e) {
    $message = '<p class="error">Erro ao gerar relatorio: ' . $e->getMessage() . '</p>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatorio de Presenca</title>
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
                <h1>Relatorio de Presenca</h1>
                <p>Referencia: <strong><?php echo htmlspecialchars($mes); ?></strong></p>
                <?php if (!empty($message)) echo $message; ?>

                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Faixa</th>
                            <th>Total de Presencas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['nome']); ?></td>
                            <td><?php echo htmlspecialchars($r['kyu']); ?></td>
                            <td style="text-align:center;"><?php echo (int)$r['total_presencas']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
