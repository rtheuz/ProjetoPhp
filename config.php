<?php
// Configuração do banco de dados.
// Prioriza MYSQL_URL (Railway) para evitar inconsistência de variáveis copiadas manualmente.

$mysql_url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';
$parsed = $mysql_url ? parse_url($mysql_url) : false;

$db_host = $parsed['host'] ?? (getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
$db_port = isset($parsed['port']) ? (string)$parsed['port'] : (getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306');
$db_user = isset($parsed['user']) ? urldecode($parsed['user']) : (getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'cdc_3b_g4');
$db_pass = isset($parsed['pass']) ? urldecode($parsed['pass']) : (getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: 'g4B@123');
$db_name = isset($parsed['path']) ? ltrim($parsed['path'], '/') : (getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'cdc_3b_grupo4');

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
