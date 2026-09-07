<?php
/**
 * GET api/tafels_ophalen.php
 * Geeft alle tafels terug met status (vrij/bezet).
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$stmt = $pdo->query('SELECT id, naam, status FROM tafels ORDER BY id');
$tafels = $stmt->fetchAll();

echo json_encode(['succes' => true, 'tafels' => $tafels]);
