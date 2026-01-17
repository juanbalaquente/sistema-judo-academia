<?php
// =================================================================
// LOGICA PHP: PAGINA DE CONSULTA DE INFORMACOES DO ALUNO (SOMENTE LEITURA)
// =================================================================
require 'includes/auth_check.php';
require 'includes/db_connect.php';

$message = '';
$alunos = [];
$aluno = null;
$campeonatos_aluno = [];
$documentos = null;
$documentos_arquivos = [];
$graduacoes = [];
$selected_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$foto_dir = 'uploads/alunos';
$docs_dir = 'uploads/documentos';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_foto') {
    $selected_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($selected_id && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['foto']['tmp_name'];
        $file_name = $_FILES['foto']['name'];
        $file_size = (int)$_FILES['foto']['size'];

        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            $message = '<p class="error">Formato invalido. Use JPG, PNG ou WEBP.</p>';
        } elseif ($file_size > 2 * 1024 * 1024) {
            $message = '<p class="error">Arquivo muito grande. Limite de 2MB.</p>';
        } else {
            if (!is_dir($foto_dir)) {
                mkdir($foto_dir, 0775, true);
            }
            $safe_name = 'aluno_' . $selected_id . '_' . time() . '.' . $ext;
            $dest = $foto_dir . '/' . $safe_name;
            if (move_uploaded_file($tmp_name, $dest)) {
                try {
                    $sql_foto = "UPDATE alunos SET foto_path = :foto_path WHERE id = :id";
                    $stmt_foto = $pdo->prepare($sql_foto);
                    $stmt_foto->execute([
                        ':foto_path' => $dest,
                        ':id' => $selected_id
                    ]);
                    $message = '<p class="success">Foto atualizada com sucesso.</p>';
                } catch (Exception $e) {
                    $message = '<p class="error">Erro ao salvar foto: ' . $e->getMessage() . '</p>';
                }
            } else {
                $message = '<p class="error">Erro ao enviar a foto.</p>';
            }
        }
    } else {
        $message = '<p class="error">Selecione uma foto valida.</p>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_foto') {
    $selected_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($selected_id) {
        try {
            $stmt_foto = $pdo->prepare("SELECT foto_path FROM alunos WHERE id = :id");
            $stmt_foto->execute([':id' => $selected_id]);
            $foto = $stmt_foto->fetchColumn();

            if (!empty($foto)) {
                $real_base = realpath($foto_dir);
                $real_foto = realpath($foto);
                if ($real_base && $real_foto && strpos($real_foto, $real_base) === 0 && file_exists($real_foto)) {
                    unlink($real_foto);
                }
            }

            $stmt_clear = $pdo->prepare("UPDATE alunos SET foto_path = NULL WHERE id = :id");
            $stmt_clear->execute([':id' => $selected_id]);
            $message = '<p class="success">Foto removida com sucesso.</p>';
        } catch (Exception $e) {
            $message = '<p class="error">Erro ao remover foto: ' . $e->getMessage() . '</p>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_checklist') {
    $selected_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($selected_id) {
        $rg_ok = isset($_POST['rg_ok']) ? 1 : 0;
        $atestado_ok = isset($_POST['atestado_ok']) ? 1 : 0;
        $autorizacao_ok = isset($_POST['autorizacao_ok']) ? 1 : 0;
        $obs_docs = filter_input(INPUT_POST, 'obs_docs', FILTER_SANITIZE_STRING);

        try {
            $sql = "INSERT INTO documentos_aluno (aluno_id, rg_ok, atestado_ok, autorizacao_ok, observacoes)
                    VALUES (:id, :rg_ok, :atestado_ok, :autorizacao_ok, :obs)
                    ON DUPLICATE KEY UPDATE
                        rg_ok = VALUES(rg_ok),
                        atestado_ok = VALUES(atestado_ok),
                        autorizacao_ok = VALUES(autorizacao_ok),
                        observacoes = VALUES(observacoes)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $selected_id,
                ':rg_ok' => $rg_ok,
                ':atestado_ok' => $atestado_ok,
                ':autorizacao_ok' => $autorizacao_ok,
                ':obs' => $obs_docs
            ]);
            $message = '<p class="success">Checklist atualizado.</p>';
        } catch (Exception $e) {
            $message = '<p class="error">Erro ao salvar checklist: ' . $e->getMessage() . '</p>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_documento') {
    $selected_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $doc_tipo = filter_input(INPUT_POST, 'doc_tipo', FILTER_SANITIZE_STRING) ?: 'Outro';
    if ($selected_id && isset($_FILES['doc_arquivo']) && $_FILES['doc_arquivo']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['doc_arquivo']['tmp_name'];
        $file_name = $_FILES['doc_arquivo']['name'];
        $file_size = (int)$_FILES['doc_arquivo']['size'];

        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            $message = '<p class="error">Formato invalido. Use JPG, PNG, WEBP ou PDF.</p>';
        } elseif ($file_size > 4 * 1024 * 1024) {
            $message = '<p class="error">Arquivo muito grande. Limite de 4MB.</p>';
        } else {
            if (!is_dir($docs_dir)) {
                mkdir($docs_dir, 0775, true);
            }
            $safe_name = 'doc_' . $selected_id . '_' . time() . '.' . $ext;
            $dest = $docs_dir . '/' . $safe_name;
            if (move_uploaded_file($tmp_name, $dest)) {
                try {
                    $sql = "INSERT INTO documentos_arquivos (aluno_id, tipo, arquivo_path)
                            VALUES (:id, :tipo, :path)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':id' => $selected_id,
                        ':tipo' => $doc_tipo,
                        ':path' => $dest
                    ]);
                    $message = '<p class="success">Documento enviado.</p>';
                } catch (Exception $e) {
                    $message = '<p class="error">Erro ao salvar documento: ' . $e->getMessage() . '</p>';
                }
            } else {
                $message = '<p class="error">Erro ao enviar documento.</p>';
            }
        }
    } else {
        $message = '<p class="error">Selecione um documento valido.</p>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_documento') {
    $doc_id = filter_input(INPUT_POST, 'doc_id', FILTER_VALIDATE_INT);
    $selected_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($doc_id && $selected_id) {
        try {
            $stmt = $pdo->prepare("SELECT arquivo_path FROM documentos_arquivos WHERE id = :id AND aluno_id = :aluno_id");
            $stmt->execute([':id' => $doc_id, ':aluno_id' => $selected_id]);
            $path = $stmt->fetchColumn();
            if (!empty($path)) {
                $real_base = realpath($docs_dir);
                $real_doc = realpath($path);
                if ($real_base && $real_doc && strpos($real_doc, $real_base) === 0 && file_exists($real_doc)) {
                    unlink($real_doc);
                }
            }
            $stmt_del = $pdo->prepare("DELETE FROM documentos_arquivos WHERE id = :id AND aluno_id = :aluno_id");
            $stmt_del->execute([':id' => $doc_id, ':aluno_id' => $selected_id]);
            $message = '<p class="success">Documento removido.</p>';
        } catch (Exception $e) {
            $message = '<p class="error">Erro ao remover documento: ' . $e->getMessage() . '</p>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_graduacao') {
    $selected_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $faixa = filter_input(INPUT_POST, 'faixa', FILTER_SANITIZE_STRING);
    $data_exame = filter_input(INPUT_POST, 'data_exame', FILTER_SANITIZE_STRING);
    $requisitos = filter_input(INPUT_POST, 'requisitos', FILTER_SANITIZE_STRING);
    $obs_grad = filter_input(INPUT_POST, 'obs_grad', FILTER_SANITIZE_STRING);
    if ($selected_id && $faixa && $data_exame) {
        try {
            $sql = "INSERT INTO graduacoes (aluno_id, faixa, data_exame, requisitos, observacoes)
                    VALUES (:id, :faixa, :data_exame, :requisitos, :observacoes)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $selected_id,
                ':faixa' => $faixa,
                ':data_exame' => $data_exame,
                ':requisitos' => $requisitos,
                ':observacoes' => $obs_grad
            ]);
            $message = '<p class="success">Graduacao registrada.</p>';
        } catch (Exception $e) {
            $message = '<p class="error">Erro ao salvar graduacao: ' . $e->getMessage() . '</p>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_termo') {
    $selected_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $termo_nome = filter_input(INPUT_POST, 'termo_nome', FILTER_SANITIZE_STRING);
    $termo_ok = isset($_POST['termo_aceito']) ? 1 : 0;
    if ($selected_id && $termo_ok) {
        try {
            $stmt = $pdo->prepare("UPDATE alunos SET termo_aceito = 1, termo_data = CURDATE(), termo_nome = :nome WHERE id = :id");
            $stmt->execute([':nome' => $termo_nome, ':id' => $selected_id]);
            $message = '<p class="success">Termo registrado.</p>';
        } catch (Exception $e) {
            $message = '<p class="error">Erro ao salvar termo: ' . $e->getMessage() . '</p>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_termo') {
    $selected_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($selected_id) {
        try {
            $stmt = $pdo->prepare("UPDATE alunos SET termo_aceito = 0, termo_data = NULL, termo_nome = NULL WHERE id = :id");
            $stmt->execute([':id' => $selected_id]);
            $message = '<p class="success">Termo removido.</p>';
        } catch (Exception $e) {
            $message = '<p class="error">Erro ao remover termo: ' . $e->getMessage() . '</p>';
        }
    }
}

try {
    $sql_lista = "SELECT id, nome, kyu, telefone, email FROM alunos ORDER BY nome ASC";
    $stmt_lista = $pdo->query($sql_lista);
    $alunos = $stmt_lista->fetchAll();

    if ($selected_id) {
        $sql_aluno = "SELECT id, nome, kyu, data_nascimento, peso, telefone, email, valor_mensal,
                             foto_path, termo_aceito, termo_data, termo_nome,
                             tipo_sanguineo, nome_pai, nome_mae, telefone_pai, telefone_mae
                      FROM alunos WHERE id = :id";
        $stmt_aluno = $pdo->prepare($sql_aluno);
        $stmt_aluno->execute([':id' => $selected_id]);
        $aluno = $stmt_aluno->fetch();

        if (!$aluno) {
            $message = '<p class="error">Aluno nao encontrado.</p>';
        } else {
            $sql_camps = "
                SELECT 
                    c.nome,
                    c.data_evento,
                    c.local,
                    i.status_pagamento,
                    i.colocacao
                FROM inscricoes i
                JOIN campeonatos c ON c.id = i.campeonato_id
                WHERE i.aluno_id = :id
                ORDER BY c.data_evento DESC
            ";
            $stmt_camps = $pdo->prepare($sql_camps);
            $stmt_camps->execute([':id' => $selected_id]);
            $campeonatos_aluno = $stmt_camps->fetchAll();

            $stmt_docs = $pdo->prepare("SELECT aluno_id, rg_ok, atestado_ok, autorizacao_ok, observacoes FROM documentos_aluno WHERE aluno_id = :id");
            $stmt_docs->execute([':id' => $selected_id]);
            $documentos = $stmt_docs->fetch();

            $stmt_files = $pdo->prepare("SELECT id, tipo, arquivo_path, uploaded_at FROM documentos_arquivos WHERE aluno_id = :id ORDER BY uploaded_at DESC");
            $stmt_files->execute([':id' => $selected_id]);
            $documentos_arquivos = $stmt_files->fetchAll();

            $stmt_grad = $pdo->prepare("SELECT faixa, data_exame, requisitos, observacoes FROM graduacoes WHERE aluno_id = :id ORDER BY data_exame DESC");
            $stmt_grad->execute([':id' => $selected_id]);
            $graduacoes = $stmt_grad->fetchAll();
        }
    }
} catch (Exception $e) {
    $message = '<p class="error">Erro ao carregar informacoes: ' . $e->getMessage() . '</p>';
}

function format_currency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informacoes do Aluno - JudA'</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="icon" href="assets/favicon.png">
    <style>
    .info-card {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #eee;
        margin-top: 20px;
    }

    .info-card h2 {
        margin-top: 0;
        border-bottom: 1px dashed #ccc;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .info-card p {
        margin: 6px 0;
        font-size: 1.05em;
    }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <?php include './includes/sidebar.php'; ?>
        <div class="content-area">
            <div class="container">

                <h1>Informacoes do Aluno</h1>
                <?php echo $message; ?>

                <?php if (count($alunos) > 0): ?>
                <table class="tabela-alunos">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Faixa</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alunos as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['nome']); ?></td>
                            <td><?php echo htmlspecialchars($item['kyu']); ?></td>
                            <td><?php echo htmlspecialchars($item['telefone']); ?></td>
                            <td><?php echo htmlspecialchars($item['email']); ?></td>
                            <td>
                                <a class="btn-acao editar" href="aluno_info.php?id=<?php echo $item['id']; ?>">
                                    Ver informacoes
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="error">Nenhum aluno cadastrado.</p>
                <?php endif; ?>

                <?php if ($aluno): ?>
                <div class="info-card">
                    <h2><?php echo htmlspecialchars($aluno['nome']); ?></h2>
                    <?php if (!empty($aluno['foto_path'])): ?>
                    <p><img src="<?php echo htmlspecialchars($aluno['foto_path']); ?>" alt="Foto do aluno"
                            style="width: 140px; height: 140px; object-fit: cover; border-radius: 10px; border: 2px solid #eee;">
                    </p>
                    <?php endif; ?>
                    <p><strong>Faixa (Kyu):</strong> <?php echo htmlspecialchars($aluno['kyu']); ?></p>
                    <p><strong>Nascimento:</strong> <?php echo date('d/m/Y', strtotime($aluno['data_nascimento'])); ?>
                    </p>
                    <p><strong>Peso (kg):</strong> <?php echo htmlspecialchars($aluno['peso']); ?></p>
                    <p><strong>Telefone:</strong> <?php echo htmlspecialchars($aluno['telefone']); ?></p>
                    <p><strong>E-mail:</strong> <?php echo htmlspecialchars($aluno['email']); ?></p>
                    <p><strong>Tipo sanguineo:</strong> <?php echo htmlspecialchars($aluno['tipo_sanguineo'] ?? ''); ?></p>
                    <p><strong>Nome do pai:</strong> <?php echo htmlspecialchars($aluno['nome_pai'] ?? ''); ?></p>
                    <p><strong>Telefone do pai:</strong> <?php echo htmlspecialchars($aluno['telefone_pai'] ?? ''); ?></p>
                    <p><strong>Nome da mae:</strong> <?php echo htmlspecialchars($aluno['nome_mae'] ?? ''); ?></p>
                    <p><strong>Telefone da mae:</strong> <?php echo htmlspecialchars($aluno['telefone_mae'] ?? ''); ?></p>
                    <p><strong>Mensalidade:</strong> <?php echo format_currency($aluno['valor_mensal']); ?></p>
                </div>

                <div class="info-card">
                    <h2>Foto do Aluno</h2>
                    <form method="POST" action="aluno_info.php?id=<?php echo $aluno['id']; ?>" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_foto">
                        <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="foto">Selecione a foto</label>
                                <input type="file" id="foto" name="foto" accept=".jpg,.jpeg,.png,.webp" required>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Salvar Foto</button>
                    </form>
                    <form method="POST" action="aluno_info.php?id=<?php echo $aluno['id']; ?>" style="margin-top: 10px;">
                        <input type="hidden" name="action" value="remove_foto">
                        <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
                        <button type="submit" class="btn-acao excluir"
                            onclick="return confirm('Deseja remover a foto deste aluno?');">
                            Remover Foto
                        </button>
                    </form>
                </div>

                <div class="info-card">
                    <h2>Checklist de Documentos</h2>
                    <form method="POST" action="aluno_info.php?id=<?php echo $aluno['id']; ?>">
                        <input type="hidden" name="action" value="save_checklist">
                        <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label><input type="checkbox" name="rg_ok" <?php echo (!empty($documentos) && !empty($documentos['rg_ok'])) ? 'checked' : ''; ?>> RG</label>
                            </div>
                            <div class="form-group">
                                <label><input type="checkbox" name="atestado_ok" <?php echo (!empty($documentos) && !empty($documentos['atestado_ok'])) ? 'checked' : ''; ?>> Atestado Medico</label>
                            </div>
                            <div class="form-group">
                                <label><input type="checkbox" name="autorizacao_ok" <?php echo (!empty($documentos) && !empty($documentos['autorizacao_ok'])) ? 'checked' : ''; ?>> Autorizacao</label>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group" style="flex: 1 1 100%;">
                                <label for="obs_docs">Observacoes</label>
                                <input type="text" id="obs_docs" name="obs_docs"
                                    value="<?php echo htmlspecialchars($documentos['observacoes'] ?? ''); ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Salvar Checklist</button>
                    </form>
                </div>

                <div class="info-card">
                    <h2>Documentos do Aluno</h2>
                    <form method="POST" action="aluno_info.php?id=<?php echo $aluno['id']; ?>" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_documento">
                        <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="doc_tipo">Tipo</label>
                                <select id="doc_tipo" name="doc_tipo">
                                    <option value="RG">RG</option>
                                    <option value="Atestado">Atestado</option>
                                    <option value="Autorizacao">Autorizacao</option>
                                    <option value="Outro">Outro</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 2 1 300px;">
                                <label for="doc_arquivo">Arquivo</label>
                                <input type="file" id="doc_arquivo" name="doc_arquivo" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Enviar Documento</button>
                    </form>

                    <?php if (count($documentos_arquivos) > 0): ?>
                    <table class="tabela-alunos">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Arquivo</th>
                                <th>Data</th>
                                <th>Acao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentos_arquivos as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['tipo']); ?></td>
                                <td><a href="<?php echo htmlspecialchars($doc['arquivo_path']); ?>" target="_blank">Abrir</a></td>
                                <td><?php echo date('d/m/Y', strtotime($doc['uploaded_at'])); ?></td>
                                <td>
                                    <form method="POST" action="aluno_info.php?id=<?php echo $aluno['id']; ?>" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_documento">
                                        <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
                                        <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                        <button type="submit" class="btn-acao excluir"
                                            onclick="return confirm('Remover este documento?');">
                                            Remover
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="info">Nenhum documento enviado.</p>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h2>Termo/Contrato</h2>
                    <form method="POST" action="aluno_info.php?id=<?php echo $aluno['id']; ?>">
                        <input type="hidden" name="action" value="save_termo">
                        <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
                        <div class="form-row">
                            <div class="form-group" style="flex: 1 1 100%;">
                                <label><input type="checkbox" name="termo_aceito" <?php echo (!empty($aluno['termo_aceito'])) ? 'checked' : ''; ?>> Aceito os termos</label>
                            </div>
                            <div class="form-group" style="flex: 1 1 300px;">
                                <label for="termo_nome">Nome para assinatura</label>
                                <input type="text" id="termo_nome" name="termo_nome"
                                    value="<?php echo htmlspecialchars($aluno['termo_nome'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Data</label>
                                <input type="text" value="<?php echo !empty($aluno['termo_data']) ? date('d/m/Y', strtotime($aluno['termo_data'])) : '---'; ?>" readonly>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Salvar Termo</button>
                    </form>
                    <form method="POST" action="aluno_info.php?id=<?php echo $aluno['id']; ?>" style="margin-top: 10px;">
                        <input type="hidden" name="action" value="cancel_termo">
                        <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
                        <button type="submit" class="btn-acao excluir"
                            onclick="return confirm('Remover termo deste aluno?');">
                            Remover Termo
                        </button>
                    </form>
                </div>

                <div class="info-card">
                    <h2>Evolucao de Faixas</h2>
                    <form method="POST" action="aluno_info.php?id=<?php echo $aluno['id']; ?>">
                        <input type="hidden" name="action" value="add_graduacao">
                        <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="faixa">Faixa</label>
                                <input type="text" id="faixa" name="faixa" required>
                            </div>
                            <div class="form-group">
                                <label for="data_exame">Data do exame</label>
                                <input type="date" id="data_exame" name="data_exame" required>
                            </div>
                            <div class="form-group" style="flex: 2 1 300px;">
                                <label for="requisitos">Requisitos</label>
                                <input type="text" id="requisitos" name="requisitos">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group" style="flex: 1 1 100%;">
                                <label for="obs_grad">Observacoes</label>
                                <input type="text" id="obs_grad" name="obs_grad">
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Adicionar Graduacao</button>
                    </form>

                    <?php if (count($graduacoes) > 0): ?>
                    <table class="tabela-alunos">
                        <thead>
                            <tr>
                                <th>Faixa</th>
                                <th>Data do Exame</th>
                                <th>Requisitos</th>
                                <th>Observacoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($graduacoes as $g): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($g['faixa']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($g['data_exame'])); ?></td>
                                <td><?php echo htmlspecialchars($g['requisitos'] ?? '---'); ?></td>
                                <td><?php echo htmlspecialchars($g['observacoes'] ?? '---'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="info">Sem historico de graduacoes.</p>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h2>Campeonatos e Colocacoes</h2>
                    <?php if (count($campeonatos_aluno) > 0): ?>
                    <table class="tabela-alunos">
                        <thead>
                            <tr>
                                <th>Campeonato</th>
                                <th>Data</th>
                                <th>Local</th>
                                <th>Pagamento</th>
                                <th>Colocacao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campeonatos_aluno as $camp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($camp['nome']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($camp['data_evento'])); ?></td>
                                <td><?php echo htmlspecialchars($camp['local']); ?></td>
                                <td><?php echo ucfirst($camp['status_pagamento']); ?></td>
                                <td><?php echo htmlspecialchars($camp['colocacao'] ?? '---'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="info">Sem inscricoes em campeonatos.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>

</html>
