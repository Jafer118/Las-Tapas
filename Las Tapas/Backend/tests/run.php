<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/reservation_validation.php';

function assertSameValue($expected, $actual, string $description): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($description . ' failed.');
    }
}

$tuesday = new DateTimeImmutable('next Tuesday');
$validReservation = [
    'name' => '  Alex Example  ',
    'phone' => '+31 6 1234 5678',
    'email' => 'alex@example.nl',
    'specialRequests' => '',
    'date' => $tuesday->format('Y-m-d'),
    'time' => '18:00',
    'tableNumber' => 1,
    'guestCount' => 2,
    'package' => '2 uur tafelen',
    'privacyAccepted' => true,
];

$reservation = validatePublicReservation($validReservation);
assertSameValue('Alex Example', $reservation['name'], 'Trim customer name');
assertSameValue(120, $reservation['durationMinutes'], 'Use server-side package duration');
assertSameValue(59.0, $reservation['totalPrice'], 'Calculate total from server-side package price');
$drinksReservation = validatePublicReservation(array_merge(
    $validReservation,
    ['time' => '17:00', 'package' => '3 uur Spaans genieten met drinken']
));
assertSameValue(99.0, $drinksReservation['totalPrice'], 'Calculate drinks package total on the server');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['guestCount' => 11])), 'Reject too many guests');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['guestCount' => true])), 'Reject boolean guest count');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['tableNumber' => 27])), 'Reject unknown table');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['tableNumber' => true])), 'Reject boolean table number');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['email' => 'not-an-email'])), 'Reject malformed email');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['phone' => 'call me'])), 'Reject malformed phone number');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['specialRequests' => str_repeat('x', 1001)])), 'Reject oversized special requests');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['package' => 'free package'])), 'Reject unknown package');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['privacyAccepted' => false])), 'Require privacy notice acknowledgement');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['date' => '2099-02-30'])), 'Reject impossible date');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['time' => '18:15'])), 'Reject unavailable time slot');
assertSameValue(null, validatePublicReservation(array_merge($validReservation, ['time' => '21:00'])), 'Reject package ending after closing');

$monday = new DateTimeImmutable('next Monday');
assertSameValue(
    null,
    validatePublicReservation(array_merge($validReservation, ['date' => $monday->format('Y-m-d'), 'time' => '18:00'])),
    'Reject reservations on the closed day'
);

echo "All public reservation validation checks passed.\n";
