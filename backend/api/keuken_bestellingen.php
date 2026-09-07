<?php
/**
 * GET api/keuken_bestellingen.php
 * Geeft alle openstaande orderregels terug voor categorie 'keuken',
 * gegroepeerd per tafel. Wordt elke paar seconden ververst door keuken.html.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$sql = "
    SELECT
        o.id            AS orderregel_id,
        t.naam          AS tafel_naam,
        t.id            AS tafel_id,
        g.naam          AS gerecht_naam,
        o.aantal        AS aantal,
        o.status        AS status,
        o.besteld_op    AS besteld_op
    FROM orderregels o
    JOIN bestellingen b ON b.id = o.bestelling_id
    JOIN tafels t       ON t.id = b.tafel_id
    JOIN gerechten g    ON g.id = o.gerecht_id
    WHERE g.categorie = 'keuken'
      AND b.status = 'open'
      AND o.status != 'geserveerd'
    ORDER BY o.besteld_op ASC
";

$stmt = $pdo->query($sql);
$regels = $stmt->fetchAll();

echo json_encode(['succes' => true, 'orderregels' => $regels]);
