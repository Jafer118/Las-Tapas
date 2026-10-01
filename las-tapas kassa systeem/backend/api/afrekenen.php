<?php
/**
 * POST api/afrekenen.php
 * Expects a JSON body: { "tafel_id": 10 }
 *
 * Closes the table's open order (status = 'afgerekend') and sets the table
 * back to 'vrij'.
 */

require_once __DIR__ . '/../lib/auth.php';
requireAuthenticatedUser();
requireHttpMethod('POST');
requireValidCsrfToken();
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$requestData = decodeJsonRequestBody();

$tableId = parsePositiveInteger($requestData['tafel_id'] ?? null);
if ($tableId === null) {
    sendJsonResponse(['succes' => false, 'fout' => 'Een geldig tafel_id is verplicht.'], 422);
}

try {
    $pdo->beginTransaction();

    $statement = $pdo->prepare('SELECT id FROM tafels WHERE id = ? FOR UPDATE');
    $statement->execute([$tableId]);
    if (!$statement->fetch()) {
        throw new InvalidArgumentException('Tafel bestaat niet.');
    }

    $statement = $pdo->prepare("SELECT id, klant_email FROM bestellingen WHERE tafel_id = ? AND status = 'open' LIMIT 1 FOR UPDATE");
    $statement->execute([$tableId]);
    $order = $statement->fetch();

    if (!$order) {
        throw new InvalidArgumentException('Geen openstaande bestelling gevonden voor deze tafel.');
    }

    $statement = $pdo->prepare("UPDATE bestellingen SET status = 'afgerekend' WHERE id = ?");
    $statement->execute([$order['id']]);

    $statement = $pdo->prepare("UPDATE tafels SET status = 'vrij' WHERE id = ?");
    $statement->execute([$tableId]);

    $pdo->commit();

    sendJsonResponse([
        'succes'        => true,
        'bestelling_id' => $order['id'],
        'klant_email'   => $order['klant_email'],
    ]);
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendJsonResponse(['succes' => false, 'fout' => $exception->getMessage()], 404);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Checkout failed: ' . $exception->getMessage());
    sendJsonResponse(['succes' => false, 'fout' => 'Afrekenen is mislukt. Probeer het later opnieuw.'], 500);
}
