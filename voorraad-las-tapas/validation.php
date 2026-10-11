<?php
declare(strict_types=1);

function validateInventoryItem(array $input): ?array
{
    $nameInput = $input['name'] ?? null;
    $category = $input['category'] ?? null;
    $priceInput = $input['price'] ?? null;
    if (!is_string($nameInput)
        || !is_string($category)
        || (!is_int($priceInput) && !is_float($priceInput) && !is_string($priceInput))
    ) {
        return null;
    }

    $name = trim($nameInput);
    $nameLength = preg_match_all('/./us', $name);
    $stock = filter_var($input['stock'] ?? null, FILTER_VALIDATE_INT);
    $minimum = filter_var($input['minimum'] ?? null, FILTER_VALIDATE_INT);
    $price = filter_var($priceInput, FILTER_VALIDATE_FLOAT);

    if ($name === ''
        || $nameLength === false
        || $nameLength > 120
        || !in_array($category, ['keuken', 'bar'], true)
        || $stock === false
        || $minimum === false
        || $price === false
        || $stock < 0
        || $stock > 4294967295
        || $minimum < 0
        || $minimum > 4294967295
        || !is_finite((float) $price)
        || $price < 0
        || $price > 99999999.99
        || round((float) $price, 2) !== (float) $price
    ) {
        return null;
    }

    return [
        'name' => $name,
        'category' => $category,
        'stock' => $stock,
        'minimum' => $minimum,
        'price' => (float) $price,
    ];
}

function parseInventoryId($value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);
    return $id !== false && $id > 0 && $id <= 4294967295 ? $id : null;
}

function parseStockDelta($value): ?int
{
    $delta = filter_var($value, FILTER_VALIDATE_INT);
    return $delta !== false && $delta >= -10000 && $delta <= 10000 ? $delta : null;
}
