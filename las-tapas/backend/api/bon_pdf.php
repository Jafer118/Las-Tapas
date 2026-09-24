<?php
/**
 * GET api/bon_pdf.php?tafel_id=10
 * GET api/bon_pdf.php?bestelling_id=7
 *
 * Genereert een echt PDF-bestand van het bonnetje en stuurt dit direct
 * terug als download (Content-Type: application/pdf).
 */

require_once __DIR__ . '/../lib/auth.php';
vereisIngelogd();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/bon_data.php';
require_once __DIR__ . '/../lib/pdf_bon.php';

$tafelId      = isset($_GET['tafel_id']) ? (int) $_GET['tafel_id'] : 0;
$bestellingId = isset($_GET['bestelling_id']) ? (int) $_GET['bestelling_id'] : 0;

$bonData = bon_data_ophalen($pdo, $tafelId, $bestellingId);

if (!$bonData) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['succes' => false, 'fout' => 'Geen (openstaande) bestelling gevonden.']);
    exit;
}

$pdfBytes = genereer_bon_pdf($bonData);
$bestandsnaam = 'bon-' . $bonData['bestelling_id'] . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $bestandsnaam . '"');
header('Content-Length: ' . strlen($pdfBytes));
echo $pdfBytes;
