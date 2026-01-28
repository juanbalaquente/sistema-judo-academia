<?php
// config.php
// Dados de Conexao MySQL (XAMPP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'speednet_reports');
define('DB_USER', 'root');
define('DB_PASS', '');

// Dados da API do Zabbix
define('ZBX_URL', 'https://sistema.speednettelecom.com.br/zabbix/api_jsonrpc.php');
define('ZBX_TOKEN', 'SEU_TOKEN_DE_API_ZABBIX_AQUI');
define('CURL_SSL_VERIFY', false);
?>
