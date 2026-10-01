<?php
/**
 * GET api/bon_gegevens.php?tafel_id=10
 * GET api/bon_gegevens.php?bestelling_id=7
 *
 * Returns the data needed to display a receipt in bon.html as JSON.
 * Use bon_pdf.php to download the same receipt as a PDF.
 */

require_once __DIR__ . '/../lib/auth.php';
requireAuthenticatedUser();
requireHttpMethod('GET');
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/bon_data.php';

$hasTableParameter = array_key_exists('tafel_id', $_GET);
$hasOrderParameter = array_key_exists('bestelling_id', $_GET);
if ($hasTableParameter === $hasOrderParameter) {
    sendJsonResponse(['succes' => false, 'fout' => 'Geef precies een geldige tafel_id of bestelling_id op.'], 422);
}

$tableId = $hasTableParameter ? parsePositiveInteger($_GET['tafel_id']) : null;
$orderId = $hasOrderParameter ? parsePositiveInteger($_GET['bestelling_id']) : null;

if (($hasTableParameter && $tableId === null) || ($hasOrderParameter && $orderId === null)) {
    sendJsonResponse(['succes' => false, 'fout' => 'Geef precies een geldige tafel_id of bestelling_id op.'], 422);
}

$receiptData = getReceiptData($pdo, $tableId ?? 0, $orderId ?? 0);

if (!$receiptData) {
    sendJsonResponse(['succes' => false, 'fout' => 'Geen (openstaande) bestelling gevonden.'], 404);
}

sendJsonResponse(array_merge(['succes' => true], $receiptData));
