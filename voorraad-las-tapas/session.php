<?php
declare(strict_types=1);

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (ini_set('session.use_strict_mode', '1') === false) {
        throw new RuntimeException('Unable to enable strict session mode.');
    }

    $cookieParams = session_get_cookie_params();
    if (!session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookieParams['path'],
        'domain' => $cookieParams['domain'],
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ])) {
        throw new RuntimeException('Unable to configure the session cookie.');
    }

    if (!session_start()) {
        throw new RuntimeException('Unable to start the application session.');
    }

    header('Cache-Control: no-store');
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function hasValidCsrfToken(?string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && $token !== null
        && hash_equals($_SESSION['csrf_token'], $token);
}
