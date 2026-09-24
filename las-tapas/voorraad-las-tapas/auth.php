<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$pdo = database();
$statement = $pdo->prepare('SELECT id, gebruikersnaam, naam, wachtwoord_hash FROM gebruikers WHERE gebruikersnaam = :username LIMIT 1');
$statement->execute(['username' => $username]);
$user = $statement->fetch();

if (!$user || !password_verify($password, $user['wachtwoord_hash'])) {
    header('Location: login.php?error=1');
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['username'] = $user['gebruikersnaam'];
$_SESSION['name'] = $user['naam'];
header('Location: index.html');
exit;