<?php
require_once __DIR__ . '/config.php';

// One-time migration endpoint protected by an environment key.
$migrateKey = getenv('MIGRATE_KEY') ?: '';
$providedKey = isset($_GET['key']) ? (string)$_GET['key'] : '';

if ($migrateKey === '' || !hash_equals($migrateKey, $providedKey)) {
    http_response_code(403);
    echo "Acesso negado. Defina MIGRATE_KEY no ambiente e chame com ?key=...\n";
    exit;
}

$sqlFile = __DIR__ . '/database.sql';
if (!file_exists($sqlFile)) {
    http_response_code(500);
    echo "Arquivo database.sql nao encontrado.\n";
    exit;
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    http_response_code(500);
    echo "Nao foi possivel ler database.sql.\n";
    exit;
}

// Remove comentários de linha para facilitar o split.
$sql = preg_replace('/^\s*(--|#).*$/m', '', $sql);
$statements = array_filter(array_map('trim', explode(';', $sql)));

$executed = 0;
try {
    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
        $executed++;
    }

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Migracao concluida. Statements executados: {$executed}\n";
    echo "Tabelas:\n";
    foreach ($tables as $table) {
        echo "- {$table}\n";
    }
    echo "\nRemova migrate.php apos uso por seguranca.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Falha na migracao: " . $e->getMessage() . "\n";
}
