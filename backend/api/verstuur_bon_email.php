<?php
/**
 * POST api/verstuur_bon_email.php
 * Body: { "bestelling_id": 7, "email": "klant@example.com" }
 *
 * "email" is optioneel: als je hem niet meestuurt, wordt het e-mailadres
 * gebruikt dat bij het bestellen is opgeslagen (klant_email). Geef je wel
 * een e-mailadres mee, dan wordt dat gebruikt (en meteen opgeslagen).
 *
 * Verstuurt écht via SMTP (zie backend/mail_config.php voor de
 * serverinstellingen) — dus vul dat bestand in met jouw eigen
 * mailaccountgegevens voordat je dit test.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/bon_data.php';
require_once __DIR__ . '/../lib/pdf_bon.php';
require_once __DIR__ . '/../lib/smtp_mailer.php';

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

// Mailserverinstellingen inladen
$mailConfigPad = __DIR__ . '/../mail_config.php';
if (!file_exists($mailConfigPad)) {
    http_response_code(500);
    echo json_encode(['succes' => false, 'fout' => 'backend/mail_config.php ontbreekt.']);
    exit;
}
$mailConfig = require $mailConfigPad;

if ($mailConfig['gebruiker'] === 'jouw-adres@gmail.com') {
    http_response_code(500);
    echo json_encode([
        'succes' => false,
        'fout' => 'Mailserver is nog niet ingesteld. Vul je eigen gegevens in bij backend/mail_config.php (zie de instructies bovenin dat bestand).',
    ]);
    exit;
}

$pdfBytes = genereer_bon_pdf($bonData);
$bestandsnaam = 'bon-' . $bonData['bestelling_id'] . '.pdf';

$berichttekst = "Beste gast,\r\n\r\n"
    . "Bedankt voor uw bezoek aan Las Tapas! In de bijlage vindt u uw bonnetje.\r\n\r\n"
    . "Tot ziens!\r\nLas Tapas\r\n";

$resultaat = verstuur_email_smtp(
    $mailConfig,
    $emailAdres,
    'Uw bonnetje van Las Tapas — ' . $bonData['tafel_naam'],
    $berichttekst,
    $bestandsnaam,
    $pdfBytes
);

if ($resultaat['succes']) {
    echo json_encode([
        'succes' => true,
        'bericht' => "E-mail met bonnetje is verstuurd naar {$emailAdres}.",
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'succes' => false,
        'fout' => $resultaat['fout'],
    ]);
}
