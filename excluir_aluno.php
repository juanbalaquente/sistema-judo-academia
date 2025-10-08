<?php
require 'includes/auth_check.php'; 
// require 'includes/auth_check.php'; // Adicionar se estiver usando a autenticação

require 'includes/db_connect.php'; 

// Verifica se um ID foi passado pela URL e se é um número
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $aluno_id = $_GET['id'];
    
    try {
        // Prepara e executa o DELETE com Prepared Statement
        $sql = "DELETE FROM alunos WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $aluno_id]);

        // Redireciona de volta para a lista após a exclusão
        header('Location: alunos_list.php?status=excluido');
        exit;

    } catch (Exception $e) {
        // Em caso de erro, você pode redirecionar para uma página de erro ou exibir a mensagem
        echo "Erro ao tentar excluir o aluno: " . $e->getMessage();
    }
} else {
    // Se o ID não foi passado corretamente, redireciona para a lista
    header('Location: alunos_list.php?status=erro_id');
    exit;
}
?>