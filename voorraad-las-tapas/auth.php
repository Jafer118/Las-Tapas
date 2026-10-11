<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
startAppSession();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? null;
if (!is_string($submittedToken) || !hasValidCsrfToken($submittedToken)) {
    header('Location: login.php?error=invalid');
    exit;
}

$usernameInput = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;
if (!is_string($usernameInput) || !is_string($password)) {
    header('Location: login.php?error=invalid');
    exit;
}

$username = trim($usernameInput);
if ($username === '' || strlen($username) > 50 || $password === '') {
    header('Location: login.php?error=invalid');
    exit;
}

try {
    $pdo = database();
    if (isInventoryLoginRateLimited($pdo, $_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
        header('Location: login.php?error=rate-limit');
        exit;
    }

    $statement = $pdo->prepare('SELECT id, gebruikersnaam, naam, wachtwoord_hash FROM gebruikers WHERE gebruikersnaam = :username LIMIT 1');
    $statement->execute(['username' => $username]);
    $user = $statement->fetch();
} catch (PDOException $error) {
    error_log('Login database error: ' . $error->getMessage());
    header('Location: login.php?error=unavailable');
    exit;
}

if (!$user || !password_verify($password, $user['wachtwoord_hash'])) {
    header('Location: login.php?error=1');
    exit;
}

if (!session_regenerate_id(true)) {
    error_log('Failed to regenerate the session ID after login.');
    header('Location: login.php?error=unavailable');
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['gebruikersnaam'];
$_SESSION['name'] = $user['naam'];
header('Location: index.html');
exit;