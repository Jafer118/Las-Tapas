<?php
declare(strict_types=1);
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}
$hasError = isset($_GET['error']);
?><!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen | Las Tapas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="login-brand"><span class="brand-mark">LT</span><span><strong>Las Tapas</strong><small>Casa de sabores</small></span></div>
        <p class="eyebrow">VOORRAADBEHEER</p>
        <h1>Welkom terug.</h1>
        <p class="login-intro">Log in om de actuele voorraad van de zaak te bekijken.</p>
        <?php if ($hasError): ?><div class="login-error">Onjuiste gebruikersnaam of wachtwoord.</div><?php endif; ?>
        <form class="login-form" action="auth.php" method="post">
            <label>Gebruikersnaam<input name="username" autocomplete="username" required autofocus placeholder="sofia of marcus"></label>
            <label>Wachtwoord<input name="password" type="password" autocomplete="current-password" required></label>
            <button class="primary-button" type="submit">Inloggen <span>→</span></button>
        </form>
        <p class="login-note">Toegang voor Sofia en Marcus</p>
    </main>
</body>
</html>