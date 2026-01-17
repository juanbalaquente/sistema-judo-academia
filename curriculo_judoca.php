<?php
// =================================================================
// LÓGICA PHP: CURRÍCULO COMPLETO DO JUDOCA
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

$aluno = null;
$historico_presenca = [];
$historico_campeonatos = [];
$message = '';

// 1. VERIFICAÇÃO DO ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: alunos_list.php');
    exit;
}
$aluno_id = (int)$_GET['id'];

// Funções de formatação
function format_currency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}
function get_status_class($status) {
    switch ($status) {
        case 'pago': return 'status-pago';
        case 'presente': return 'status-success';
        case 'atrasado': return 'status-danger';
        case 'pendente': return 'status-warning';
        default: return '';
    }
}
function get_nome_mes($mes_num) {
    $nomes = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
    return $nomes[$mes_num] ?? 'Mês?';
}


try {
    // 2. BUSCA DADOS BÁSICOS DO ALUNO (INFORMAÇÕES DE CADASTRO E FINANCEIRO PADRÃO)
    $sql_aluno = "SELECT nome, kyu, data_nascimento, email, telefone, valor_mensal FROM alunos WHERE id = :id";
    $stmt_aluno = $pdo->prepare($sql_aluno);
    $stmt_aluno->execute([':id' => $aluno_id]);
    $aluno = $stmt_aluno->fetch();

    if (!$aluno) {
        $message = '<p class="error">Judoca não encontrado.</p>';
        
    } else {
        // 3. HISTÓRICO DE PRESENÇA (Últimos 6 meses)
        $sql_presenca = "
            SELECT 
                YEAR(data_aula) AS ano,
                MONTH(data_aula) AS mes,
                COUNT(id) AS presencas
            FROM presencas
            WHERE aluno_id = :aluno_id AND status = 'presente'
            AND data_aula >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY ano, mes
            ORDER BY ano DESC, mes DESC
        ";
        $stmt_presenca = $pdo->prepare($sql_presenca);
        $stmt_presenca->execute([':aluno_id' => $aluno_id]);
        $historico_presenca = $stmt_presenca->fetchAll();


        // 4. HISTÓRICO DE CAMPEONATOS (Com status de pagamento)
        $sql_campeonatos = "
            SELECT 
                c.nome AS campeonato_nome,
                c.data_evento,
                c.local,
                i.status_pagamento
            FROM inscricoes i
            JOIN campeonatos c ON i.campeonato_id = c.id
            WHERE i.aluno_id = :aluno_id
            ORDER BY c.data_evento DESC
        ";
        $stmt_campeonatos = $pdo->prepare($sql_campeonatos);
        $stmt_campeonatos->execute([':aluno_id' => $aluno_id]);
        $historico_campeonatos = $stmt_campeonatos->fetchAll();
    }

} catch (Exception $e) {
    $message = '<p class="error">Erro ao carregar o currículo: ' . $e->getMessage() . '</p>';
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Currículo do Judoca: <?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?></title>
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

    .profile-details strong {
        font-weight: 700;
        color: var(--color-dark);
    }

    .profile-section {
        margin-top: 30px;
        padding-top: 15px;
    }

    .finance-link {
        margin-top: 15px;
        text-align: center;
    }

    .tabela-presenca-resumo {
        width: 100%;
        max-width: 500px;
        margin: 10px 0;
    }

    .tabela-presenca-resumo th {
        background-color: #5a5a5a !important;
        color: white;
        padding: 10px;
    }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">

                <?php if ($aluno): ?>
                <h1>Currículo do Judoca: **<?php echo htmlspecialchars($aluno['nome']); ?>**</h1>
                <p><a href="alunos_list.php">&larr; Voltar para a Lista de Alunos</a></p>

                <?php echo $message; ?>

                <div class="profile-card">
                    <h2>Dados de Cadastro</h2>
                    <div class="profile-details">
                        <p><strong>Nome Completo:</strong> <?php echo htmlspecialchars($aluno['nome']); ?></p>
                        <p><strong>Faixa Atual (Kyu):</strong> <span
                                style="color: var(--color-primary); font-weight: bold;"><?php echo htmlspecialchars($aluno['kyu']); ?></span>
                        </p>
                        <p><strong>Nascimento:</strong>
                            <?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?></p>
                        <p><strong>Telefone:</strong> <?php echo htmlspecialchars($aluno['telefone']); ?></p>
                        <p><strong>E-mail:</strong> <?php echo htmlspecialchars($aluno['email']); ?></p>
                        <p><strong>Mensalidade Padrão:</strong> <?php echo format_currency($aluno['valor_mensal']); ?>
                        </p>

                        <div class="finance-link">
                            <a href="historico_financeiro.php?id=<?php echo $aluno_id; ?>" class="btn-acao editar"
                                style="background-color: var(--color-primary); color: white;">
                                Ver Histórico Financeiro Completo
                            </a>
                        </div>
                    </div>
                </div>

                <div class="profile-section">
                    <h2>Histórico de Campeonatos</h2>
                    <?php if (count($historico_campeonatos) > 0): ?>
                    <table class="tabela-alunos">
                        <thead>
                            <tr>
                                <th>Campeonato</th>
                                <th>Data</th>
                                <th>Local</th>
                                <th>Pagamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historico_campeonatos as $evento): ?>
                            <tr class="<?php echo get_status_class($evento['status_pagamento']); ?>">
                                <td><?php echo htmlspecialchars($evento['campeonato_nome']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($evento['data_evento'])); ?></td>
                                <td><?php echo htmlspecialchars($evento['local']); ?></td>
                                <td><?php echo ucfirst($evento['status_pagamento']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="error">Judoca sem inscrições em campeonatos.</p>
                    <?php endif; ?>
                </div>


                <div class="profile-section">
                    <h2>Resumo de Presença (Últimos 6 Meses)</h2>
                    <?php if (count($historico_presenca) > 0): ?>
                    <table class="tabela-alunos tabela-presenca-resumo">
                        <thead>
                            <tr>
                                <?php foreach ($historico_presenca as $resumo): ?>
                                <th><?php echo get_nome_mes($resumo['mes']) . '/' . substr($resumo['ano'], 2); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php foreach ($historico_presenca as $resumo): ?>
                                <td style="text-align: center; font-weight: bold;"><?php echo $resumo['presencas']; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="error">Nenhum registro de presença encontrado nos últimos 6 meses.</p>
                    <?php endif; ?>
                </div>


                <?php else: ?>
                <?php echo $message; ?>
                <p><a href="alunos_list.php">&larr; Voltar para a Lista</a></p>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>

</html>