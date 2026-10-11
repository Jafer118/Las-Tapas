<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../voorraad-las-tapas/session.php';
try {
    startAppSession();
} catch (Throwable $error) {
    error_log('Reservation session initialization failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'De beveiligde sessie kon niet worden gestart.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/rate_limit.php';
require_once __DIR__ . '/../lib/reservation_validation.php';

function sendReservationResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function reservationRequestBody(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== 0) {
        sendReservationResponse(['error' => 'Een JSON-aanvraag is vereist.'], 415);
    }

    $maximumBytes = 16384;
    $contentLength = filter_var($_SERVER['CONTENT_LENGTH'] ?? null, FILTER_VALIDATE_INT);
    if ($contentLength !== false && $contentLength > $maximumBytes) {
        sendReservationResponse(['error' => 'De aanvraag is te groot.'], 413);
    }

    $rawBody = file_get_contents('php://input', false, null, 0, $maximumBytes + 1);
    if ($rawBody === false) {
        sendReservationResponse(['error' => 'De aanvraag kon niet worden gelezen.'], 400);
    }

    if (strlen($rawBody) > $maximumBytes) {
        sendReservationResponse(['error' => 'De aanvraag is te groot.'], 413);
    }

    $body = json_decode($rawBody);
    if (json_last_error() !== JSON_ERROR_NONE || !$body instanceof stdClass) {
        sendReservationResponse(['error' => 'Ongeldige JSON-aanvraag.'], 400);
    }

    return get_object_vars($body);
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    sendReservationResponse(['error' => 'Methode niet toegestaan.'], 405);
}

try {
    if ($method === 'GET') {
        sendReservationResponse(['csrfToken' => csrfToken()]);
    }

    if (!hasValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        sendReservationResponse(['error' => 'Ongeldig beveiligingstoken.'], 403);
    }

    $pdo = database();

    if (isReservationRateLimited($pdo, $_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
        header('Retry-After: 900');
        sendReservationResponse(['error' => 'Te veel reserveringspogingen. Probeer het later opnieuw.'], 429);
    }

    $reservation = validatePublicReservation(reservationRequestBody());
    if ($reservation === null) {
        sendReservationResponse(['error' => 'Controleer je gegevens, datum, tijd, tafel en pakket.'], 422);
    }

    $pdo->beginTransaction();
    $pdo->exec(
        'DELETE FROM reservations
         WHERE reservation_date <= DATE_SUB(CURDATE(), INTERVAL 90 DAY)'
    );

    $statement = $pdo->prepare(
        'SELECT capacity
         FROM reservation_tables
         WHERE table_number = ?
         FOR UPDATE'
    );
    $statement->execute([$reservation['tableNumber']]);
    $capacity = $statement->fetchColumn();

    if ($capacity === false || $reservation['guestCount'] > (int) $capacity) {
        $pdo->rollBack();
        sendReservationResponse(['error' => 'Deze tafel is niet geschikt voor het gekozen aantal personen.'], 422);
    }

    $statement = $pdo->prepare(
        "SELECT id
         FROM reservations
         WHERE table_number = ?
           AND reservation_date = ?
           AND status = 'confirmed'
           AND TIMESTAMP(reservation_date, reservation_time) < ?
           AND DATE_ADD(
                TIMESTAMP(reservation_date, reservation_time),
                INTERVAL duration_minutes MINUTE
           ) > ?
         LIMIT 1"
    );
    $statement->execute([
        $reservation['tableNumber'],
        $reservation['date'],
        $reservation['end'],
        $reservation['start'],
    ]);

    if ($statement->fetchColumn() !== false) {
        $pdo->rollBack();
        sendReservationResponse(['error' => 'Deze tafel is op dat tijdstip niet meer beschikbaar. Kies een andere tafel of tijd.'], 409);
    }

    $statement = $pdo->prepare(
        "INSERT INTO reservations (
            customer_name, phone, email, special_requests, reservation_date,
            reservation_time, duration_minutes, guest_count, table_number,
            package_code, price_per_guest, total_price, status
         ) VALUES (
            :name, :phone, :email, :specialRequests, :date, :time,
            :durationMinutes, :guestCount, :tableNumber, :packageCode,
            :pricePerGuest, :totalPrice, 'confirmed'
         )"
    );
    $statement->execute([
        'name' => $reservation['name'],
        'phone' => $reservation['phone'],
        'email' => $reservation['email'],
        'specialRequests' => $reservation['specialRequests'],
        'date' => $reservation['date'],
        'time' => $reservation['time'],
        'durationMinutes' => $reservation['durationMinutes'],
        'guestCount' => $reservation['guestCount'],
        'tableNumber' => $reservation['tableNumber'],
        'packageCode' => $reservation['packageCode'],
        'pricePerGuest' => $reservation['pricePerGuest'],
        'totalPrice' => $reservation['totalPrice'],
    ]);
    $reservationId = (int) $pdo->lastInsertId();
    $pdo->commit();

    sendReservationResponse([
        'reservationId' => $reservationId,
        'totalPrice' => $reservation['totalPrice'],
    ], 201);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Reservation request failed: ' . $error->getMessage());
    sendReservationResponse(['error' => 'De reservering kon niet worden opgeslagen. Probeer het later opnieuw.'], 500);
}
