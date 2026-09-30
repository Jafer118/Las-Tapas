<?php
require_once __DIR__ . '/../lib/auth.php';
requireAuthenticatedUser();
requireHttpMethod('GET');
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$statement = $pdo->query('SELECT id, naam, capaciteit, verdieping, status FROM tafels ORDER BY id');
$tables = $statement->fetchAll();

echo json_encode(['succes' => true, 'tafels' => $tables]);
