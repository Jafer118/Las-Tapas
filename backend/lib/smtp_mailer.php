<?php
/**
 * smtp_mailer.php
 *
 * Kleine, zelfgebouwde SMTP-client — zonder externe library (geen
 * PHPMailer/Composer nodig). Praat rechtstreeks met de mailserver via een
 * TCP-socket: EHLO -> STARTTLS -> AUTH LOGIN -> MAIL FROM/RCPT TO/DATA.
 *
 * Nodig omdat PHP's ingebouwde mail()-functie op Windows geen
 * geauthenticeerde verbinding (zoals bij Gmail) ondersteunt.
 */

function smtp_regel_lezen($socket): string
{
    $data = '';
    while (($regel = fgets($socket, 515)) !== false) {
        $data .= $regel;
        // Een SMTP-antwoord kan uit meerdere regels bestaan; de LAATSTE
        // regel heeft een spatie op positie 3 (bv. "250 OK"), tussenregels
        // hebben daar een streepje (bv. "250-STARTTLS").
        if (strlen($regel) >= 4 && $regel[3] === ' ') {
            break;
        }
    }
    return $data;
}

function smtp_stuur(&$socket, string $commando): void
{
    fwrite($socket, $commando . "\r\n");
}

/**
 * Verstuurt een e-mail met eventueel één PDF-bijlage via SMTP.
 *
 * @param array $config Zie mail_config.php (host, port, tls, gebruiker, wachtwoord, van_naam, van_email)
 * @return array ['succes' => bool, 'fout' => string|null]
 */
function verstuur_email_smtp(
    array $config,
    string $naarEmail,
    string $onderwerp,
    string $tekstBody,
    ?string $bijlageNaam = null,
    ?string $bijlageBytes = null
): array {
    $socket = @fsockopen($config['host'], $config['port'], $errno, $errstr, 15);
    if (!$socket) {
        return ['succes' => false, 'fout' => "Kon geen verbinding maken met {$config['host']}:{$config['port']} — {$errstr}"];
    }

    smtp_regel_lezen($socket); // welkomstbericht van de server

    smtp_stuur($socket, 'EHLO las-tapas.local');
    smtp_regel_lezen($socket);

    if (!empty($config['tls'])) {
        smtp_stuur($socket, 'STARTTLS');
        $resp = smtp_regel_lezen($socket);
        if (substr($resp, 0, 3) !== '220') {
            fclose($socket);
            return ['succes' => false, 'fout' => 'Mailserver ondersteunt geen STARTTLS, of weigerde de opdracht.'];
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return ['succes' => false, 'fout' => 'Opzetten van de beveiligde (TLS) verbinding is mislukt.'];
        }
        smtp_stuur($socket, 'EHLO las-tapas.local');
        smtp_regel_lezen($socket);
    }

    smtp_stuur($socket, 'AUTH LOGIN');
    smtp_regel_lezen($socket);
    smtp_stuur($socket, base64_encode($config['gebruiker']));
    smtp_regel_lezen($socket);
    smtp_stuur($socket, base64_encode($config['wachtwoord']));
    $authResp = smtp_regel_lezen($socket);

    if (substr($authResp, 0, 3) !== '235') {
        fclose($socket);
        return [
            'succes' => false,
            'fout' => 'Inloggen bij de mailserver is mislukt. Controleer gebruiker/wachtwoord (app-wachtwoord) in backend/mail_config.php. Serverantwoord: ' . trim($authResp),
        ];
    }

    smtp_stuur($socket, "MAIL FROM: <{$config['van_email']}>");
    smtp_regel_lezen($socket);

    smtp_stuur($socket, "RCPT TO: <{$naarEmail}>");
    $rcptResp = smtp_regel_lezen($socket);
    if (substr($rcptResp, 0, 1) !== '2') {
        fclose($socket);
        return ['succes' => false, 'fout' => 'De mailserver weigerde het ontvangende e-mailadres: ' . trim($rcptResp)];
    }

    smtp_stuur($socket, 'DATA');
    smtp_regel_lezen($socket);

    $grens = 'las-tapas-' . md5(uniqid((string) mt_rand(), true));

    $headers  = "From: {$config['van_naam']} <{$config['van_email']}>\r\n";
    $headers .= "To: <{$naarEmail}>\r\n";
    $headers .= "Subject: {$onderwerp}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$grens}\"\r\n";

    $body  = "--{$grens}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $tekstBody . "\r\n\r\n";

    if ($bijlageNaam !== null && $bijlageBytes !== null) {
        $body .= "--{$grens}\r\n";
        $body .= "Content-Type: application/pdf; name=\"{$bijlageNaam}\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$bijlageNaam}\"\r\n\r\n";
        $body .= chunk_split(base64_encode($bijlageBytes)) . "\r\n";
    }

    $body .= "--{$grens}--\r\n";

    $volledigBericht = $headers . "\r\n" . $body;

    // "Dot-stuffing": regels die met een punt beginnen moeten volgens de
    // SMTP-spec verdubbeld worden, anders denkt de server dat het bericht
    // daar eindigt.
    $volledigBericht = preg_replace('/^\./m', '..', $volledigBericht);

    smtp_stuur($socket, $volledigBericht . "\r\n.");
    $dataResp = smtp_regel_lezen($socket);

    smtp_stuur($socket, 'QUIT');
    fclose($socket);

    if (substr($dataResp, 0, 1) !== '2') {
        return ['succes' => false, 'fout' => 'De mailserver weigerde het bericht: ' . trim($dataResp)];
    }

    return ['succes' => true, 'fout' => null];
}
