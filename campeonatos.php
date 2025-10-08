<?php
// =================================================================
// LÓGICA PHP: MÓDULO DE GERENCIAMENTO DE CAMPEONATOS
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$message = '';
$campeonatos = [];

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
        $message = '<p class="success">🏆 Campeonato **' . htmlspecialchars($nome) . '** cadastrado com sucesso!</p>';
    } catch (Exception $e) {
        $message = '<p class="error">Erro ao cadastrar campeonato: ' . $e->getMessage() . '</p>';
    }
}

// 2. BUSCA GERAL DOS CAMPEONATOS
try {
    $sql = "SELECT id, nome, data_evento, local, taxa, status FROM campeonatos ORDER BY data_evento DESC";
    $stmt = $pdo->query($sql);
    $campeonatos = $stmt->fetchAll();
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
</head>

<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">
                <h1>Gerenciamento de Campeonatos</h1>
                <?php echo $message; ?>

                <h2>Cadastrar Novo Evento</h2>
                <form method="POST" action="campeonatos.php" class="form-cadastro"
                    style="border: 1px dashed #ccc; padding: 20px; border-radius: 5px; margin-bottom: 30px;">
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
                            <input type="text" id="local" name="local" required placeholder="Ex: Ginásio Municipal">
                        </div>
                        <div class="form-group">
                            <label for="taxa">Taxa de Inscrição (R$)</label>
                            <input type="number" step="0.01" min="0" id="taxa" name="taxa" value="0.00">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" style="width: 250px;">
                        Cadastrar Campeonato
                    </button>
                </form>

                <h2>Próximos Campeonatos</h2>

                <?php if (count($campeonatos) > 0): ?>
                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Data</th>
                            <th>Local</th>
                            <th>Taxa</th>
                            <th>Status</th>
                            <th>Inscrições</th>
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
</body>

</html>