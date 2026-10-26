<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/lib/validation.php';
require_once __DIR__ . '/../backend/lib/formules.php';
require_once __DIR__ . '/../backend/lib/pdf_bon.php';

function assertSameValue($expected, $actual, string $description): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($description . ' failed.');
    }
}

assertSameValue(42, parsePositiveInteger('42'), 'Positive integer parsing');
assertSameValue(null, parsePositiveInteger('0'), 'Zero rejection');
assertSameValue(null, parsePositiveInteger('-1'), 'Negative integer rejection');
assertSameValue(null, parsePositiveInteger('4x'), 'Malformed integer rejection');
assertSameValue(null, parsePositiveInteger([]), 'Non-scalar integer rejection');

assertSameValue(true, isValidCsrfToken('session-token', 'session-token'), 'Matching CSRF token');
assertSameValue(false, isValidCsrfToken('wrong-token', 'session-token'), 'Mismatched CSRF token');
assertSameValue(false, isValidCsrfToken('session-token', ''), 'Empty session token');

assertSameValue(true, isAllowedOrderStatusTransition('besteld', 'bereid'), 'Preparation transition');
assertSameValue(true, isAllowedOrderStatusTransition('bereid', 'geserveerd'), 'Served transition');
assertSameValue(false, isAllowedOrderStatusTransition('besteld', 'geserveerd'), 'Skipped transition');
assertSameValue(false, isAllowedOrderStatusTransition('geserveerd', 'bereid'), 'Reverse transition');

assertSameValue(['naam' => '3 uur tafelen', 'prijs' => 36.50], getDiningFormula('3-uur'), 'Dining formula lookup');
assertSameValue(null, getDiningFormula('unknown'), 'Unknown dining formula rejection');
assertSameValue(109.5, calculateDiningFormulaTotal('3-uur', 3, 36.50), 'Per-person formula total');
assertSameValue(0.0, calculateDiningFormulaTotal(null, null, null), 'Missing formula total');
assertSameValue(
    73.0,
    calculateOrderTotal([['subtotaal' => 120.0]], '3-uur', 2, 36.50),
    'Formula total excludes included dishes'
);
assertSameValue(
    120.0,
    calculateOrderTotal([['subtotaal' => 120.0]], null, null, null),
    'Regular order keeps dish totals'
);

$pdf = generateReceiptPdf([
    'tafel_naam' => 'Tafel 4',
    'bestelling_id' => 17,
    'datum_tijd' => '2026-09-30 19:30:00',
    'aantal_personen' => 2,
    'formule_naam' => '2 uur tafelen',
    'formule_code' => '2-uur',
    'formule_prijs' => 29.50,
    'formule_subtotaal' => 59.00,
    'orderregels' => [[
        'gerecht_naam' => 'Croquetas (ham)',
        'aantal' => 2,
        'subtotaal' => 13.00,
    ]],
    'totaal' => 59.00,
]);

if (strpos($pdf, '%PDF-1.4') !== 0
    || strpos($pdf, 'Croquetas \\(ham\\)') === false
    || strpos($pdf, 'Formule: 2 uur tafelen') === false
    || strpos($pdf, 'Inbegrepen') === false
    || strpos($pdf, 'EUR13,00') !== false
    || strpos($pdf, 'TOTAAL') === false
    || !preg_match('/startxref\n(\d+)\n%%EOF$/', $pdf, $matches)
    || substr($pdf, (int) $matches[1], 4) !== 'xref'
    || !preg_match('/xref\n0 7\n0000000000 65535 f \n((?:\d{10} 00000 n \n){6})/', $pdf, $xrefMatches)
) {
    throw new RuntimeException('PDF structure validation failed.');
}

preg_match_all('/(\d{10}) 00000 n \n/', $xrefMatches[1], $objectOffsets);
foreach ($objectOffsets[1] as $index => $offset) {
    $objectNumber = $index + 1;
    $objectHeader = $objectNumber . " 0 obj\n";
    if (substr($pdf, (int) $offset, strlen($objectHeader)) !== $objectHeader) {
        throw new RuntimeException('PDF cross-reference offset validation failed.');
    }
}

echo "All backend checks passed.\n";