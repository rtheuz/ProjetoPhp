<?php
// Configuração do banco de dados.
// Compatível com variáveis genéricas (DB_*) e com as variáveis do Railway (MYSQL*).

$db_host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306';
$db_user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'cdc_3b_g4';
$db_pass = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: 'g4B@123';
$db_name = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'cdc_3b_grupo4';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

try {
    $pdo = new PDO(
        $dsn,
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}
?>
