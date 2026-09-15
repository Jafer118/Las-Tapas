<?php
/**
 * mail_config.php
 * Vul hier je eigen SMTP-gegevens in zodat verstuur_bon_email.php écht
 * e-mails kan versturen.
 *
 * VOORBEELD MET GMAIL:
 * 1. Ga naar https://myaccount.google.com/apppasswords (vereist dat
 *    2-staps-verificatie aanstaat op je Google-account).
 * 2. Maak een nieuw "app-wachtwoord" aan (kies bv. "Mail" als app).
 * 3. Kopieer het gegenereerde wachtwoord (16 tekens, met spaties) hieronder
 *    bij 'wachtwoord'. Gebruik NIET je normale Google-wachtwoord — dat
 *    werkt niet en is bovendien onveilig om in code te zetten.
 * 4. Vul bij 'gebruiker' en 'van_email' je eigen Gmail-adres in.
 */

return [
    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'tls'        => true,
    'gebruiker'  => 'jouw-adres@gmail.com',      // <-- aanpassen
    'wachtwoord' => 'xxxx xxxx xxxx xxxx',        // <-- app-wachtwoord invullen
    'van_naam'   => 'Las Tapas',
    'van_email'  => 'jouw-adres@gmail.com',      // <-- meestal gelijk aan 'gebruiker'
];
