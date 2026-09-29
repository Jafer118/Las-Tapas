<?php
require_once __DIR__ . '/../lib/auth.php';
vereisIngelogd();
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$stmt = $pdo->query('SELECT id, naam, capaciteit, verdieping, status FROM tafels ORDER BY id');
$tafels = $stmt->fetchAll();

echo json_encode(['succes' => true, 'tafels' => $tafels]);
