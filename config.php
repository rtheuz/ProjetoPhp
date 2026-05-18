<?php
// Configuração do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'cdc_3b_g4');
define('DB_PASS', 'g4B@123');
define('DB_NAME', 'cdc_3b_grupo4');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
}
?>
