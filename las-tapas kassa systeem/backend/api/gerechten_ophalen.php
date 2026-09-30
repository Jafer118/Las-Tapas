<?php
require_once __DIR__ . '/../lib/auth.php';
requireAuthenticatedUser();
requireHttpMethod('GET');
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$statement = $pdo->query(
    "SELECT id, naam, beschrijving, categorie, menugroep, prijs, voorraad
     FROM gerechten
     ORDER BY FIELD(menugroep, 'Frías', 'Calientes', 'Especialidades', 'Postres', 'Bebidas'), naam"
);
$menuItems = $statement->fetchAll();

echo json_encode(['succes' => true, 'gerechten' => $menuItems]);
