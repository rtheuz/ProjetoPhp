<?php
// Configuração do banco de dados.
// Compatível com variáveis genéricas (DB_*) e com as variáveis do Railway (MYSQL*).

$db_host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
$db_user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'cdc_3b_g4';
$db_pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: 'g4B@123';
$db_name = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'cdc_3b_grupo4';

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
