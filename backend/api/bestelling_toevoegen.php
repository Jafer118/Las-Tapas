<?php
/**
 * POST api/bestelling_toevoegen.php
 *
 * Verwacht JSON body:
 * {
 *   "tafel_id": 10,
 *   "regels": [
 *     { "gerecht_id": 1, "aantal": 2 },
 *     { "gerecht_id": 7, "aantal": 4 }
 *   ]
 * }
 *
 * Doet het volgende (in 1 transactie, dus alles-of-niets):
 *  1. Zoekt een openstaande bestelling voor deze tafel, of maakt er een aan.
 *  2. Controleert per gerecht of er genoeg voorraad is.
 *  3. Voegt orderregels toe.
 *  4. Verlaagt de voorraad van elk gerecht.
 *  5. Zet de tafelstatus op 'bezet'.
 */

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

try {
    $pdo->beginTransaction();

    // 1. Controleer of de tafel bestaat
    $stmt = $pdo->prepare('SELECT id FROM tafels WHERE id = ?');
    $stmt->execute([$tafelId]);
    if (!$stmt->fetch()) {
        throw new Exception('Tafel bestaat niet.');
    }

    // 2. Zoek open bestelling voor deze tafel, of maak nieuwe aan
    $stmt = $pdo->prepare("SELECT id FROM bestellingen WHERE tafel_id = ? AND status = 'open' LIMIT 1");
    $stmt->execute([$tafelId]);
    $bestelling = $stmt->fetch();

    if ($bestelling) {
        $bestellingId = $bestelling['id'];
    } else {
        $stmt = $pdo->prepare('INSERT INTO bestellingen (tafel_id, status) VALUES (?, "open")');
        $stmt->execute([$tafelId]);
        $bestellingId = $pdo->lastInsertId();
    }

    // 3. Verwerk elke regel: check voorraad, voeg toe, verlaag voorraad
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

        // Orderregel toevoegen (prijs wordt "bevroren" op het moment van bestellen)
        $stmtInsertRegel->execute([$bestellingId, $gerechtId, $aantal, $gerecht['prijs']]);

        // Voorraad automatisch verlagen
        $stmtVerlaagVoorraad->execute([$aantal, $gerechtId]);
    }

    // 4. Tafel op 'bezet' zetten
    $stmt = $pdo->prepare("UPDATE tafels SET status = 'bezet' WHERE id = ?");
    $stmt->execute([$tafelId]);

    $pdo->commit();

    echo json_encode(['succes' => true, 'bestelling_id' => $bestellingId]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => $e->getMessage()]);
}
