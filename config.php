<?php
// Configuração do banco de dados — usa variáveis de ambiente em produção
// Para desenvolvimento local, valores padrão são usados como fallback.

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'cdc_3b_g4';
$db_pass = getenv('DB_PASS') ?: 'g4B@123';
$db_name = getenv('DB_NAME') ?: 'cdc_3b_grupo4';

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
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
