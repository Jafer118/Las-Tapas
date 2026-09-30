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
vereisIngelogd();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    antwoord(['succes' => false, 'fout' => 'Alleen POST-verzoeken zijn toegestaan.'], 405);
}

require_once __DIR__ . '/../db.php';

$rawInput = file_get_contents('php://input');
$requestData = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($requestData)) {
    antwoord(['succes' => false, 'fout' => 'De JSON-invoer is ongeldig.'], 400);
}

if (!isset($requestData['tafel_id']) || filter_var($requestData['tafel_id'], FILTER_VALIDATE_INT) === false) {
    antwoord(['succes' => false, 'fout' => 'Een geldig tafel_id is verplicht.'], 422);
}

$tableId = (int) $requestData['tafel_id'];
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

if ($tableId <= 0 || !$orderLinesAreList || count($orderLines) === 0 || count($orderLines) > 100) {
    antwoord(['succes' => false, 'fout' => 'Een geldige tafel en 1 tot 100 bestelregels zijn verplicht.'], 422);
}

if (!$customerEmailIsString) {
    antwoord(['succes' => false, 'fout' => 'Het opgegeven e-mailadres is ongeldig.'], 422);
}

if ($customerEmail !== '' && (strlen($customerEmail) > 254 || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL))) {
    antwoord(['succes' => false, 'fout' => 'Het opgegeven e-mailadres is ongeldig.'], 422);
}

if ($guestCount !== null && ($guestCount === false || $guestCount <= 0)) {
    antwoord(['succes' => false, 'fout' => 'Aantal personen moet minimaal 1 zijn.'], 422);
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
        antwoord(['succes' => false, 'fout' => 'Elke bestelregel vereist een geldig gerecht_id en een aantal van 1 tot 99.'], 422);
    }
}

try {
    $pdo->beginTransaction();

    // 1. Check that the table exists and retrieve its capacity.
    $stmt = $pdo->prepare('SELECT id, naam, capaciteit FROM tafels WHERE id = ? FOR UPDATE');
    $stmt->execute([$tableId]);
    $table = $stmt->fetch();
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
    $stmt = $pdo->prepare("SELECT id FROM bestellingen WHERE tafel_id = ? AND status = 'open' LIMIT 1");
    $stmt->execute([$tableId]);
    $order = $stmt->fetch();

    if ($order) {
        $orderId = $order['id'];
        // Update the order if an email address or party size is provided later.
        if ($customerEmail !== '') {
            $stmt = $pdo->prepare('UPDATE bestellingen SET klant_email = ? WHERE id = ?');
            $stmt->execute([$customerEmail, $orderId]);
        }
        if ($guestCount !== null) {
            $stmt = $pdo->prepare('UPDATE bestellingen SET aantal_personen = ? WHERE id = ?');
            $stmt->execute([$guestCount, $orderId]);
        }
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO bestellingen (tafel_id, klant_email, aantal_personen, status) VALUES (?, ?, ?, "open")'
        );
        $stmt->execute([$tableId, $customerEmail !== '' ? $customerEmail : null, $guestCount]);
        $orderId = $pdo->lastInsertId();
    }

    // 3. Check stock, add each order line, and deduct stock.
    $stmtGerecht = $pdo->prepare('SELECT naam, prijs, voorraad FROM gerechten WHERE id = ? FOR UPDATE');
    $stmtInsertRegel = $pdo->prepare(
        'INSERT INTO orderregels (bestelling_id, gerecht_id, aantal, prijs_per_stuk, status)
         VALUES (?, ?, ?, ?, "besteld")'
    );
    $stmtVerlaagVoorraad = $pdo->prepare('UPDATE gerechten SET voorraad = voorraad - ? WHERE id = ?');

    foreach ($orderLines as $orderLine) {
        $menuItemId = (int) $orderLine['gerecht_id'];
        $quantity = (int) $orderLine['aantal'];

        $stmtGerecht->execute([$menuItemId]);
        $menuItem = $stmtGerecht->fetch();

        if (!$menuItem) {
            throw new InvalidArgumentException("Gerecht met id {$menuItemId} bestaat niet.");
        }

        if ($menuItem['voorraad'] < $quantity) {
            throw new InvalidArgumentException("Onvoldoende voorraad voor '{$menuItem['naam']}' (nog {$menuItem['voorraad']} beschikbaar).");
        }

        // Store the current price so later menu changes do not affect past orders.
        $stmtInsertRegel->execute([$orderId, $menuItemId, $quantity, $menuItem['prijs']]);

        $stmtVerlaagVoorraad->execute([$quantity, $menuItemId]);
    }

    $stmt = $pdo->prepare("UPDATE tafels SET status = 'bezet' WHERE id = ?");
    $stmt->execute([$tableId]);

    $pdo->commit();

    antwoord(['succes' => true, 'bestelling_id' => $orderId]);
} catch (InvalidArgumentException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    antwoord(['succes' => false, 'fout' => $exception->getMessage()], 422);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order creation failed: ' . $exception->getMessage());
    antwoord(['succes' => false, 'fout' => 'Bestelling kon niet worden opgeslagen. Probeer het later opnieuw.'], 500);
}
