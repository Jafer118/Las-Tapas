<?php
/**
 * GET api/rekening.php?tafel_id=10
 * Returns the open order lines and total for one table.
 */

require_once __DIR__ . '/../lib/auth.php';
requireAuthenticatedUser();
requireHttpMethod('GET');
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$tableId = parsePositiveInteger($_GET['tafel_id'] ?? null);

if ($tableId === null) {
    sendJsonResponse(['succes' => false, 'fout' => 'Een geldige tafel_id is verplicht.'], 422);
}

$statement = $pdo->prepare("SELECT id, aangemaakt_op, aantal_personen FROM bestellingen WHERE tafel_id = ? AND status = 'open' LIMIT 1");
$statement->execute([$tableId]);
$order = $statement->fetch();

if (!$order) {
    echo json_encode([
        'succes'      => true,
        'tafel_id'    => $tableId,
        'bestelling'  => null,
        'orderregels' => [],
        'totaal'      => 0,
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

$total = 0;
foreach ($lines as $row) {
    $total += $row['subtotaal'];
}

echo json_encode([
    'succes'          => true,
    'tafel_id'        => $tableId,
    'bestelling_id'   => $order['id'],
    'aantal_personen' => $order['aantal_personen'],
    'orderregels'     => $lines,
    'totaal'          => round($total, 2),
]);
