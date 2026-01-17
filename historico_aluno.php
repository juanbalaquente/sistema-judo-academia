<?php
// =================================================================
// LÓGICA PHP: VISUALIZAÇÃO DO HISTÓRICO DE PRESENÇA DO ALUNO
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$aluno = null;
$message = '';
$historico_mensal = [];

// 1. VERIFICAÇÃO DO ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: alunos_list.php');
    exit;
}
$aluno_id = (int)$_GET['id'];

try {
    // 2. BUSCA DADOS BÁSICOS DO ALUNO
    $sql_aluno = "SELECT nome, kyu, data_nascimento FROM alunos WHERE id = :id";
    $stmt_aluno = $pdo->prepare($sql_aluno);
    $stmt_aluno->execute([':id' => $aluno_id]);
    $aluno = $stmt_aluno->fetch();

    if (!$aluno) {
        $message = '<p class="error">Aluno não encontrado.</p>';
        // Se o aluno não existe, paramos o processamento
    } else {
        // 3. CONSULTA O HISTÓRICO DE PRESENÇAS DO ALUNO
        // Agrupa as presenças por ano e mês para gerar o relatório
        $sql_historico = "
            SELECT 
                YEAR(data_aula) AS ano,
                MONTH(data_aula) AS mes,
                COUNT(id) AS total_presencas
            FROM presencas
            WHERE aluno_id = :aluno_id AND status = 'presente'
            GROUP BY ano, mes
            ORDER BY ano DESC, mes DESC
        ";
        $stmt_historico = $pdo->prepare($sql_historico);
        $stmt_historico->execute([':aluno_id' => $aluno_id]);
        $historico_mensal = $stmt_historico->fetchAll();
    }

} catch (Exception $e) {
    $message = '<p class="error">Erro ao carregar o histórico: ' . $e->getMessage() . '</p>';
}

// Função para traduzir o número do mês
function get_nome_mes($mes_num) {
    $nomes = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 
        7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
    ];
    return $nomes[$mes_num] ?? 'Mês Desconhecido';
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de <?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?></title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="icon" href="assets/favicon.png">
</head>

<body>
    <div class="main-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">

                <?php if ($aluno): ?>
                <h1>Histórico de Presença</h1>

                <div class="aluno-detalhes"
                    style="margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                    <h2>Aluno: **<?php echo htmlspecialchars($aluno['nome']); ?>**</h2>
                    <p>Faixa: **<?php echo htmlspecialchars($aluno['kyu']); ?>**</p>
                    <p>Nascimento: **<?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?>**</p>
                    <p><a href="alunos_list.php">&larr; Voltar para a Lista</a></p>
                </div>

                <?php echo $message; ?>

                <?php if (count($historico_mensal) > 0): ?>
                <table class="tabela-alunos" style="width: 50%; margin: 0 auto;">
                    <thead>
                        <tr>
                            <th>Mês/Ano</th>
                            <th>Total de Presenças</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico_mensal as $registro): ?>
                        <tr>
                            <td><?php echo get_nome_mes($registro['mes']) . ' de ' . $registro['ano']; ?></td>
                            <td style="text-align: center; font-weight: bold;">
                                <?php echo $registro['total_presencas']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="error">Nenhum registro de presença encontrado para este aluno.</p>
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