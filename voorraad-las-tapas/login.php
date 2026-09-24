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
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .login-page{align-items:center;background:#293b3a;display:flex;justify-content:center;min-height:100vh;padding:24px}
        .login-card{background:#fffdf9;border-radius:6px;box-shadow:0 24px 70px #17252266;max-width:430px;padding:42px;width:100%}
        .login-brand{align-items:center;display:flex;gap:11px;margin-bottom:55px}.login-brand strong{display:block;font:21px 'Playfair Display',serif}.login-brand small{color:#858a83;display:block;font-size:10px;margin-top:4px}
        .login-card h1{font:38px 'Playfair Display',serif;margin:0 0 9px}.login-intro{color:#858a83;font-size:12px;line-height:1.6;margin:0 0 24px}.login-form{display:grid;gap:16px}.login-form label{color:#5b655d;display:grid;font-size:11px;font-weight:700;gap:7px}.login-form input{background:#fff;border:1px solid #e7e0d5;border-radius:3px;color:#242a26;outline:0;padding:12px}.login-form input:focus{border-color:#d65b48}.login-form .primary-button{background:#d65b48;border:0;color:#fff;margin-top:6px;padding:12px;width:100%}.login-error{background:#fae0db;border:1px solid #f1c3ba;border-radius:3px;color:#ad4435;font-size:11px;margin-bottom:16px;padding:11px}.login-note{color:#aaa69e;font-size:10px;margin:25px 0 0;text-align:center}
    </style>
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