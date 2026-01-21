<?php
// =================================================================
// LOGICA PHP: EXPORTACAO DA LISTA DE ALUNOS PARA CSV (EXCEL FRIENDLY)
// =================================================================
require 'includes/auth_check.php';
require 'includes/db_connect.php';

try {
    // 1. Cabecalhos para Excel (CSV com BOM e separador ;)
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename=alunos_academia_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    $delimiter = ';';

    // BOM UTF-8 para o Excel abrir acentos corretamente
    fwrite($output, "ï»¿");

    // 2. Cabecalho das colunas
    $header = [
        'ID', 'Nome Completo', 'Data Nasc.', 'Idade', 'Peso (kg)', 'Faixa (Kyu)',
        'Telefone', 'Email', 'Mensalidade Prev. (R$)', 'Numero do Zempo', 'Numero da FMJ', 'ID da Academia',
        'Tipo Sanguineo', 'Nome do Pai', 'Telefone do Pai', 'Nome da Mae', 'Telefone da Mae'
    ];
    fputcsv($output, $header, $delimiter);

    // 3. Consulta com novos campos
    $sql = "SELECT id, nome, data_nascimento, peso, kyu, telefone, email, valor_mensal,
                   numero_zempo, numero_fmj, academia_id,
                   tipo_sanguineo, nome_pai, telefone_pai, nome_mae, telefone_mae
            FROM alunos ORDER BY nome ASC";
    $stmt = $pdo->query($sql);

    // Evita formula injection em Excel
    $safe_csv = function ($value) {
        $value = (string)$value;
        if ($value !== '' && preg_match('/^[=+@-]/', $value)) {
            return "'" . $value;
        }
        return $value;
    };

    // 4. Escreve as linhas
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data_nascimento = new DateTime($row['data_nascimento']);
        $hoje = new DateTime('today');
        $idade = $data_nascimento->diff($hoje)->y;

        $peso_br = number_format((float)$row['peso'], 2, ',', '');
        $valor_mensal_br = number_format((float)$row['valor_mensal'], 2, ',', '');

        $data = [
            $safe_csv($row['id']),
            $safe_csv($row['nome']),
            date('d/m/Y', strtotime($row['data_nascimento'])),
            $safe_csv($idade),
            $safe_csv($peso_br),
            $safe_csv($row['kyu']),
            $safe_csv($row['telefone']),
            $safe_csv($row['email']),
            $safe_csv($valor_mensal_br),
            $safe_csv($row['numero_zempo']),
            $safe_csv($row['numero_fmj']),
            $safe_csv($row['academia_id']),
            $safe_csv($row['tipo_sanguineo']),
            $safe_csv($row['nome_pai']),
            $safe_csv($row['telefone_pai']),
            $safe_csv($row['nome_mae']),
            $safe_csv($row['telefone_mae'])
        ];

        fputcsv($output, $data, $delimiter);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<h1>Erro na Exportacao</h1>";
    echo "<p>Nao foi possivel gerar o arquivo CSV. Detalhes: " . $e->getMessage() . "</p>";
}
?>
