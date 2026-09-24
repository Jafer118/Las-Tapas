<?php
/**
 * GET api/gerechten_ophalen.php
 * Geeft alle gerechten terug (voor de bestelpagina).
 */

require_once __DIR__ . '/../lib/auth.php';
vereisIngelogd();
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$stmt = $pdo->query(
    "SELECT id, naam, beschrijving, categorie, menugroep, prijs, voorraad
     FROM gerechten
     ORDER BY FIELD(menugroep, 'Frías', 'Calientes', 'Especialidades', 'Postres', 'Bebidas'), naam"
);
$gerechten = $stmt->fetchAll();

echo json_encode(['succes' => true, 'gerechten' => $gerechten]);
