<?php
// includes/sidebar.php

$userName = $_SESSION['username'] ?? 'Usuário';

$current_page = basename($_SERVER['PHP_SELF']); 
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3>🥋 JUDÔ - ADMIN</h3>
        <p>Bem-vindo(a), **<?php echo htmlspecialchars($userName); ?>**</p>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($current_page == 'financeiro.php') ? 'active' : ''; ?>">
                <a href="financeiro.php">
                    <span class="icon">💰</span> Controle Financeiro
                </a>
            </li>

            <li class="<?php echo ($current_page == 'campeonatos.php') ? 'active' : ''; ?>">
                <a href="campeonatos.php">
                    <span class="icon">🏆</span> Campeonatos
                </a>
            </li>

            <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a href="index.php">
                    <span class="icon">➕</span> Cadastro de Aluno
                </a>
            </li>

            <li
                class="<?php echo ($current_page == 'alunos_list.php' || $current_page == 'editar_aluno.php' || $current_page == 'historico_financeiro.php') ? 'active' : ''; ?>">
                <a href="alunos_list.php">
                    <span class="icon">📋</span> Lista e Busca
                </a>
            </li>

            <li class="<?php echo ($current_page == 'presenca.php') ? 'active' : ''; ?>">
                <a href="presenca.php">
                    <span class="icon">📅</span> Registro de Presença
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