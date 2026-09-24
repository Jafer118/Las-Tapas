<?php
/**
 * GET api/bon_gegevens.php?tafel_id=10
 * GET api/bon_gegevens.php?bestelling_id=7
 *
 * Geeft alle gegevens terug die nodig zijn om een bonnetje te tonen op
 * bon.html (JSON-variant). Voor de echte PDF-download, zie bon_pdf.php.
 */

require_once __DIR__ . '/../lib/auth.php';
vereisIngelogd();
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/bon_data.php';

$tafelId      = isset($_GET['tafel_id']) ? (int) $_GET['tafel_id'] : 0;
$bestellingId = isset($_GET['bestelling_id']) ? (int) $_GET['bestelling_id'] : 0;

if ($tafelId <= 0 && $bestellingId <= 0) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => 'tafel_id of bestelling_id is verplicht.']);
    exit;
}

$bonData = bon_data_ophalen($pdo, $tafelId, $bestellingId);

if (!$bonData) {
    http_response_code(404);
    echo json_encode(['succes' => false, 'fout' => 'Geen (openstaande) bestelling gevonden.']);
    exit;
}

echo json_encode(array_merge(['succes' => true], $bonData));
