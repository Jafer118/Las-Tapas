<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/auth.php';

$requestData = decodeJsonRequestBody();
$action = $_GET['actie'] ?? $requestData['actie'] ?? '';

if ($action === 'me') {
    requireHttpMethod('GET');
    sendJsonResponse([
        'ingelogd' => (bool) getAuthenticatedUser(),
        'gebruiker' => getAuthenticatedUser(),
        'csrf_token' => getCsrfToken(),
    ]);
}

if ($action === 'logout') {
    requireHttpMethod('POST');
    requireAuthenticatedUser();
    requireValidCsrfToken();

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $cookieParams = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
    }
    session_destroy();
    sendJsonResponse(['succes' => true]);
}

if ($action === 'login') {
    requireHttpMethod('POST');
    requireValidCsrfToken();

    $emailInput = $requestData['email'] ?? null;
    $passwordInput = $requestData['wachtwoord'] ?? null;
    if (!is_string($emailInput) || !is_string($passwordInput)) {
        sendJsonResponse(['succes' => false, 'fout' => 'Vul een geldig e-mailadres en wachtwoord in.'], 422);
    }

    $email = strtolower(trim($emailInput));
    $password = $passwordInput;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        sendJsonResponse(['succes' => false, 'fout' => 'Vul een geldig e-mailadres en wachtwoord in.'], 422);
    }
    enforceRateLimit($pdo, 'login', 10, 15);

    $statement = $pdo->prepare('SELECT id, email, naam, rol, wachtwoord_hash FROM kassa_gebruikers WHERE email = ? AND actief = 1 LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch();
    if (!$user || !password_verify($password, $user['wachtwoord_hash'])) {
        sendJsonResponse(['succes' => false, 'fout' => 'E-mailadres of wachtwoord is onjuist.'], 401);
    }

    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    $_SESSION['gebruiker'] = [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'naam' => $user['naam'],
        'rol' => $user['rol'],
    ];
    sendJsonResponse(['succes' => true, 'gebruiker' => $_SESSION['gebruiker']]);
}

if ($action === 'reset_aanvragen') {
    requireHttpMethod('POST');
    requireValidCsrfToken();

    $emailInput = $requestData['email'] ?? null;
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
    enforceRateLimit($pdo, 'password_reset_request', 5, 60);
    $statement = $pdo->prepare('SELECT id FROM kassa_gebruikers WHERE email = ? AND actief = 1 LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch();
    $result = ['succes' => true, 'bericht' => 'Als dit e-mailadres bestaat, is een resetlink aangemaakt.'];

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $statement = $pdo->prepare('INSERT INTO kassa_wachtwoord_resets (gebruiker_id, token_hash, verloopt_op) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE))');
        $statement->execute([$user['id'], hash('sha256', $token)]);
        if (strtolower(getenv('APP_ENV') ?: 'production') === 'development') {
            $result['reset_link'] = 'resetten.html?token=' . urlencode($token);
        }
    }
    sendJsonResponse($result);
}

if ($action === 'wachtwoord_resetten') {
    requireHttpMethod('POST');
    requireValidCsrfToken();
    enforceRateLimit($pdo, 'password_reset_attempt', 5, 60);

    $tokenInput = $requestData['token'] ?? null;
    $passwordInput = $requestData['nieuw_wachtwoord'] ?? null;
    if (!is_string($tokenInput) || !is_string($passwordInput)) {
        sendJsonResponse(['succes' => false, 'fout' => 'De resetgegevens zijn ongeldig.'], 422);
    }

    $token = trim($tokenInput);
    $newPassword = $passwordInput;
    if (strlen($newPassword) < 10) {
        sendJsonResponse(['succes' => false, 'fout' => 'Gebruik minimaal 10 tekens voor het nieuwe wachtwoord.'], 422);
    }

    if (!preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
        sendJsonResponse(['succes' => false, 'fout' => 'Deze resetlink is ongeldig of verlopen.'], 400);
    }

    $pdo->beginTransaction();
    $statement = $pdo->prepare('SELECT id, gebruiker_id FROM kassa_wachtwoord_resets WHERE token_hash = ? AND gebruikt_op IS NULL AND verloopt_op > NOW() LIMIT 1 FOR UPDATE');
    $statement->execute([hash('sha256', $token)]);
    $resetRecord = $statement->fetch();
    if (!$resetRecord) {
        $pdo->rollBack();
        sendJsonResponse(['succes' => false, 'fout' => 'Deze resetlink is ongeldig of verlopen.'], 400);
    }

    $statement = $pdo->prepare('UPDATE kassa_gebruikers SET wachtwoord_hash = ? WHERE id = ?');
    $statement->execute([password_hash($newPassword, PASSWORD_DEFAULT), $resetRecord['gebruiker_id']]);
    $statement = $pdo->prepare('UPDATE kassa_wachtwoord_resets SET gebruikt_op = NOW() WHERE id = ?');
    $statement->execute([$resetRecord['id']]);
    $pdo->commit();
    sendJsonResponse(['succes' => true, 'bericht' => 'Je wachtwoord is gewijzigd. Je kunt nu inloggen.']);
}

sendJsonResponse(['succes' => false, 'fout' => 'Onbekende actie.'], 400);
