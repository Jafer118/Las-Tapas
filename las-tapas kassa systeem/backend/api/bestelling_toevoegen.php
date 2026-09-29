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
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['tafel_id']) || empty($input['regels']) || !is_array($input['regels'])) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => 'Ongeldige invoer: tafel_id en regels zijn verplicht.']);
    exit;
}

$tafelId = (int) $input['tafel_id'];
$regels  = $input['regels'];
$klantEmail = isset($input['klant_email']) ? trim($input['klant_email']) : '';
$aantalPersonen = isset($input['aantal_personen']) && $input['aantal_personen'] !== ''
    ? (int) $input['aantal_personen']
    : null;

if ($klantEmail !== '' && !filter_var($klantEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => 'Het opgegeven e-mailadres is ongeldig.']);
    exit;
}

if ($aantalPersonen !== null && $aantalPersonen <= 0) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => 'Aantal personen moet minimaal 1 zijn.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Check that the table exists and retrieve its capacity.
    $stmt = $pdo->prepare('SELECT id, naam, capaciteit FROM tafels WHERE id = ?');
    $stmt->execute([$tafelId]);
    $tafel = $stmt->fetch();
    if (!$tafel) {
        throw new Exception('Tafel bestaat niet.');
    }

    if ($aantalPersonen !== null && $aantalPersonen > $tafel['capaciteit']) {
        throw new Exception(
            "{$tafel['naam']} heeft plaats voor maximaal {$tafel['capaciteit']} personen "
            . "(opgegeven: {$aantalPersonen})."
        );
    }

    // 2. Find or create an open order for this table.
    $stmt = $pdo->prepare("SELECT id FROM bestellingen WHERE tafel_id = ? AND status = 'open' LIMIT 1");
    $stmt->execute([$tafelId]);
    $bestelling = $stmt->fetch();

    if ($bestelling) {
        $bestellingId = $bestelling['id'];
        // Update the order if an email address or party size is provided later.
        if ($klantEmail !== '') {
            $stmt = $pdo->prepare('UPDATE bestellingen SET klant_email = ? WHERE id = ?');
            $stmt->execute([$klantEmail, $bestellingId]);
        }
        if ($aantalPersonen !== null) {
            $stmt = $pdo->prepare('UPDATE bestellingen SET aantal_personen = ? WHERE id = ?');
            $stmt->execute([$aantalPersonen, $bestellingId]);
        }
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO bestellingen (tafel_id, klant_email, aantal_personen, status) VALUES (?, ?, ?, "open")'
        );
        $stmt->execute([$tafelId, $klantEmail !== '' ? $klantEmail : null, $aantalPersonen]);
        $bestellingId = $pdo->lastInsertId();
    }

    // 3. Check stock, add each order line, and deduct stock.
    $stmtGerecht = $pdo->prepare('SELECT naam, prijs, voorraad FROM gerechten WHERE id = ? FOR UPDATE');
    $stmtInsertRegel = $pdo->prepare(
        'INSERT INTO orderregels (bestelling_id, gerecht_id, aantal, prijs_per_stuk, status)
         VALUES (?, ?, ?, ?, "besteld")'
    );
    $stmtVerlaagVoorraad = $pdo->prepare('UPDATE gerechten SET voorraad = voorraad - ? WHERE id = ?');

    foreach ($regels as $regel) {
        $gerechtId = (int) ($regel['gerecht_id'] ?? 0);
        $aantal    = (int) ($regel['aantal'] ?? 0);

        if ($gerechtId <= 0 || $aantal <= 0) {
            throw new Exception('Ongeldige regel: gerecht_id en aantal moeten positief zijn.');
        }

        $stmtGerecht->execute([$gerechtId]);
        $gerecht = $stmtGerecht->fetch();

        if (!$gerecht) {
            throw new Exception("Gerecht met id {$gerechtId} bestaat niet.");
        }

        if ($gerecht['voorraad'] < $aantal) {
            throw new Exception("Onvoldoende voorraad voor '{$gerecht['naam']}' (nog {$gerecht['voorraad']} beschikbaar).");
        }

        // Store the current price so later menu changes do not affect past orders.
        $stmtInsertRegel->execute([$bestellingId, $gerechtId, $aantal, $gerecht['prijs']]);

        $stmtVerlaagVoorraad->execute([$aantal, $gerechtId]);
    }

    $stmt = $pdo->prepare("UPDATE tafels SET status = 'bezet' WHERE id = ?");
    $stmt->execute([$tafelId]);

    $pdo->commit();

    echo json_encode(['succes' => true, 'bestelling_id' => $bestellingId]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => $e->getMessage()]);
}
