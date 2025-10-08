<?php
// =================================================================
// LÓGICA PHP: EXPORTAÇÃO DA LISTA DE ALUNOS PARA CSV
// =================================================================
require 'includes/auth_check.php'; 
require 'includes/db_connect.php'; 

try {
    // 1. Definições do cabeçalho
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=alunos_academia_' . date('Ymd_His') . '.csv');

    // Abre o output stream
    $output = fopen('php://output', 'w');
    
    // Configura o delimitador para PONTO E VÍRGULA (padrão brasileiro)
    $delimiter = ';'; 

    // 2. Define e escreve os títulos das colunas (Cabeçalho do CSV)
    $header = ['ID', 'Nome Completo', 'Data Nasc.', 'Idade', 'Peso (kg)', 'Faixa (Kyu)', 'Telefone', 'Email', 'Mensalidade Prev. (R$)'];
    fputcsv($output, $header, $delimiter); 

    // 3. Consulta para buscar TODOS os alunos
    $sql = "SELECT id, nome, data_nascimento, peso, kyu, telefone, email, valor_mensal FROM alunos ORDER BY nome ASC";
    $stmt = $pdo->query($sql);

    // 4. Itera sobre os resultados e escreve cada linha no CSV
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        
        // CÁLCULO DE IDADE SIMPLES
        $data_nascimento = new DateTime($row['data_nascimento']);
        $hoje = new DateTime('today');
        $idade = $data_nascimento->diff($hoje)->y;

        // FORMATAÇÃO DE VALORES PARA PADRÃO BRASILEIRO (vírgula como separador decimal)
        $peso_br = number_format((float)$row['peso'], 2, ',', ''); 
        $valor_mensal_br = number_format((float)$row['valor_mensal'], 2, ',', '');

        // Prepara a linha de dados para o CSV
        $data = [
            $row['id'],
            $row['nome'],
            date('d/m/Y', strtotime($row['data_nascimento'])), 
            $idade,
            $peso_br,
            $row['kyu'],
            $row['telefone'],
            $row['email'],
            $valor_mensal_br
        ];

        // Escreve a linha no CSV, usando o ponto e vírgula
        fputcsv($output, $data, $delimiter);
    }

    // 5. Fecha o stream de saída e encerra o script
    fclose($output);
    exit;

} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Erro na Exportação</h1>";
    echo "<p>Não foi possível gerar o arquivo CSV. Detalhes: " . $e->getMessage() . "</p>";
}
?>