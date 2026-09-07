<?php
/**
 * GET api/rekening.php?tafel_id=10
 * Geeft de openstaande orderregels + het totaalbedrag terug voor 1 tafel.
 * Wordt gebruikt door de kassapagina.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$tafelId = isset($_GET['tafel_id']) ? (int) $_GET['tafel_id'] : 0;

if ($tafelId <= 0) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => 'tafel_id is verplicht.']);
    exit;
}

// Open bestelling zoeken
$stmt = $pdo->prepare("SELECT id, aangemaakt_op FROM bestellingen WHERE tafel_id = ? AND status = 'open' LIMIT 1");
$stmt->execute([$tafelId]);
$bestelling = $stmt->fetch();

if (!$bestelling) {
    echo json_encode([
        'succes'      => true,
        'tafel_id'    => $tafelId,
        'bestelling'  => null,
        'orderregels' => [],
        'totaal'      => 0,
    ]);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT g.naam AS gerecht_naam, o.aantal, o.prijs_per_stuk,
            (o.aantal * o.prijs_per_stuk) AS subtotaal, o.status
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
    'succes'       => true,
    'tafel_id'     => $tafelId,
    'bestelling_id'=> $bestelling['id'],
    'orderregels'  => $regels,
    'totaal'       => round($totaal, 2),
]);
