<?php
// Debug file to check environment variables in Railway
echo "=== Database Configuration Debug ===\n\n";

$vars = [
    'DB_HOST',
    'DB_PORT',
    'DB_USER',
    'DB_PASS',
    'DB_NAME',
    'MYSQLHOST',
    'MYSQLPORT',
    'MYSQLUSER',
    'MYSQLPASSWORD',
    'MYSQLDATABASE',
];

foreach ($vars as $var) {
    $value = getenv($var);
    if ($value === false) {
        echo "$var: NOT SET\n";
    } else {
        // Hide password
        if (strpos($var, 'PASS') !== false || strpos($var, 'PASSWORD') !== false) {
            echo "$var: (hidden)\n";
        } else {
            echo "$var: $value\n";
        }
    }
}

echo "\n=== Config.php will use ===\n\n";

$db_host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306';
$db_user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'cdc_3b_g4';
$db_name = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: 'cdc_3b_grupo4';

echo "Host: $db_host\n";
echo "Port: $db_port\n";
echo "User: $db_user\n";
echo "Database: $db_name\n";
?>
