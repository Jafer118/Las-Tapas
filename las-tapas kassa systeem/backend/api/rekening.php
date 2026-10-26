<?php
declare(strict_types=1);

/**
 * GET api/rekening.php?tafel_id=10
 * Returns open order lines, dining formula, and total for one table.
 */

require_once __DIR__ . '/../lib/auth.php';
requireAuthenticatedUser();
requireHttpMethod('GET');
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/formules.php';

$tableId = parsePositiveInteger($_GET['tafel_id'] ?? null);

if ($tableId === null) {
    sendJsonResponse(['succes' => false, 'fout' => 'Een geldige tafel_id is verplicht.'], 422);
}

$statement = $pdo->prepare(
    "SELECT id, aangemaakt_op, aantal_personen, formule_code, formule_naam, formule_prijs
     FROM bestellingen WHERE tafel_id = ? AND status = 'open' LIMIT 1"
);
$statement->execute([$tableId]);
$order = $statement->fetch();

if (!$order) {
    echo json_encode([
        'succes'      => true,
        'tafel_id'    => $tableId,
        'bestelling'  => null,
        'orderregels' => [],
        'totaal'      => 0,
        'formule_subtotaal' => 0,
    ]);
    exit;
}

$statement = $pdo->prepare(
    'SELECT g.naam AS gerecht_naam, o.aantal, o.prijs_per_stuk,
            (o.aantal * o.prijs_per_stuk) AS subtotaal, o.status
     FROM orderregels o
     JOIN gerechten g ON g.id = o.gerecht_id
     WHERE o.bestelling_id = ?
     ORDER BY o.besteld_op ASC'
);
$statement->execute([$order['id']]);
$lines = $statement->fetchAll();

$formulaTotal = calculateDiningFormulaTotal(
    $order['formule_code'],
    $order['aantal_personen'] !== null ? (int) $order['aantal_personen'] : null,
    $order['formule_prijs'] !== null ? (float) $order['formule_prijs'] : null
);
$total = calculateOrderTotal(
    $lines,
    $order['formule_code'],
    $order['aantal_personen'] !== null ? (int) $order['aantal_personen'] : null,
    $order['formule_prijs'] !== null ? (float) $order['formule_prijs'] : null
);

echo json_encode([
    'succes'          => true,
    'tafel_id'        => $tableId,
    'bestelling_id'   => $order['id'],
    'aantal_personen' => $order['aantal_personen'],
    'formule_code'    => $order['formule_code'],
    'formule_naam'    => $order['formule_naam'],
    'formule_prijs'   => $order['formule_prijs'],
    'formule_subtotaal' => $formulaTotal,
    'orderregels'     => $lines,
    'totaal'          => $total,
]);
