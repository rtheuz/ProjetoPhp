<?php
// Teste simples de conexão ao banco usando as constantes de config.php
require __DIR__ . '/config.php';

$host = isset($_GET['host']) && $_GET['host'] !== '' ? $_GET['host'] : DB_HOST;
$port = isset($_GET['port']) && $_GET['port'] !== '' ? (int)$_GET['port'] : null;

$dsn = 'mysql:host=' . $host . ';dbname=' . DB_NAME . ';charset=utf8mb4';
if ($port) {
    $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4';
}

try {
    $pdoTest = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Conexão OK com host={$host}" . ($port ? " port={$port}" : "") . "\n";
    $row = $pdoTest->query('SELECT NOW() as now')->fetch(PDO::FETCH_ASSOC);
    echo 'Hora do servidor DB: ' . ($row['now'] ?? 'n/a');
} catch (PDOException $e) {
    echo 'Erro ao conectar: ' . $e->getMessage();
}

?>
