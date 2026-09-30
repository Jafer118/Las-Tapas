<?php
/**
 * POST api/bestelling_toevoegen.php
 *
 * Expects a JSON body:
 * {
 *   "tafel_id": 10,
 *   "regels": [
 *     { "gerecht_id": 1, "aantal": 2 },
 *     { "gerecht_id": 7, "aantal": 4 }
 *   ]
 * }
 *
 * Performs these steps in a single transaction:
 *  1. Finds or creates an open order for the table.
 *  2. Checks stock for each item.
 *  3. Adds order lines and deducts stock.
 *  4. Marks the table as occupied.
 */

require_once __DIR__ . '/../lib/auth.php';
requireAuthenticatedUser();
requireHttpMethod('POST');
requireValidCsrfToken();

require_once __DIR__ . '/../db.php';

$requestData = decodeJsonRequestBody();
$tableId = parsePositiveInteger($requestData['tafel_id'] ?? null);
if ($tableId === null) {
    sendJsonResponse(['succes' => false, 'fout' => 'Een geldig tafel_id is verplicht.'], 422);
}

$orderLines = $requestData['regels'] ?? null;
$orderLinesAreList = is_array($orderLines)
    && array_keys($orderLines) === range(0, count($orderLines) - 1);
$customerEmailIsString = !isset($requestData['klant_email']) || is_string($requestData['klant_email']);
$customerEmail = $customerEmailIsString && isset($requestData['klant_email'])
    ? trim($requestData['klant_email'])
    : '';
$guestCount = isset($requestData['aantal_personen']) && $requestData['aantal_personen'] !== ''
    ? (is_scalar($requestData['aantal_personen'])
        ? filter_var($requestData['aantal_personen'], FILTER_VALIDATE_INT)
        : false)
    : null;

if (!$orderLinesAreList || count($orderLines) === 0 || count($orderLines) > 100) {
    sendJsonResponse(['succes' => false, 'fout' => 'Een geldige tafel en 1 tot 100 bestelregels zijn verplicht.'], 422);
}

if (!$customerEmailIsString) {
    sendJsonResponse(['succes' => false, 'fout' => 'Het opgegeven e-mailadres is ongeldig.'], 422);
}

if ($customerEmail !== '' && (strlen($customerEmail) > 254 || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL))) {
    sendJsonResponse(['succes' => false, 'fout' => 'Het opgegeven e-mailadres is ongeldig.'], 422);
}

if ($guestCount !== null && ($guestCount === false || $guestCount <= 0)) {
    sendJsonResponse(['succes' => false, 'fout' => 'Aantal personen moet minimaal 1 zijn.'], 422);
}

foreach ($orderLines as $orderLine) {
    if (!is_array($orderLine)
        || !isset($orderLine['gerecht_id'], $orderLine['aantal'])
        || !is_scalar($orderLine['gerecht_id'])
        || !is_scalar($orderLine['aantal'])
        || filter_var($orderLine['gerecht_id'], FILTER_VALIDATE_INT) === false
        || filter_var($orderLine['aantal'], FILTER_VALIDATE_INT) === false
        || (int) $orderLine['gerecht_id'] <= 0
        || (int) $orderLine['aantal'] <= 0
        || (int) $orderLine['aantal'] > 99
    ) {
        sendJsonResponse(['succes' => false, 'fout' => 'Elke bestelregel vereist een geldig gerecht_id en een aantal van 1 tot 99.'], 422);
    }
}

try {
    $pdo->beginTransaction();

    // 1. Check that the table exists and retrieve its capacity.
    $statement = $pdo->prepare('SELECT id, naam, capaciteit FROM tafels WHERE id = ? FOR UPDATE');
    $statement->execute([$tableId]);
    $table = $statement->fetch();
    if (!$table) {
        throw new InvalidArgumentException('Tafel bestaat niet.');
    }

    if ($guestCount !== null && $guestCount > $table['capaciteit']) {
        throw new InvalidArgumentException(
            "{$table['naam']} heeft plaats voor maximaal {$table['capaciteit']} personen "
            . "(opgegeven: {$guestCount})."
        );
    }

    // 2. Find or create an open order for this table.
    $statement = $pdo->prepare("SELECT id FROM bestellingen WHERE tafel_id = ? AND status = 'open' LIMIT 1");
    $statement->execute([$tableId]);
    $order = $statement->fetch();

    if ($order) {
        $orderId = $order['id'];
        // Update the order if an email address or party size is provided later.
        if ($customerEmail !== '') {
            $statement = $pdo->prepare('UPDATE bestellingen SET klant_email = ? WHERE id = ?');
            $statement->execute([$customerEmail, $orderId]);
        }
        if ($guestCount !== null) {
            $statement = $pdo->prepare('UPDATE bestellingen SET aantal_personen = ? WHERE id = ?');
            $statement->execute([$guestCount, $orderId]);
        }
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO bestellingen (tafel_id, klant_email, aantal_personen, status) VALUES (?, ?, ?, "open")'
        );
        $statement->execute([$tableId, $customerEmail !== '' ? $customerEmail : null, $guestCount]);
        $orderId = $pdo->lastInsertId();
    }

    // 3. Check stock, add each order line, and deduct stock.
    $menuItemStatement = $pdo->prepare('SELECT naam, prijs, voorraad FROM gerechten WHERE id = ? FOR UPDATE');
    $insertOrderLineStatement = $pdo->prepare(
        'INSERT INTO orderregels (bestelling_id, gerecht_id, aantal, prijs_per_stuk, status)
         VALUES (?, ?, ?, ?, "besteld")'
    );
    $decreaseStockStatement = $pdo->prepare('UPDATE gerechten SET voorraad = voorraad - ? WHERE id = ?');

    foreach ($orderLines as $orderLine) {
        $menuItemId = (int) $orderLine['gerecht_id'];
        $quantity = (int) $orderLine['aantal'];

        $menuItemStatement->execute([$menuItemId]);
        $menuItem = $menuItemStatement->fetch();

        if (!$menuItem) {
            throw new InvalidArgumentException("Gerecht met id {$menuItemId} bestaat niet.");
        }

        if ($menuItem['voorraad'] < $quantity) {
            throw new InvalidArgumentException("Onvoldoende voorraad voor '{$menuItem['naam']}' (nog {$menuItem['voorraad']} beschikbaar).");
        }

        // Store the current price so later menu changes do not affect past orders.
        $insertOrderLineStatement->execute([$orderId, $menuItemId, $quantity, $menuItem['prijs']]);

        $decreaseStockStatement->execute([$quantity, $menuItemId]);
    }

    $statement = $pdo->prepare("UPDATE tafels SET status = 'bezet' WHERE id = ?");
    $statement->execute([$tableId]);

    $pdo->commit();

    sendJsonResponse(['succes' => true, 'bestelling_id' => $orderId]);
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendJsonResponse(['succes' => false, 'fout' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order creation failed: ' . $exception->getMessage());
    sendJsonResponse(['succes' => false, 'fout' => 'Bestelling kon niet worden opgeslagen. Probeer het later opnieuw.'], 500);
}
