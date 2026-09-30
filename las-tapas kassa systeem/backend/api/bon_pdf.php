<?php
require_once __DIR__ . '/../lib/auth.php';
requireAuthenticatedUser();
requireHttpMethod('GET');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/bon_data.php';
require_once __DIR__ . '/../lib/pdf_bon.php';

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

$pdfBytes = generateReceiptPdf($receiptData);
$fileName = 'bon-' . $receiptData['bestelling_id'] . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $fileName . '"');
header('Content-Length: ' . strlen($pdfBytes));
echo $pdfBytes;
