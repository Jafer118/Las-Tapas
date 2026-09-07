<?php
/**
 * POST api/afrekenen.php
 * Verwacht JSON body: { "tafel_id": 10 }
 *
 * Sluit de openstaande bestelling van deze tafel af (status = afgerekend)
 * en zet de tafel weer op 'vrij'.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['tafel_id'])) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => 'tafel_id is verplicht.']);
    exit;
}

$tafelId = (int) $input['tafel_id'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id FROM bestellingen WHERE tafel_id = ? AND status = 'open' LIMIT 1");
    $stmt->execute([$tafelId]);
    $bestelling = $stmt->fetch();

    if (!$bestelling) {
        throw new Exception('Geen openstaande bestelling gevonden voor deze tafel.');
    }

    $stmt = $pdo->prepare("UPDATE bestellingen SET status = 'afgerekend' WHERE id = ?");
    $stmt->execute([$bestelling['id']]);

    $stmt = $pdo->prepare("UPDATE tafels SET status = 'vrij' WHERE id = ?");
    $stmt->execute([$tafelId]);

    $pdo->commit();

    echo json_encode(['succes' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => $e->getMessage()]);
}
