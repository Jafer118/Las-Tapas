<?php
declare(strict_types=1);

function isReservationRateLimited(PDO $pdo, string $clientAddress): bool
{
    $secret = getenv('LAS_TAPAS_RATE_LIMIT_SECRET');
    if ($secret === false || strlen($secret) < 32) {
        if (getenv('APP_ENV') === 'production') {
            throw new RuntimeException('A 32-character rate-limit secret is required in production.');
        }
        $secret = 'local-development-only';
    }

    $clientHash = hash_hmac('sha256', $clientAddress, $secret);

    $statement = $pdo->prepare(
        'INSERT INTO reservation_request_limits (client_hash, attempts, window_started_at)
         VALUES (?, 1, NOW())
         ON DUPLICATE KEY UPDATE
            attempts = IF(
                window_started_at <= DATE_SUB(NOW(), INTERVAL 15 MINUTE),
                1,
                LEAST(attempts + 1, 65535)
            ),
            window_started_at = IF(
                window_started_at <= DATE_SUB(NOW(), INTERVAL 15 MINUTE),
                NOW(),
                window_started_at
            )'
    );
    $statement->execute([$clientHash]);

    $statement = $pdo->prepare(
        'SELECT attempts
         FROM reservation_request_limits
         WHERE client_hash = ?'
    );
    $statement->execute([$clientHash]);
    $attempts = (int) $statement->fetchColumn();

    if (random_int(1, 100) === 1) {
        $pdo->exec(
            'DELETE FROM reservation_request_limits
             WHERE window_started_at < DATE_SUB(NOW(), INTERVAL 1 DAY)'
        );
    }

    return $attempts > 5;
}
