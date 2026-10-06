<?php

declare(strict_types=1);

$environmentFile = __DIR__ . '/environment.php';
if (is_file($environmentFile)) {
    require_once $environmentFile;
}

$appEnv = strtolower((string) (getenv('APP_ENV') ?: getenv('ENVIRONMENT') ?: ($_SERVER['APP_ENV'] ?? 'development')));
$envSuffix = in_array($appEnv, ['production', 'prod'], true) ? 'PROD' : (in_array($appEnv, ['development', 'dev'], true) ? 'DEV' : strtoupper($appEnv));

$resolvedHost = getenv('DB_HOST_' . $envSuffix) ?: getenv('DB_HOST') ?: 'localhost';
$resolvedPort = getenv('DB_PORT_' . $envSuffix) ?: getenv('DB_PORT') ?: '3306';
$resolvedDbName = getenv('DB_NAME_' . $envSuffix) ?: getenv('DB_NAME') ?: 'crm_database';
$resolvedUser = getenv('DB_USER_' . $envSuffix) ?: getenv('DB_USER') ?: 'root';
$resolvedPassword = getenv('DB_PASSWORD_' . $envSuffix) ?: getenv('DB_PASSWORD') ?: '';

$host = $resolvedHost;
$port = $resolvedPort;
$dbname = $resolvedDbName;
$username = $resolvedUser;
$password = $resolvedPassword;

try {
    $conn = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed.');
}
