<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/auth.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$actie = $_GET['actie'] ?? $input['actie'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $actie === 'me') {
    antwoord(['ingelogd' => (bool) ingelogdeGebruiker(), 'gebruiker' => ingelogdeGebruiker()]);
}

if ($actie === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    antwoord(['succes' => true]);
}

if ($actie === 'login') {
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $wachtwoord = (string) ($input['wachtwoord'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $wachtwoord === '') {
        antwoord(['succes' => false, 'fout' => 'Vul een geldig e-mailadres en wachtwoord in.'], 422);
    }

    $stmt = $pdo->prepare('SELECT id, email, naam, rol, wachtwoord_hash FROM gebruikers WHERE email = ? AND actief = 1 LIMIT 1');
    $stmt->execute([$email]);
    $gebruiker = $stmt->fetch();
    if (!$gebruiker || !password_verify($wachtwoord, $gebruiker['wachtwoord_hash'])) {
        antwoord(['succes' => false, 'fout' => 'E-mailadres of wachtwoord is onjuist.'], 401);
    }

    session_regenerate_id(true);
    $_SESSION['gebruiker'] = [
        'id' => (int) $gebruiker['id'],
        'email' => $gebruiker['email'],
        'naam' => $gebruiker['naam'],
        'rol' => $gebruiker['rol'],
    ];
    antwoord(['succes' => true, 'gebruiker' => $_SESSION['gebruiker']]);
}

if ($actie === 'reset_aanvragen') {
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $stmt = $pdo->prepare('SELECT id FROM gebruikers WHERE email = ? AND actief = 1 LIMIT 1');
    $stmt->execute([$email]);
    $gebruiker = $stmt->fetch();
    $resultaat = ['succes' => true, 'bericht' => 'Als dit e-mailadres bestaat, is een resetlink aangemaakt.'];

    if ($gebruiker) {
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare('INSERT INTO wachtwoord_resets (gebruiker_id, token_hash, verloopt_op) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))');
        $stmt->execute([$gebruiker['id'], hash('sha256', $token)]);
        $resultaat['reset_link'] = 'resetten.html?token=' . urlencode($token);
    }
    antwoord($resultaat);
}

if ($actie === 'wachtwoord_resetten') {
    $token = trim((string) ($input['token'] ?? ''));
    $nieuw = (string) ($input['nieuw_wachtwoord'] ?? '');
    if (strlen($nieuw) < 10) {
        antwoord(['succes' => false, 'fout' => 'Gebruik minimaal 10 tekens voor het nieuwe wachtwoord.'], 422);
    }

    $stmt = $pdo->prepare('SELECT id, gebruiker_id FROM wachtwoord_resets WHERE token_hash = ? AND gebruikt_op IS NULL AND verloopt_op > NOW() LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $reset = $stmt->fetch();
    if (!$reset) {
        antwoord(['succes' => false, 'fout' => 'Deze resetlink is ongeldig of verlopen.'], 400);
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE gebruikers SET wachtwoord_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($nieuw, PASSWORD_DEFAULT), $reset['gebruiker_id']]);
    $stmt = $pdo->prepare('UPDATE wachtwoord_resets SET gebruikt_op = NOW() WHERE id = ?');
    $stmt->execute([$reset['id']]);
    $pdo->commit();
    antwoord(['succes' => true, 'bericht' => 'Je wachtwoord is gewijzigd. Je kunt nu inloggen.']);
}

antwoord(['succes' => false, 'fout' => 'Onbekende actie.'], 400);
