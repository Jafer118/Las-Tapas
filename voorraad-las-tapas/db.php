<?php
declare(strict_types=1);

function database(): PDO
{
    static $connection;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = getenv('LAS_TAPAS_DB_HOST') ?: 'localhost';
    $database = getenv('LAS_TAPAS_DB_NAME') ?: 'las_tapas';
    $username = getenv('LAS_TAPAS_DB_USER') ?: 'root';
    $password = getenv('LAS_TAPAS_DB_PASSWORD');
    if ($password === false) {
        $password = '';
    }

    $connection = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $connection;
}