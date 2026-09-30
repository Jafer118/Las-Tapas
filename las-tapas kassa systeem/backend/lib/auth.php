<?php
require_once __DIR__ . '/validation.php';

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$isProduction = strtolower(getenv('APP_ENV') ?: 'production') === 'production';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https || $isProduction,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function getAuthenticatedUser(): ?array
{
    return $_SESSION['gebruiker'] ?? null;
}

function requireAuthenticatedUser(): void
{
    if (!getAuthenticatedUser()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['succes' => false, 'fout' => 'Inloggen is vereist.']);
        exit;
    }
}

function sendJsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function requireValidCsrfToken(): void
{
    $providedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!isValidCsrfToken($providedToken, $sessionToken)) {
        sendJsonResponse(['succes' => false, 'fout' => 'Ongeldige beveiligingscontrole. Vernieuw de pagina en probeer opnieuw.'], 403);
    }
}

function requireHttpMethod(string $expectedMethod): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $expectedMethod) {
        header('Allow: ' . $expectedMethod);
        sendJsonResponse(['succes' => false, 'fout' => 'Deze HTTP-methode is niet toegestaan.'], 405);
    }
}

function enforceRateLimit(PDO $pdo, string $scope, int $maximumAttempts, int $windowMinutes): void
{
    $clientAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateLimitSecret = getenv('LAS_TAPAS_RATE_LIMIT_SECRET') ?: 'local-development-only';
    $clientHash = hash_hmac('sha256', $clientAddress, $rateLimitSecret);
    $windowMinutes = max(1, min($windowMinutes, 1440));
    $windowStart = 'DATE_SUB(NOW(), INTERVAL ' . $windowMinutes . ' MINUTE)';

    $statement = $pdo->prepare(
        'INSERT INTO kassa_api_rate_limits (scope, client_hash, attempts, window_started_at)
         VALUES (?, ?, 1, NOW())
         ON DUPLICATE KEY UPDATE
            attempts = IF(window_started_at <= ' . $windowStart . ', 1, attempts + 1),
            window_started_at = IF(window_started_at <= ' . $windowStart . ', NOW(), window_started_at)'
    );
    $statement->execute([$scope, $clientHash]);

    if (random_int(1, 100) === 1) {
        $pdo->exec('DELETE FROM kassa_api_rate_limits WHERE window_started_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }

    $statement = $pdo->prepare('SELECT attempts FROM kassa_api_rate_limits WHERE scope = ? AND client_hash = ?');
    $statement->execute([$scope, $clientHash]);
    if ((int) $statement->fetchColumn() > $maximumAttempts) {
        header('Retry-After: ' . ($windowMinutes * 60));
        sendJsonResponse(['succes' => false, 'fout' => 'Te veel verzoeken. Probeer het later opnieuw.'], 429);
    }
}

set_exception_handler(static function (Throwable $exception): void {
    error_log('Unhandled API exception: ' . $exception->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        ['succes' => false, 'fout' => 'Er is een onverwachte fout opgetreden. Probeer het later opnieuw.'],
        JSON_UNESCAPED_UNICODE
    );
});
