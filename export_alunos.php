<?php
// =================================================================
// LÓGICA PHP: EXPORTAÇÃO DA LISTA DE ALUNOS PARA CSV
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

try {
    // 1. Definições do cabeçalho para forçar o download do arquivo CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=alunos_academia_' . date('Ymd_His') . '.csv');

    // 2. Cria um stream de saída (ponteiro de arquivo temporário)
    $output = fopen('php://output', 'w');

    // 3. Define e escreve os títulos das colunas (Cabeçalho do CSV)
    // Os títulos devem ser claros e fáceis de entender
    $header = ['ID', 'Nome Completo', 'Data Nasc.', 'Idade (Estimada)', 'Peso (kg)', 'Faixa (Kyu)', 'Telefone', 'Email', 'Mensalidade Prev. (R$)'];
    fputcsv($output, $header, ';'); // Usando ponto-e-vírgula como delimitador (padrão brasileiro)

    // 4. Consulta para buscar TODOS os alunos
    $sql = "SELECT id, nome, data_nascimento, peso, kyu, telefone, email, valor_mensal FROM alunos ORDER BY nome ASC";
    $stmt = $pdo->query($sql);

    // 5. Itera sobre os resultados e escreve cada linha no CSV
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        
        // CÁLCULO DE IDADE SIMPLES (para adicionar mais utilidade ao relatório)
        $data_nascimento = new DateTime($row['data_nascimento']);
        $hoje = new DateTime('today');
        $idade = $data_nascimento->diff($hoje)->y;

        // Prepara a linha de dados para o CSV
        $data = [
            $row['id'],
            $row['nome'],
            date('d/m/Y', strtotime($row['data_nascimento'])), // Formata a data
            $idade,
            str_replace('.', ',', $row['peso']), // Substitui ponto por vírgula para o Excel (padrão BR)
            $row['kyu'],
            $row['telefone'],
            $row['email'],
            str_replace('.', ',', $row['valor_mensal']) // Formata valor
        ];

        // Escreve a linha no CSV
        fputcsv($output, $data, ';');
    }

    // 6. Fecha o stream de saída e encerra o script
    fclose($output);
    exit;

} catch (Exception $e) {
    // Se houver erro, exibe uma mensagem simples de erro
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Erro na Exportação</h1>";
    echo "<p>Não foi possível gerar o arquivo CSV. Verifique a conexão com o banco de dados.</p>";
    echo "<p>Detalhes: " . $e->getMessage() . "</p>";
}
?>