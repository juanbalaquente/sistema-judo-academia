<?php
// =================================================================
// LOGICA PHP: IMPORTACAO EM MASSA DE ALUNOS VIA CSV
// =================================================================
require 'includes/auth_check.php';
require 'includes/db_connect.php';

$message = '';
$errors = [];
$result = [
    'total' => 0,
    'inserted' => 0,
    'skipped' => 0,
];

function normalize_header($value) {
    $value = trim((string)$value);
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    if (function_exists('mb_convert_encoding')) {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, ISO-8859-15, Windows-1252');
    }
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]/', '', $value);
    return $value;
}

function parse_date($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $formats = ['d/m/Y', 'Y-m-d'];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt && $dt->format($format) === $value) {
            return $dt->format('Y-m-d');
        }
    }
    return false;
}

function parse_decimal($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    $has_comma = strpos($value, ',') !== false;
    $has_dot = strpos($value, '.') !== false;
    if ($has_comma && $has_dot) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
    } elseif ($has_comma) {
        $value = str_replace(',', '.', $value);
    }
    if (!is_numeric($value)) {
        return false;
    }
    return (float)$value;
}

function parse_bool($value) {
    $value = strtolower(trim((string)$value));
    if ($value === '') {
        return 0;
    }
    $truthy = ['1', 'sim', 's', 'true', 'yes', 'y'];
    $falsy = ['0', 'nao', 'n', 'false', 'no'];
    if (in_array($value, $truthy, true)) {
        return 1;
    }
    if (in_array($value, $falsy, true)) {
        return 0;
    }
    return false;
}

if (isset($_GET['download_template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=alunos_import_template.csv');
    $output = fopen('php://output', 'w');
    $delimiter = ';';
    fwrite($output, "\xEF\xBB\xBF");
    $header = [
        'Nome Completo',
        'Data Nasc.',
        'Peso (kg)',
        'Faixa (Kyu)',
        'Telefone',
        'Email',
        'Mensalidade Prev. (R$)',
        'Tipo Sanguineo',
        'Nome do Pai',
        'Telefone do Pai',
        'Nome da Mae',
        'Telefone da Mae',
        'Foto Path',
        'Termo Aceito',
        'Termo Data',
        'Termo Nome'
    ];
    fputcsv($output, $header, $delimiter);
    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if (!isset($_FILES['csv_file']['error']) || is_array($_FILES['csv_file']['error'])) {
        $message = '<p class="error">Arquivo invalido.</p>';
    } elseif ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $message = '<p class="error">Falha no upload do arquivo.</p>';
    } else {
        $tmp_path = $_FILES['csv_file']['tmp_name'];
        $delimiter = ';';
        $handle = fopen($tmp_path, 'r');
        if ($handle === false) {
            $message = '<p class="error">Nao foi possivel abrir o arquivo CSV.</p>';
        } else {
            $header_row = fgetcsv($handle, 0, $delimiter);
            if ($header_row === false) {
                $message = '<p class="error">CSV vazio ou sem cabecalho.</p>';
            } else {
                $map = [
                    'id' => null,
                    'idade' => null,
                    'nome' => 'nome',
                    'nomecompleto' => 'nome',
                    'aluno' => 'nome',
                    'datanasc' => 'data_nascimento',
                    'datadenascimento' => 'data_nascimento',
                    'datanascimento' => 'data_nascimento',
                    'peso' => 'peso',
                    'pesokg' => 'peso',
                    'faixa' => 'kyu',
                    'faixakyu' => 'kyu',
                    'kyu' => 'kyu',
                    'telefone' => 'telefone',
                    'email' => 'email',
                    'mensalidade' => 'valor_mensal',
                    'mensalidadeprevrs' => 'valor_mensal',
                    'valormensal' => 'valor_mensal',
                    'tiposanguineo' => 'tipo_sanguineo',
                    'nomedopai' => 'nome_pai',
                    'telefonedopai' => 'telefone_pai',
                    'nomedamae' => 'nome_mae',
                    'telefonedamae' => 'telefone_mae',
                    'fotopath' => 'foto_path',
                    'termoaceito' => 'termo_aceito',
                    'termodata' => 'termo_data',
                    'termonome' => 'termo_nome'
                ];

                $col_index = [];
                foreach ($header_row as $idx => $label) {
                    $key = normalize_header($label);
                    if (array_key_exists($key, $map)) {
                        $field = $map[$key];
                        if ($field && !isset($col_index[$field])) {
                            $col_index[$field] = $idx;
                        }
                    }
                }

                if (!isset($col_index['nome'])) {
                    $message = '<p class="error">Cabecalho invalido: coluna "Nome" nao encontrada.</p>';
                } else {
                    $sql = "INSERT INTO alunos (
                                nome, data_nascimento, peso, kyu, telefone, email, valor_mensal,
                                tipo_sanguineo, nome_pai, nome_mae, telefone_pai, telefone_mae,
                                foto_path, termo_aceito, termo_data, termo_nome
                            ) VALUES (
                                :nome, :data_nascimento, :peso, :kyu, :telefone, :email, :valor_mensal,
                                :tipo_sanguineo, :nome_pai, :nome_mae, :telefone_pai, :telefone_mae,
                                :foto_path, :termo_aceito, :termo_data, :termo_nome
                            )";
                    $stmt_insert = $pdo->prepare($sql);
                    $stmt_check_email = $pdo->prepare("SELECT id FROM alunos WHERE email = :email LIMIT 1");

                    $line = 1;
                    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                        $line++;
                        if (count($row) === 1 && trim((string)$row[0]) === '') {
                            continue;
                        }
                        $result['total']++;

                        $nome = isset($col_index['nome']) ? trim((string)$row[$col_index['nome']]) : '';
                        if ($nome === '') {
                            $errors[] = "Linha {$line}: nome vazio.";
                            continue;
                        }

                        $email = isset($col_index['email']) ? trim((string)$row[$col_index['email']]) : '';
                        if ($email !== '') {
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $errors[] = "Linha {$line}: email invalido.";
                                continue;
                            }
                            $stmt_check_email->execute([':email' => $email]);
                            if ($stmt_check_email->fetchColumn()) {
                                $result['skipped']++;
                                $errors[] = "Linha {$line}: email ja existe (ignorando).";
                                continue;
                            }
                        } else {
                            $email = null;
                        }

                        $data_nascimento = isset($col_index['data_nascimento']) ? parse_date($row[$col_index['data_nascimento']]) : null;
                        if ($data_nascimento === false) {
                            $errors[] = "Linha {$line}: data de nascimento invalida.";
                            continue;
                        }

                        $peso = isset($col_index['peso']) ? parse_decimal($row[$col_index['peso']]) : null;
                        if ($peso === false) {
                            $errors[] = "Linha {$line}: peso invalido.";
                            continue;
                        }

                        $valor_mensal = isset($col_index['valor_mensal']) ? parse_decimal($row[$col_index['valor_mensal']]) : null;
                        if ($valor_mensal === false) {
                            $errors[] = "Linha {$line}: valor mensal invalido.";
                            continue;
                        }

                        $termo_aceito = isset($col_index['termo_aceito']) ? parse_bool($row[$col_index['termo_aceito']]) : 0;
                        if ($termo_aceito === false) {
                            $errors[] = "Linha {$line}: termo aceito invalido (use 1/0 ou sim/nao).";
                            continue;
                        }

                        $termo_data = isset($col_index['termo_data']) ? parse_date($row[$col_index['termo_data']]) : null;
                        if ($termo_data === false) {
                            $errors[] = "Linha {$line}: termo data invalida.";
                            continue;
                        }

                        $data = [
                            ':nome' => $nome,
                            ':data_nascimento' => $data_nascimento,
                            ':peso' => $peso,
                            ':kyu' => isset($col_index['kyu']) ? trim((string)$row[$col_index['kyu']]) : null,
                            ':telefone' => isset($col_index['telefone']) ? trim((string)$row[$col_index['telefone']]) : null,
                            ':email' => $email,
                            ':valor_mensal' => $valor_mensal,
                            ':tipo_sanguineo' => isset($col_index['tipo_sanguineo']) ? trim((string)$row[$col_index['tipo_sanguineo']]) : null,
                            ':nome_pai' => isset($col_index['nome_pai']) ? trim((string)$row[$col_index['nome_pai']]) : null,
                            ':nome_mae' => isset($col_index['nome_mae']) ? trim((string)$row[$col_index['nome_mae']]) : null,
                            ':telefone_pai' => isset($col_index['telefone_pai']) ? trim((string)$row[$col_index['telefone_pai']]) : null,
                            ':telefone_mae' => isset($col_index['telefone_mae']) ? trim((string)$row[$col_index['telefone_mae']]) : null,
                            ':foto_path' => isset($col_index['foto_path']) ? trim((string)$row[$col_index['foto_path']]) : null,
                            ':termo_aceito' => $termo_aceito,
                            ':termo_data' => $termo_data,
                            ':termo_nome' => isset($col_index['termo_nome']) ? trim((string)$row[$col_index['termo_nome']]) : null
                        ];

                        try {
                            $stmt_insert->execute($data);
                            $result['inserted']++;
                        } catch (Exception $e) {
                            $errors[] = "Linha {$line}: erro ao inserir ({$e->getMessage()}).";
                        }
                    }

                    if ($result['inserted'] > 0) {
                        $message = '<p class="success">Importacao concluida. Inseridos: ' . $result['inserted'] .
                                   ', Ignorados: ' . $result['skipped'] . ', Total lido: ' . $result['total'] . '.</p>';
                    } else {
                        $message = '<p class="error">Nenhum registro foi inserido.</p>';
                    }
                }
            }
            fclose($handle);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Alunos - Judo</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="icon" href="assets/favicon.png">
</head>
<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">
                <h1>Importacao em Massa de Alunos</h1>
                <?php echo $message; ?>

                <div class="section-separator" style="border-top: none; margin-top: 0; padding-top: 0;">
                    <p>Use CSV com separador <strong>;</strong>. A primeira linha deve conter o cabecalho.</p>
                    <p>
                        <a class="btn-submit" href="import_alunos.php?download_template=1"
                           style="width:auto; padding:10px 20px; background-color: var(--color-primary);">
                            Baixar modelo CSV
                        </a>
                    </p>
                </div>

                <form method="POST" action="import_alunos.php" enctype="multipart/form-data" class="form-filtro">
                    <div>
                        <label for="csv_file">Arquivo CSV</label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required>
                    </div>
                    <div style="display:flex; align-items:flex-end;">
                        <button type="submit" class="btn-submit">Importar</button>
                    </div>
                </form>

                <?php if (!empty($errors)): ?>
                    <div class="section-separator">
                        <h2>Erros/Alertas</h2>
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="section-separator">
                    <h2>Campos aceitos no CSV</h2>
                    <p>
                        Nome Completo, Data Nasc., Peso (kg), Faixa (Kyu), Telefone, Email,
                        Mensalidade Prev. (R$), Tipo Sanguineo, Nome do Pai, Telefone do Pai,
                        Nome da Mae, Telefone da Mae, Foto Path, Termo Aceito, Termo Data, Termo Nome.
                    </p>
                    <p>Datas aceitam <strong>dd/mm/aaaa</strong> ou <strong>yyyy-mm-dd</strong>.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
