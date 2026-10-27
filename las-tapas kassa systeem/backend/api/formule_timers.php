<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
requireAuthenticatedUser();
requireHttpMethod('GET');
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$sql = "
    SELECT
        b.id AS bestelling_id,
        t.naam AS tafel_naam,
        b.formule_naam AS formule_naam,
        UNIX_TIMESTAMP(b.aangemaakt_op) +
            CASE b.formule_code
                WHEN '2-uur' THEN 7200
                WHEN '3-uur' THEN 10800
                WHEN '3-uur-drinken' THEN 10800
            END AS eindigt_op
    FROM bestellingen b
    JOIN tafels t ON t.id = b.tafel_id
    WHERE b.status = 'open'
      AND b.formule_code IN ('2-uur', '3-uur', '3-uur-drinken')
    ORDER BY b.aangemaakt_op ASC
";

$statement = $pdo->query($sql);
$timers = $statement->fetchAll();

echo json_encode(['succes' => true, 'timers' => $timers]);
