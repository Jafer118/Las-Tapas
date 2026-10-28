<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
requireAuthenticatedUser();
requireHttpMethod('GET');
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$statement = $pdo->query(
    "SELECT gerechten.id, gerechten.naam, gerechten.beschrijving, gerechten.categorie,
            gerechten.menugroep, gerechten.prijs,
            COALESCE((
                SELECT voorraad_items.voorraad
                FROM voorraad_items
                WHERE voorraad_items.naam = gerechten.naam
                  AND voorraad_items.categorie = gerechten.categorie
                  AND voorraad_items.actief = 1
                ORDER BY voorraad_items.id
                LIMIT 1
            ), 0) AS voorraad
     FROM gerechten
     ORDER BY FIELD(gerechten.menugroep, 'Frías', 'Calientes', 'Especialidades', 'Postres', 'Bebidas'),
              gerechten.naam"
);
$menuItems = $statement->fetchAll();

echo json_encode(['succes' => true, 'gerechten' => $menuItems]);
