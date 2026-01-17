<?php
// =================================================================
// LÓGICA PHP: MÓDULO DE GERENCIAMENTO DE CAMPEONATOS
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$message = '';
$campeonatos = [];
$stats = [
    'total' => 0,
    'aberto' => 0,
    'fechado' => 0,
    'realizado' => 0,
];

// 1. PROCESSAMENTO DO CADASTRO DE NOVO CAMPEONATO
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_campeonato') {
    
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $data_evento = filter_input(INPUT_POST, 'data_evento', FILTER_SANITIZE_STRING);
    $local = filter_input(INPUT_POST, 'local', FILTER_SANITIZE_STRING);
    $taxa = filter_input(INPUT_POST, 'taxa', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    try {
        $sql = "INSERT INTO campeonatos (nome, data_evento, local, taxa) VALUES (:nome, :data_evento, :local, :taxa)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':data_evento' => $data_evento,
            ':local' => $local,
            ':taxa' => $taxa
        ]);
        $message = '<p class="success">OK Campeonato **' . htmlspecialchars($nome) . '** cadastrado com sucesso!</p>';
    } catch (Exception $e) {
        $message = '<p class="error">Erro ao cadastrar campeonato: ' . $e->getMessage() . '</p>';
    }
}

// 2. BUSCA GERAL DOS CAMPEONATOS
try {
    $sql = "SELECT id, nome, data_evento, local, taxa, status FROM campeonatos ORDER BY data_evento DESC";
    $stmt = $pdo->query($sql);
    $campeonatos = $stmt->fetchAll();
    $stats['total'] = count($campeonatos);
    foreach ($campeonatos as $c) {
        $status = $c['status'] ?? '';
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
    }
} catch (Exception $e) {
    $message .= '<p class="error">Erro ao carregar lista de campeonatos: ' . $e->getMessage() . '</p>';
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
    <title>Gerenciamento de Campeonatos</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="icon" href="assets/favicon.png">
    <style>
    .camp-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 20px;
    }

    .camp-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(120px, 1fr));
        gap: 10px;
        width: 100%;
        margin-bottom: 20px;
    }

    .camp-kpi {
        background: #f7f7f7;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 12px 14px;
        text-align: center;
    }

    .camp-kpi strong {
        display: block;
        font-size: 1.4em;
        margin-bottom: 4px;
        color: var(--color-dark);
    }

    .camp-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .camp-card {
        background: #ffffff;
        border: 1px solid #eee;
        border-radius: 10px;
        padding: 20px;
    }

    .camp-card h2 {
        margin-top: 0;
        border-bottom: 1px dashed #ccc;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .camp-note {
        color: #666;
        font-size: 0.95em;
        margin-top: -8px;
        margin-bottom: 16px;
    }

    @media (max-width: 900px) {
        .camp-kpis {
            grid-template-columns: repeat(2, minmax(120px, 1fr));
        }
    }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">
                <div class="camp-header">
                    <div>
                        <h1>Gerenciamento de Campeonatos</h1>
                        <p class="camp-note">Cadastre eventos e acompanhe o status das competicoes.</p>
                    </div>
                </div>
                <?php echo $message; ?>

                <div class="camp-kpis">
                    <div class="camp-kpi">
                        <strong><?php echo $stats['total']; ?></strong>
                        Total
                    </div>
                    <div class="camp-kpi">
                        <strong><?php echo $stats['aberto']; ?></strong>
                        Abertos
                    </div>
                    <div class="camp-kpi">
                        <strong><?php echo $stats['fechado']; ?></strong>
                        Fechados
                    </div>
                    <div class="camp-kpi">
                        <strong><?php echo $stats['realizado']; ?></strong>
                        Realizados
                    </div>
                </div>

                <div class="camp-grid">
                    <div class="camp-card">
                        <h2>Novo Campeonato</h2>
                        <form method="POST" action="campeonatos.php" class="form-cadastro">
                            <input type="hidden" name="action" value="add_campeonato">

                            <div class="form-row">
                                <div class="form-group" style="flex: 2 1 300px;">
                                    <label for="nome">Nome do Campeonato</label>
                                    <input type="text" id="nome" name="nome" required>
                                </div>
                                <div class="form-group">
                                    <label for="data_evento">Data do Evento</label>
                                    <input type="date" id="data_evento" name="data_evento" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group" style="flex: 2 1 300px;">
                                    <label for="local">Local</label>
                                    <input type="text" id="local" name="local" required placeholder="Ex: Ginasio Municipal">
                                </div>
                                <div class="form-group">
                                    <label for="taxa">Taxa de Inscricao (R$)</label>
                                    <input type="number" step="0.01" min="0" id="taxa" name="taxa" value="0.00">
                                </div>
                            </div>

                            <button type="submit" class="btn-submit" style="width: 250px;">
                                Cadastrar Campeonato
                            </button>
                        </form>
                    </div>

                    <div class="camp-card">
                        <h2>Calendario de Campeonatos</h2>

                        <?php if (count($campeonatos) > 0): ?>
                        <table class="tabela-alunos">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Data</th>
                                    <th>Local</th>
                                    <th>Taxa</th>
                                    <th>Status</th>
                                    <th>Inscricoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($campeonatos as $campeonato): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($campeonato['nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($campeonato['data_evento'])); ?></td>
                                    <td><?php echo htmlspecialchars($campeonato['local']); ?></td>
                                    <td><?php echo format_currency($campeonato['taxa']); ?></td>
                                    <td
                                        class="<?php echo ($campeonato['status'] == 'aberto') ? 'status-success' : (($campeonato['status'] == 'fechado') ? 'status-danger' : ''); ?>">
                                        <?php echo ucfirst($campeonato['status']); ?>
                                    </td>
                                    <td>
                                        <a href="gerenciar_inscricoes.php?campeonato_id=<?php echo $campeonato['id']; ?>"
                                            class="btn-acao editar">
                                            Gerenciar
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="error">Nenhum campeonato cadastrado ainda.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>

</html>