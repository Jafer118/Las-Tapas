<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

session_start();

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
    $body = json_decode(file_get_contents('php://input'), true);
    return is_array($body) ? $body : [];
}

try {
    $pdo = database();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $statement = $pdo->query(
            'SELECT id, naam AS name, categorie AS category, voorraad AS stock,
                    minimumvoorraad AS minimum, prijs AS price
             FROM voorraad_items
             WHERE actief = 1
             ORDER BY categorie, naam'
        );
        response(['items' => $statement->fetchAll()]);
    }

    $body = requestBody();
    $id = filter_var($body['id'] ?? null, FILTER_VALIDATE_INT);

    if ($method === 'POST') {
        $name = trim((string) ($body['name'] ?? ''));
        $category = $body['category'] ?? '';
        $stock = filter_var($body['stock'] ?? null, FILTER_VALIDATE_INT);
        $minimum = filter_var($body['minimum'] ?? null, FILTER_VALIDATE_INT);
        $price = filter_var($body['price'] ?? null, FILTER_VALIDATE_FLOAT);

        if ($name === '' || !in_array($category, ['keuken', 'bar'], true) || $stock === false || $minimum === false || $price === false || $stock < 0 || $minimum < 0 || $price < 0) {
            response(['error' => 'Ongeldige artikelgegevens.'], 422);
        }

        $statement = $pdo->prepare(
            'INSERT INTO voorraad_items (naam, categorie, voorraad, minimumvoorraad, prijs)
             VALUES (:name, :category, :stock, :minimum, :price)'
        );
        $statement->execute(['name' => $name, 'category' => $category, 'stock' => $stock, 'minimum' => $minimum, 'price' => $price]);
        response(['id' => (int) $pdo->lastInsertId()], 201);
    }

    if ($id === false || $id === null) {
        response(['error' => 'Een geldig artikel-id is verplicht.'], 422);
    }

    if ($method === 'PATCH') {
        $delta = filter_var($body['delta'] ?? null, FILTER_VALIDATE_INT);
        if ($delta === false || abs($delta) > 10000) {
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