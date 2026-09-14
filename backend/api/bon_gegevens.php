<?php
/**
 * GET api/bon_gegevens.php?tafel_id=10
 * GET api/bon_gegevens.php?bestelling_id=7
 *
 * Geeft alle gegevens terug die nodig zijn om een bonnetje te tonen/printen:
 * tafelnaam, datum/tijd, orderregels en totaalbedrag.
 *
 * - Met tafel_id: pakt de op dit moment ÓPEN bestelling van die tafel
 *   (handig om een tussentijds bonnetje te bekijken tijdens het bezoek).
 * - Met bestelling_id: pakt een specifieke bestelling, ongeacht status
 *   (handig om ná het afrekenen alsnog het bonnetje te printen).
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$tafelId      = isset($_GET['tafel_id']) ? (int) $_GET['tafel_id'] : 0;
$bestellingId = isset($_GET['bestelling_id']) ? (int) $_GET['bestelling_id'] : 0;

if ($tafelId <= 0 && $bestellingId <= 0) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => 'tafel_id of bestelling_id is verplicht.']);
    exit;
}

if ($bestellingId > 0) {
    $stmt = $pdo->prepare(
        'SELECT b.id, b.aangemaakt_op, b.status, t.naam AS tafel_naam
         FROM bestellingen b
         JOIN tafels t ON t.id = b.tafel_id
         WHERE b.id = ?'
    );
    $stmt->execute([$bestellingId]);
} else {
    $stmt = $pdo->prepare(
        "SELECT b.id, b.aangemaakt_op, b.status, t.naam AS tafel_naam
         FROM bestellingen b
         JOIN tafels t ON t.id = b.tafel_id
         WHERE b.tafel_id = ? AND b.status = 'open'
         LIMIT 1"
    );
    $stmt->execute([$tafelId]);
}

$bestelling = $stmt->fetch();

if (!$bestelling) {
    http_response_code(404);
    echo json_encode(['succes' => false, 'fout' => 'Geen (openstaande) bestelling gevonden.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT g.naam AS gerecht_naam, o.aantal, o.prijs_per_stuk,
            (o.aantal * o.prijs_per_stuk) AS subtotaal
     FROM orderregels o
     JOIN gerechten g ON g.id = o.gerecht_id
     WHERE o.bestelling_id = ?
     ORDER BY o.besteld_op ASC'
);
$stmt->execute([$bestelling['id']]);
$regels = $stmt->fetchAll();

$totaal = 0;
foreach ($regels as $regel) {
    $totaal += $regel['subtotaal'];
}

echo json_encode([
    'succes'        => true,
    'bestelling_id' => $bestelling['id'],
    'tafel_naam'    => $bestelling['tafel_naam'],
    'datum_tijd'    => $bestelling['aangemaakt_op'],
    'status'        => $bestelling['status'],
    'orderregels'   => $regels,
    'totaal'        => round($totaal, 2),
]);
