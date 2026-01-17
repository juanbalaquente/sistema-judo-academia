<?php
// includes/sidebar.php

$userName = $_SESSION['username'] ?? 'Usuário';

$current_page = basename($_SERVER['PHP_SELF']); 
?>

<div class="sidebar">
    <div class="sidebar-header">
        <img class="logo" src="assets/logo.png" alt="Judo Sao Geraldo">
        <h3>JUDO SAO GERALDO</h3>
        <p>Bem-vindo(a), **<?php echo htmlspecialchars($userName); ?>**</p>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="dashboard.php">Dashboard
                </a>
            </li>

            <li class="<?php echo ($current_page == 'financeiro.php') ? 'active' : ''; ?>">
                <a href="financeiro.php">Controle Financeiro
                </a>
            </li>

            <li class="<?php echo ($current_page == 'campeonatos.php') ? 'active' : ''; ?>">
                <a href="campeonatos.php">Campeonatos
                </a>
            </li>

            <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a href="index.php">Cadastro de Aluno
                </a>
            </li>

            <li
                class="<?php echo ($current_page == 'alunos_list.php' || $current_page == 'editar_aluno.php' || $current_page == 'historico_financeiro.php') ? 'active' : ''; ?>">
                <a href="alunos_list.php">Lista e Busca
                </a>
            </li>

            <li class="<?php echo ($current_page == 'presenca.php') ? 'active' : ''; ?>">
                <a href="presenca.php">Registro de Presença
                </a>
            </li>

            <li class="<?php echo ($current_page == 'aluno_info.php') ? 'active' : ''; ?>">
                <a href="aluno_info.php">
                    Informacoes do Aluno
                </a>
            </li>

                        <li class="<?php echo ($current_page == 'relatorios.php' || $current_page == 'relatorio_mensalidades.php' || $current_page == 'relatorio_presenca.php' || $current_page == 'relatorio_aluno.php') ? 'active' : ''; ?>">
                <a href="relatorios.php">Relatorios
                </a>
            </li>

            <li class="logout-link">
                <a href="logout.php">Sair
                </a>
            </li>
        </ul>
    </nav>
</div>
