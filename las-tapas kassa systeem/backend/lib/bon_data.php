<?php
/**
 * bon_data.php
 * Retrieves the data needed for a receipt in JSON or PDF form.
 * Shared by bon_gegevens.php and bon_pdf.php to keep this logic in one place.
 */

function bon_data_ophalen(PDO $pdo, int $tafelId, int $bestellingId): ?array
{
    if ($bestellingId > 0) {
        $stmt = $pdo->prepare(
            'SELECT b.id, b.aangemaakt_op, b.status, b.klant_email, b.aantal_personen, t.naam AS tafel_naam
             FROM bestellingen b
             JOIN tafels t ON t.id = b.tafel_id
             WHERE b.id = ?'
        );
        $stmt->execute([$bestellingId]);
    } elseif ($tafelId > 0) {
        $stmt = $pdo->prepare(
            "SELECT b.id, b.aangemaakt_op, b.status, b.klant_email, b.aantal_personen, t.naam AS tafel_naam
             FROM bestellingen b
             JOIN tafels t ON t.id = b.tafel_id
             WHERE b.tafel_id = ? AND b.status = 'open'
             LIMIT 1"
        );
        $stmt->execute([$tafelId]);
    } else {
        return null;
    }

    $bestelling = $stmt->fetch();
    if (!$bestelling) {
        return null;
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

    return [
        'bestelling_id' => $bestelling['id'],
        'tafel_naam'    => $bestelling['tafel_naam'],
        'datum_tijd'    => $bestelling['aangemaakt_op'],
        'status'        => $bestelling['status'],
        'klant_email'      => $bestelling['klant_email'],
        'aantal_personen'  => $bestelling['aantal_personen'],
        'orderregels'   => $regels,
        'totaal'        => round($totaal, 2),
    ];
}
