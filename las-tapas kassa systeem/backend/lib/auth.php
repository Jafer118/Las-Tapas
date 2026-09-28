<?php
// Session-based authentication shared by all protected API endpoints.
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function ingelogdeGebruiker(): ?array
{
    return $_SESSION['gebruiker'] ?? null;
}

function vereisIngelogd(): void
{
    if (!ingelogdeGebruiker()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['succes' => false, 'fout' => 'Inloggen is vereist.']);
        exit;
    }
}

function antwoord(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
