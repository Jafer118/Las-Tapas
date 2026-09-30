<?php
/**
 * db.php
 * Shared PDO database connection for all API scripts.
 * Update these settings for your local environment (e.g. XAMPP or MAMP).
 */

$DB_HOST = 'localhost';
$DB_NAME = 'las_tapas';
$DB_USER = 'root';
$DB_PASS = '';        // Empty by default in XAMPP; set this if needed.
$DB_CHARSET = 'utf8mb4';

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['succes' => false, 'fout' => 'The service is temporarily unavailable.']);
    exit;
}
