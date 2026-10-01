<?php

function parsePositiveInteger($value): ?int
{
    if (!is_string($value) && !is_int($value)) {
        return null;
    }

    $integer = filter_var($value, FILTER_VALIDATE_INT);
    return $integer !== false && $integer > 0 ? $integer : null;
}

function decodeJsonRequestBody(int $maximumBytes = 1048576): array
{
    $contentLength = $_SERVER['CONTENT_LENGTH'] ?? null;
    if (is_numeric($contentLength) && (int) $contentLength > $maximumBytes) {
        sendJsonResponse(['succes' => false, 'fout' => 'De aanvraag is te groot.'], 413);
    }

    $rawBody = file_get_contents('php://input');
    if (strlen($rawBody) > $maximumBytes) {
        sendJsonResponse(['succes' => false, 'fout' => 'De aanvraag is te groot.'], 413);
    }
    if ($rawBody === '') {
        return [];
    }

    $requestData = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($requestData)) {
        sendJsonResponse(['succes' => false, 'fout' => 'De JSON-invoer is ongeldig.'], 400);
    }

    return $requestData;
}

function isValidCsrfToken($providedToken, $sessionToken): bool
{
    return is_string($providedToken)
        && is_string($sessionToken)
        && $sessionToken !== ''
        && hash_equals($sessionToken, $providedToken);
}

function isAllowedOrderStatusTransition($currentStatus, $nextStatus): bool
{
    $allowedTransitions = ['besteld' => 'bereid', 'bereid' => 'geserveerd'];
    return is_string($currentStatus)
        && is_string($nextStatus)
        && ($allowedTransitions[$currentStatus] ?? null) === $nextStatus;
}