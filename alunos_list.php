<?php
// ... seu código PHP (topo) de autenticação, paginação e filtro ...
require 'includes/auth_check.php'; 
// ... restante do PHP ...
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
</head>

<body>

    <div class="main-wrapper">

        <?php include 'includes/sidebar.php'; ?>

        <div class="content-area">

            <div class="container">

                <div
                    style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                    <h1>Lista de Alunos Cadastrados</h1>
                </div>

                <?php echo $message; ?>

            </div>
        </div>
    </div>
</body>

</html>