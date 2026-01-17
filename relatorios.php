<?php
require 'includes/auth_check.php';
require 'includes/db_connect.php';

$message = '';
$alunos = [];
$mes = filter_input(INPUT_GET, 'mes', FILTER_SANITIZE_STRING) ?: date('Y-m');

try {
    $stmt = $pdo->query("SELECT id, nome FROM alunos ORDER BY nome ASC");
    $alunos = $stmt->fetchAll();
} catch (Exception $e) {
    $message = '<p class="error">Erro ao carregar alunos: ' . $e->getMessage() . '</p>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatorios</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="icon" href="assets/favicon.png">
    <style>
    .rel-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(260px, 1fr));
        gap: 20px;
    }
    .rel-card {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 18px;
    }
    .rel-card h2 { margin-top: 0; }
    @media (max-width: 900px) {
        .rel-grid { grid-template-columns: 1fr; }
    }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">
                <h1>Relatorios</h1>
                <?php if (!empty($message)) echo $message; ?>

                <div class="rel-grid">
                    <div class="rel-card">
                        <h2>Mensalidades do Mes</h2>
                        <form method="GET" action="relatorios.php">
                            <label for="mes">Mes (YYYY-MM)</label>
                            <input type="month" id="mes" name="mes" value="<?php echo htmlspecialchars($mes); ?>">
                            <button type="submit" class="btn-submit" style="margin-top: 10px;">Atualizar</button>
                        </form>
                        <p style="margin-top: 12px;">
                            <a class="btn-acao editar" href="relatorio_mensalidades.php?mes=<?php echo htmlspecialchars($mes); ?>">Abrir Relatorio</a>
                        </p>
                    </div>

                    <div class="rel-card">
                        <h2>Presenca do Mes</h2>
                        <p>Mes selecionado: <strong><?php echo htmlspecialchars($mes); ?></strong></p>
                        <p>
                            <a class="btn-acao editar" href="relatorio_presenca.php?mes=<?php echo htmlspecialchars($mes); ?>">Abrir Relatorio</a>
                        </p>
                    </div>

                    <div class="rel-card">
                        <h2>Historico do Aluno</h2>
                        <form method="GET" action="relatorio_aluno.php">
                            <label for="aluno_id">Aluno</label>
                            <select id="aluno_id" name="id" required>
                                <option value="">Selecione</option>
                                <?php foreach ($alunos as $a): ?>
                                <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-submit" style="margin-top: 10px;">Abrir Relatorio</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
