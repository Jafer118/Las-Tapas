<?php
/**
 * POST api/status_bijwerken.php
 *
 * Verwacht JSON body:
 * { "orderregel_id": 12, "status": "bereid" }
 *
 * Toegestane statussen: besteld -> bereid -> geserveerd
 * Wordt aangeroepen vanuit keuken.html / bar.html als personeel
 * op "Gereed" of "Geserveerd" klikt.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

$geldigeStatussen = ['besteld', 'bereid', 'geserveerd'];

if (
    !$input ||
    empty($input['orderregel_id']) ||
    empty($input['status']) ||
    !in_array($input['status'], $geldigeStatussen, true)
) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => 'Ongeldige invoer.']);
    exit;
}

$orderregelId = (int) $input['orderregel_id'];
$nieuweStatus = $input['status'];

$stmt = $pdo->prepare('UPDATE orderregels SET status = ? WHERE id = ?');
$stmt->execute([$nieuweStatus, $orderregelId]);

echo json_encode(['succes' => true]);
