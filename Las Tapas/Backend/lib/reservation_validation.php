<?php
declare(strict_types=1);

function validatePublicReservation(array $input): ?array
{
    $name = $input['name'] ?? null;
    $phone = $input['phone'] ?? null;
    $email = $input['email'] ?? null;
    $specialRequests = $input['specialRequests'] ?? '';
    $date = $input['date'] ?? null;
    $time = $input['time'] ?? null;
    $tableNumberInput = $input['tableNumber'] ?? null;
    $guestCountInput = $input['guestCount'] ?? null;
    if ((!is_int($tableNumberInput) && !is_string($tableNumberInput))
        || (!is_int($guestCountInput) && !is_string($guestCountInput))
        || (is_string($tableNumberInput) && !ctype_digit($tableNumberInput))
        || (is_string($guestCountInput) && !ctype_digit($guestCountInput))
    ) {
        return null;
    }

    $tableNumber = filter_var($tableNumberInput, FILTER_VALIDATE_INT);
    $guestCount = filter_var($guestCountInput, FILTER_VALIDATE_INT);
    $package = $input['package'] ?? null;
    $consent = $input['privacyAccepted'] ?? null;

    if (!is_string($name)
        || !is_string($phone)
        || !is_string($email)
        || !is_string($specialRequests)
        || !is_string($date)
        || !is_string($time)
        || !is_string($package)
        || $consent !== true
    ) {
        return null;
    }

    $name = trim($name);
    $phone = trim($phone);
    $email = trim($email);
    $specialRequests = trim($specialRequests);
    $nameLength = preg_match_all('/./us', $name);
    $specialRequestLength = preg_match_all('/./us', $specialRequests);
    $phoneDigitCount = preg_match_all('/[0-9]/', $phone);

    if ($name === ''
        || $nameLength === false
        || $nameLength > 120
        || strlen($phone) > 30
        || !preg_match('/\A\+?[0-9][0-9(). -]{5,29}\z/', $phone)
        || $phoneDigitCount === false
        || $phoneDigitCount < 6
        || strlen($email) > 254
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $specialRequestLength === false
        || $specialRequestLength > 1000
        || $tableNumber === false
        || $tableNumber < 1
        || $tableNumber > 26
        || $guestCount === false
        || $guestCount < 1
        || $guestCount > 10
    ) {
        return null;
    }

    $packages = [
        '2 uur tafelen' => ['code' => 'two-hour', 'duration' => 120, 'price' => 29.50],
        '3 uur tafelen' => ['code' => 'three-hour', 'duration' => 180, 'price' => 36.50],
        '3 uur Spaans genieten met drinken' => ['code' => 'three-hour-drinks', 'duration' => 180, 'price' => 49.50],
    ];
    if (!isset($packages[$package])) {
        return null;
    }

    $allowedTimes = [
        '13:00', '13:30', '14:00', '14:30', '15:00',
        '17:00', '17:30', '18:00', '18:30', '19:00',
        '19:30', '20:00', '20:30', '21:00', '21:30',
    ];
    if (!in_array($time, $allowedTimes, true)
        || !preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $date)
    ) {
        return null;
    }

    $timezone = new DateTimeZone(date_default_timezone_get());
    $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, $timezone);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if ($start === false
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        || $start->format('Y-m-d H:i') !== $date . ' ' . $time
        || $start < new DateTimeImmutable('now', $timezone)
    ) {
        return null;
    }

    $openingHours = [
        0 => ['13:00', '21:00'],
        1 => null,
        2 => ['17:00', '22:30'],
        3 => ['17:00', '22:30'],
        4 => ['17:00', '23:00'],
        5 => ['17:00', '23:30'],
        6 => ['13:00', '23:30'],
    ];
    $hours = $openingHours[(int) $start->format('w')];
    $end = $start->modify('+' . $packages[$package]['duration'] . ' minutes');
    if ($hours === null || $start->format('H:i') < $hours[0] || $end->format('H:i') > $hours[1]) {
        return null;
    }

    return [
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'specialRequests' => $specialRequests,
        'date' => $date,
        'time' => $time,
        'start' => $start->format('Y-m-d H:i:s'),
        'end' => $end->format('Y-m-d H:i:s'),
        'tableNumber' => $tableNumber,
        'guestCount' => $guestCount,
        'packageCode' => $packages[$package]['code'],
        'durationMinutes' => $packages[$package]['duration'],
        'pricePerGuest' => $packages[$package]['price'],
        'totalPrice' => round($packages[$package]['price'] * $guestCount, 2),
    ];
}
