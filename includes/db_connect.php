<?php
$host = 'localhost'; // Geralmente 'localhost' no XAMPP
$db   = 'academia_judo';
$user = 'root'; // Usuário padrão do XAMPP sem senha
$pass = ''; // Senha padrão do XAMPP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
// Agora a variável $pdo contém a conexão ativa com o banco de dados.
?>