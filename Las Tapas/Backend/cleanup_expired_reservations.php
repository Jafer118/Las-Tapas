<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

try {
    $pdo = database();
    $statement = $pdo->prepare(
        'DELETE FROM reservations
         WHERE reservation_date <= DATE_SUB(CURDATE(), INTERVAL 90 DAY)'
    );
    $statement->execute();
    echo 'Expired reservations removed: ' . $statement->rowCount() . PHP_EOL;

    $statement = $pdo->prepare(
        'DELETE FROM reservation_request_limits
         WHERE window_started_at < DATE_SUB(NOW(), INTERVAL 1 DAY)'
    );
    $statement->execute();
    echo 'Expired request-limit records removed: ' . $statement->rowCount() . PHP_EOL;
} catch (Throwable $error) {
    error_log('Reservation data cleanup failed: ' . $error->getMessage());
    fwrite(STDERR, "Reservation data cleanup failed. Check the PHP error log.\n");
    exit(1);
}
