<?php
/**
 * db.php
 * Shared PDO database connection for all API scripts.
 * Update these settings for your local environment (e.g. XAMPP or MAMP).
 */

$databaseHost = getenv('LAS_TAPAS_DB_HOST') ?: 'localhost';
$databaseName = getenv('LAS_TAPAS_DB_NAME') ?: 'las_tapas';
$databaseUser = getenv('LAS_TAPAS_DB_USER') ?: 'root';
$databasePassword = getenv('LAS_TAPAS_DB_PASS');
$databasePassword = $databasePassword === false ? '' : $databasePassword;
$databaseCharset = 'utf8mb4';

$dsn = "mysql:host={$databaseHost};dbname={$databaseName};charset={$databaseCharset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $databaseUser, $databasePassword, $options);
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['succes' => false, 'fout' => 'The service is temporarily unavailable.']);
    exit;
}
