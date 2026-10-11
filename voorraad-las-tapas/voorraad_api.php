<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session.php';
startAppSession();
require_once __DIR__ . '/validation.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Inloggen vereist.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/db.php';

function response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requestBody(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== 0) {
        response(['error' => 'Een JSON-aanvraag is vereist.'], 415);
    }

    $maximumBytes = 1048576;
    $contentLength = filter_var($_SERVER['CONTENT_LENGTH'] ?? null, FILTER_VALIDATE_INT);
    if ($contentLength !== false && $contentLength > $maximumBytes) {
        response(['error' => 'De aanvraag is te groot.'], 413);
    }

    $rawBody = file_get_contents('php://input');
    if (strlen($rawBody) > $maximumBytes) {
        response(['error' => 'De aanvraag is te groot.'], 413);
    }

    $decodedBody = json_decode($rawBody);
    if (json_last_error() !== JSON_ERROR_NONE || !$decodedBody instanceof stdClass) {
        response(['error' => 'Ongeldige JSON-aanvraag.'], 400);
    }

    return get_object_vars($decodedBody);
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET', 'POST', 'PATCH', 'DELETE'], true)) {
        header('Allow: GET, POST, PATCH, DELETE');
        response(['error' => 'Methode niet toegestaan.'], 405);
    }

    $pdo = database();

    if ($method === 'GET') {
        $statement = $pdo->query(
            'SELECT id, naam AS name, categorie AS category, voorraad AS stock,
                    minimumvoorraad AS minimum, prijs AS price
             FROM voorraad_items
             WHERE actief = 1
             ORDER BY categorie, naam'
        );
        response(['items' => $statement->fetchAll(), 'csrfToken' => csrfToken()]);
    }

    if (!hasValidCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        response(['error' => 'Ongeldig beveiligingstoken.'], 403);
    }

    $body = requestBody();

    if ($method === 'POST') {
        $item = validateInventoryItem($body);
        if ($item === null) {
            response(['error' => 'Ongeldige artikelgegevens.'], 422);
        }

        $statement = $pdo->prepare(
            'INSERT INTO voorraad_items (naam, categorie, voorraad, minimumvoorraad, prijs)
             VALUES (:name, :category, :stock, :minimum, :price)'
        );
        $statement->execute($item);
        response(['id' => (int) $pdo->lastInsertId()], 201);
    }

    $id = parseInventoryId($body['id'] ?? null);
    if ($id === null) {
        response(['error' => 'Een geldig artikel-id is verplicht.'], 422);
    }

    if ($method === 'PATCH') {
        $delta = parseStockDelta($body['delta'] ?? null);
        if ($delta === null) {
            response(['error' => 'Ongeldige voorraadmutatie.'], 422);
        }

        $statement = $pdo->prepare(
            'UPDATE voorraad_items
             SET voorraad = GREATEST(0, voorraad + :delta)
             WHERE id = :id AND actief = 1'
        );
        $statement->execute(['delta' => $delta, 'id' => $id]);
        response(['updated' => $statement->rowCount() === 1]);
    }

    if ($method === 'DELETE') {
        $statement = $pdo->prepare('UPDATE voorraad_items SET actief = 0 WHERE id = :id AND actief = 1');
        $statement->execute(['id' => $id]);
        response(['archived' => $statement->rowCount() === 1]);
    }

    response(['error' => 'Methode niet toegestaan.'], 405);
} catch (Throwable $error) {
    error_log($error->getMessage());
    response(['error' => 'Databaseverbinding of query mislukt.'], 500);
}