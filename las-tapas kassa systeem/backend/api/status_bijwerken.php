<?php
/**
 * POST api/status_bijwerken.php
 *
 * Expects a JSON body:
 * { "orderregel_id": 12, "status": "bereid" }
 *
 * Allowed status values: 'besteld' -> 'bereid' -> 'geserveerd'.
 * Called from kitchen.html or bar.html when staff update an order line.
 */

require_once __DIR__ . '/../lib/auth.php';
requireAuthenticatedUser();
requireHttpMethod('POST');
requireValidCsrfToken();
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$requestData = decodeJsonRequestBody();

$orderLineId = parsePositiveInteger($requestData['orderregel_id'] ?? null);
$nextStatus = $requestData['status'] ?? null;
if ($orderLineId === null || !is_string($nextStatus) || !in_array($nextStatus, ['bereid', 'geserveerd'], true)) {
    sendJsonResponse(['succes' => false, 'fout' => 'Ongeldige invoer.'], 422);
}

try {
    $pdo->beginTransaction();
    $statement = $pdo->prepare('SELECT status FROM orderregels WHERE id = ? FOR UPDATE');
    $statement->execute([$orderLineId]);
    $currentStatus = $statement->fetchColumn();

    if ($currentStatus === false) {
        $pdo->rollBack();
        sendJsonResponse(['succes' => false, 'fout' => 'Bestelregel niet gevonden.'], 404);
    }

    if (!isAllowedOrderStatusTransition($currentStatus, $nextStatus)) {
        $pdo->rollBack();
        sendJsonResponse(['succes' => false, 'fout' => 'Deze statusovergang is niet toegestaan.'], 409);
    }

    $statement = $pdo->prepare('UPDATE orderregels SET status = ? WHERE id = ?');
    $statement->execute([$nextStatus, $orderLineId]);
    $pdo->commit();
    sendJsonResponse(['succes' => true]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order status update failed: ' . $exception->getMessage());
    sendJsonResponse(['succes' => false, 'fout' => 'De bestelstatus kon niet worden bijgewerkt.'], 500);
}
