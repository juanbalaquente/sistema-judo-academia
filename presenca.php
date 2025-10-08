<?php
// =================================================================
// LÓGICA PHP: REGISTRO DE PRESENÇA E LISTAGEM DE ALUNOS
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$message = '';
$alunos = [];
$data_aula = date('Y-m-d'); // Data padrão é a de hoje

// Se o usuário selecionou uma data via GET (para mudar o dia)
if (isset($_GET['data']) && !empty($_GET['data'])) {
    $data_aula = filter_input(INPUT_GET, 'data', FILTER_SANITIZE_STRING);
}

// 1. PROCESSAMENTO DA SUBMISSÃO DE PRESENÇA (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['data_aula'])) {
    
    $data_aula_post = filter_input(INPUT_POST, 'data_aula', FILTER_SANITIZE_STRING);
    $data_aula = $data_aula_post ?: date('Y-m-d');
    $presencas = $_POST['presenca'] ?? []; // Array de IDs de alunos presentes

    try {
        $pdo->beginTransaction();

        // 1.1. Remove registros antigos para esta data
        // Isso permite que você refaça ou corrija a chamada do dia.
        $sql_delete = "DELETE FROM presencas WHERE data_aula = :data_aula";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([':data_aula' => $data_aula]);

        // 1.2. Insere a presença para os alunos marcados
        $sql_insert = "INSERT INTO presencas (aluno_id, data_aula, status) VALUES (:aluno_id, :data_aula, 'presente')";
        $stmt_insert = $pdo->prepare($sql_insert);

        $alunos_registrados = 0;
        foreach ($presencas as $aluno_id => $status) {
            // Apenas registra se o checkbox foi marcado ('presente')
            if ($status === 'presente') {
                 $stmt_insert->execute([
                    ':aluno_id' => $aluno_id,
                    ':data_aula' => $data_aula
                ]);
                $alunos_registrados++;
            }
        }
        
        $pdo->commit();
        $message = '<p class="success">✅ Presença de **' . $alunos_registrados . '** alunos registrada com sucesso para **' . date('d/m/Y', strtotime($data_aula)) . '**!</p>';

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = '<p class="error">Erro ao registrar presença: ' . $e->getMessage() . '</p>';
        // Se este erro acontecer, provavelmente é devido a restrição de FOREIGN KEY (aluno excluído, mas o ID foi enviado)
    }
}

// 2. BUSCA GERAL DOS ALUNOS ATIVOS (Para montar a lista de chamada)
try {
    // Busca todos os alunos, ordenados pelo nome
    $sql_alunos = "SELECT id, nome, kyu FROM alunos ORDER BY nome ASC";
    $stmt_alunos = $pdo->query($sql_alunos);
    $alunos = $stmt_alunos->fetchAll();

    // 3. BUSCA O STATUS DE PRESENÇA JÁ REGISTRADO PARA A DATA SELECIONADA
    $presencas_hoje = [];
    if (!empty($alunos)) {
        $sql_status = "SELECT aluno_id, status FROM presencas WHERE data_aula = :data_aula";
        $stmt_status = $pdo->prepare($sql_status);
        $stmt_status->execute([':data_aula' => $data_aula]);
        
        // Coloca os resultados em um array com a chave sendo o aluno_id
        while ($row = $stmt_status->fetch()) {
            $presencas_hoje[$row['aluno_id']] = $row['status'];
        }
    }
    
} catch (Exception $e) {
    // Este erro só deve ocorrer se a tabela 'alunos' estiver inacessível
    $message = '<p class="error">Erro ao carregar alunos: ' . $e->getMessage() . '</p>';
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Presença - Judô</title>
    <link rel="stylesheet" href="styles/main.css">
    <style>
    /* Estilos específicos para a tabela de presença */
    .tabela-presenca th {
        background-color: #38761d !important;
    }

    /* Verde escuro */
    .tabela-presenca tr:hover td {
        background-color: #e6ffe6;
    }

    .tabela-presenca td:last-child {
        text-align: center;
    }

    .presenca-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">
                <h1>Registro de Presença</h1>
                <?php echo $message; ?>

                <div class="presenca-header">
                    <form method="GET" action="presenca.php" style="display: flex; align-items: center; gap: 10px;">
                        <label for="data_aula_select">Selecionar Data:</label>
                        <input type="date" id="data_aula_select" name="data"
                            value="<?php echo htmlspecialchars($data_aula); ?>" onchange="this.form.submit()"
                            style="padding: 5px; border-radius: 5px; border: 1px solid #ccc;">
                    </form>
                    <h2>Chamada para **<?php echo date('d/m/Y', strtotime($data_aula)); ?>**</h2>
                </div>

                <form method="POST" action="presenca.php">
                    <input type="hidden" name="data_aula" value="<?php echo htmlspecialchars($data_aula); ?>">

                    <?php if (count($alunos) > 0): ?>
                    <table class="tabela-alunos tabela-presenca">
                        <thead>
                            <tr>
                                <th>Nome do Aluno</th>
                                <th>Faixa</th>
                                <th>Marcar Presença</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                foreach ($alunos as $aluno): 
                                    $aluno_id = $aluno['id'];
                                    // Verifica se o aluno já estava marcado como presente na data
                                    $is_presente = isset($presencas_hoje[$aluno_id]) && $presencas_hoje[$aluno_id] == 'presente';
                                ?>
                            <tr>
                                <td><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                <td><?php echo htmlspecialchars($aluno['kyu']); ?></td>
                                <td>
                                    <input type="checkbox" name="presenca[<?php echo $aluno_id; ?>]" value="presente"
                                        <?php echo $is_presente ? 'checked' : ''; ?>>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn-submit"
                        style="background-color: var(--color-success); margin-top: 20px;">
                        Salvar Presenças do Dia
                    </button>
                    <?php else: ?>
                    <p class="error">Nenhum aluno cadastrado. Cadastre alunos para registrar a presença.</p>
                    <?php endif; ?>
                </form>

            </div>
        </div>
    </div>
</body>

</html>