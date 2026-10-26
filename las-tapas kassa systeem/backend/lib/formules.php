<?php
declare(strict_types=1);

function getDiningFormula(string $code): ?array
{
    $formulas = [
        '2-uur' => ['naam' => '2 uur tafelen', 'prijs' => 29.50],
        '3-uur' => ['naam' => '3 uur tafelen', 'prijs' => 36.50],
        '3-uur-drinken' => ['naam' => '3 uur + drinken', 'prijs' => 49.50],
    ];

    return $formulas[$code] ?? null;
}

function calculateDiningFormulaTotal(?string $code, ?int $guestCount, ?float $price): float
{
    if ($code === null || $guestCount === null || $price === null) {
        return 0.0;
    }

    return round($guestCount * $price, 2);
}

function calculateOrderTotal(array $orderLines, ?string $formulaCode, ?int $guestCount, ?float $formulaPrice): float
{
    if ($formulaCode !== null) {
        return calculateDiningFormulaTotal($formulaCode, $guestCount, $formulaPrice);
    }

    $total = 0.0;
    foreach ($orderLines as $orderLine) {
        $total += (float) ($orderLine['subtotaal'] ?? 0);
    }

    return round($total, 2);
}
