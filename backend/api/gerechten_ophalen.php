<?php
/**
 * GET api/gerechten_ophalen.php
 * Geeft alle gerechten terug (voor de bestelpagina).
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$stmt = $pdo->query('SELECT id, naam, categorie, prijs, voorraad FROM gerechten ORDER BY categorie, naam');
$gerechten = $stmt->fetchAll();

echo json_encode(['succes' => true, 'gerechten' => $gerechten]);
