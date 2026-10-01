<?php
/**
 * bon_data.php
 * Retrieves the data needed for a receipt in JSON or PDF form.
 * Shared by bon_gegevens.php and bon_pdf.php to keep this logic in one place.
 */

function getReceiptData(PDO $pdo, int $tableId, int $orderId): ?array
{
    if ($orderId > 0) {
        $statement = $pdo->prepare(
            'SELECT b.id, b.aangemaakt_op, b.status, b.klant_email, b.aantal_personen, t.naam AS tafel_naam
             FROM bestellingen b
             JOIN tafels t ON t.id = b.tafel_id
             WHERE b.id = ?'
        );
        $statement->execute([$orderId]);
    } elseif ($tableId > 0) {
        $statement = $pdo->prepare(
            "SELECT b.id, b.aangemaakt_op, b.status, b.klant_email, b.aantal_personen, t.naam AS tafel_naam
             FROM bestellingen b
             JOIN tafels t ON t.id = b.tafel_id
             WHERE b.tafel_id = ? AND b.status = 'open'
             LIMIT 1"
        );
        $statement->execute([$tableId]);
    } else {
        return null;
    }

    $order = $statement->fetch();
    if (!$order) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT g.naam AS gerecht_naam, o.aantal, o.prijs_per_stuk,
                (o.aantal * o.prijs_per_stuk) AS subtotaal
         FROM orderregels o
         JOIN gerechten g ON g.id = o.gerecht_id
         WHERE o.bestelling_id = ?
         ORDER BY o.besteld_op ASC'
    );
    $statement->execute([$order['id']]);
    $orderLines = $statement->fetchAll();

    $total = 0;
    foreach ($orderLines as $orderLine) {
        $total += $orderLine['subtotaal'];
    }

    return [
        'bestelling_id' => $order['id'],
        'tafel_naam' => $order['tafel_naam'],
        'datum_tijd' => $order['aangemaakt_op'],
        'status' => $order['status'],
        'klant_email' => $order['klant_email'],
        'aantal_personen' => $order['aantal_personen'],
        'orderregels' => $orderLines,
        'totaal' => round($total, 2),
    ];
}
