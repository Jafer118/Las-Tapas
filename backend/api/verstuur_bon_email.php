<?php
/**
 * POST api/verstuur_bon_email.php
 * Body: { "bestelling_id": 7, "email": "klant@example.com" }
 *
 * "email" is optioneel: als je hem niet meestuurt, wordt het e-mailadres
 * gebruikt dat bij het bestellen is opgeslagen (klant_email). Geef je wel
 * een e-mailadres mee, dan wordt dat gebruikt (en niet opgeslagen).
 *
 * BELANGRIJK — lees dit als de e-mail niet aankomt:
 * Dit script gebruikt PHP's ingebouwde mail()-functie. Op een kale
 * XAMPP/MAMP-installatie is er standaard GEEN mailserver geconfigureerd,
 * dus mail() geeft dan wel "verstuurd" terug, maar de e-mail wordt
 * nergens echt afgeleverd. Voor een werkende opdracht/demo is dat vaak
 * geen probleem (de code is functioneel correct), maar voor ECHTE
 * verzending moet je op je server/lokale machine een mailserver of
 * SMTP-relay instellen (zie de README voor opties).
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/bon_data.php';
require_once __DIR__ . '/../lib/pdf_bon.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['bestelling_id'])) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => 'bestelling_id is verplicht.']);
    exit;
}

$bestellingId = (int) $input['bestelling_id'];
$emailOverride = trim($input['email'] ?? '');

$bonData = bon_data_ophalen($pdo, 0, $bestellingId);

if (!$bonData) {
    http_response_code(404);
    echo json_encode(['succes' => false, 'fout' => 'Bestelling niet gevonden.']);
    exit;
}

$emailAdres = $emailOverride !== '' ? $emailOverride : ($bonData['klant_email'] ?? '');

if ($emailAdres === '' || !filter_var($emailAdres, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'fout' => 'Geen geldig e-mailadres opgegeven of opgeslagen bij deze bestelling.']);
    exit;
}

// Als er een override-adres is meegegeven, direct ook opslaan bij de bestelling
if ($emailOverride !== '') {
    $stmt = $pdo->prepare('UPDATE bestellingen SET klant_email = ? WHERE id = ?');
    $stmt->execute([$emailOverride, $bestellingId]);
}

$pdfBytes = genereer_bon_pdf($bonData);
$bestandsnaam = 'bon-' . $bonData['bestelling_id'] . '.pdf';

// ---------- E-mail met PDF-bijlage opbouwen (multipart/mixed) ----------
$afzender = 'Las Tapas <noreply@las-tapas.local>';
$onderwerp = 'Uw bonnetje van Las Tapas — ' . $bonData['tafel_naam'];
$grens = 'las-tapas-bon-' . md5((string) time());

$berichttekst = "Beste gast,\r\n\r\n"
    . "Bedankt voor uw bezoek aan Las Tapas! In de bijlage vindt u uw bonnetje.\r\n\r\n"
    . "Tot ziens!\r\nLas Tapas\r\n";

$headers  = "From: {$afzender}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$grens}\"\r\n";

$body  = "--{$grens}\r\n";
$body .= "Content-Type: text/plain; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= $berichttekst . "\r\n";

$body .= "--{$grens}\r\n";
$body .= "Content-Type: application/pdf; name=\"{$bestandsnaam}\"\r\n";
$body .= "Content-Transfer-Encoding: base64\r\n";
$body .= "Content-Disposition: attachment; filename=\"{$bestandsnaam}\"\r\n\r\n";
$body .= chunk_split(base64_encode($pdfBytes)) . "\r\n";
$body .= "--{$grens}--";

$verzonden = @mail($emailAdres, $onderwerp, $body, $headers);

if ($verzonden) {
    echo json_encode([
        'succes' => true,
        'bericht' => "E-mail aangeboden voor verzending naar {$emailAdres}. "
            . "Let op: of hij écht aankomt, hangt af van de mailserver-configuratie van je omgeving (zie README).",
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'succes' => false,
        'fout' => 'PHP kon de e-mail niet aanbieden voor verzending. Waarschijnlijk is er geen mailserver/SMTP geconfigureerd op deze omgeving (zie README voor hoe je dit instelt).',
    ]);
}
