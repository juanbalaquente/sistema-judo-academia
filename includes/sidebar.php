<?php
// includes/sidebar.php

// Pega o nome do usuário logado para exibir no menu (definido em login.php)
// Se a sessão não estiver iniciada ou o nome não estiver definido (caso improvável após auth_check), usa 'Usuário'.
$userName = $_SESSION['username'] ?? 'Usuário';

// Obtém o nome do script atual para destacar o item ativo no menu
$current_page = basename($_SERVER['PHP_SELF']); 
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3>🥋 JUDÔ - ADMIN</h3>
        <p>Bem-vindo(a), **<?php echo htmlspecialchars($userName); ?>**</p>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a href="index.php">
                    <span class="icon">➕</span> Cadastro de Aluno
                </a>
            </li>

            <li class="<?php echo ($current_page == 'alunos_list.php') ? 'active' : ''; ?>">
                <a href="alunos_list.php">
                    <span class="icon">📋</span> Lista e Busca
                </a>
            </li>

            <li class="disabled">
                <a href="#">
                    <span class="icon">📅</span> Presença (Em Breve)
                </a>
            </li>

            <li class="logout-link">
                <a href="logout.php">
                    <span class="icon">🚪</span> Sair
                </a>
            </li>
        </ul>
    </nav>
</div>