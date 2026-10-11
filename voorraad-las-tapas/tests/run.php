<?php
declare(strict_types=1);

require_once __DIR__ . '/../validation.php';

function assertSameValue($expected, $actual, string $description): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($description . ' failed.');
    }
}

$validItem = [
    'name' => '  Tomate  ',
    'category' => 'keuken',
    'stock' => 4,
    'minimum' => 2,
    'price' => 3.5,
];

assertSameValue('Tomate', validateInventoryItem($validItem)['name'], 'Trim item name');
assertSameValue(null, validateInventoryItem(array_merge($validItem, ['name' => []])), 'Reject non-string item name');
assertSameValue(null, validateInventoryItem(array_merge($validItem, ['name' => str_repeat('a', 121)])), 'Reject overlong item name');
assertSameValue(null, validateInventoryItem(array_merge($validItem, ['category' => 'other'])), 'Reject unknown category');
assertSameValue(null, validateInventoryItem(array_merge($validItem, ['stock' => -1])), 'Reject negative stock');
assertSameValue(null, validateInventoryItem(array_merge($validItem, ['minimum' => 4294967296])), 'Reject out-of-range minimum stock');
assertSameValue(null, validateInventoryItem(array_merge($validItem, ['price' => 3.501])), 'Reject prices with more than two decimals');
assertSameValue(null, validateInventoryItem(array_merge($validItem, ['price' => INF])), 'Reject non-finite prices');

assertSameValue(12, parseInventoryId('12'), 'Parse positive inventory ID');
assertSameValue(null, parseInventoryId('0'), 'Reject zero inventory ID');
assertSameValue(null, parseInventoryId('-1'), 'Reject negative inventory ID');
assertSameValue(1, parseStockDelta('1'), 'Parse stock increase');
assertSameValue(-1, parseStockDelta('-1'), 'Parse stock decrease');
assertSameValue(null, parseStockDelta('10001'), 'Reject oversized stock change');

echo "All inventory validation checks passed.\n";
