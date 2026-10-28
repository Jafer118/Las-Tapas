CREATE DATABASE IF NOT EXISTS las_tapas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE las_tapas;

CREATE TABLE IF NOT EXISTS gebruikers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    gebruikersnaam VARCHAR(50) NOT NULL UNIQUE,
    naam VARCHAR(100) NOT NULL,
    wachtwoord_hash VARCHAR(255) NOT NULL,
    aangemaakt_op TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

INSERT INTO gebruikers (gebruikersnaam, naam, wachtwoord_hash)
SELECT 'sofia', 'Sofia', '$2y$10$A7QoAXZV9bCQeTRiTTfg1.9vL.1OFsJqLK6D9UjR86IhLgDT60fYu'
WHERE NOT EXISTS (SELECT 1 FROM gebruikers WHERE gebruikersnaam = 'sofia');

INSERT INTO gebruikers (gebruikersnaam, naam, wachtwoord_hash)
SELECT 'marcus', 'Marcus', '$2y$10$GFgyJqHUHxv6jJ6qpd9SUegSPCBOMx7BHxk1fxNTVCWVejBNqsp2e'
WHERE NOT EXISTS (SELECT 1 FROM gebruikers WHERE gebruikersnaam = 'marcus');

CREATE TABLE IF NOT EXISTS voorraad_login_rate_limits (
    client_hash CHAR(64) NOT NULL,
    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (client_hash),
    INDEX idx_voorraad_login_window (window_started_at)
);

CREATE TABLE IF NOT EXISTS voorraad_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    naam VARCHAR(120) NOT NULL,
    categorie ENUM('keuken', 'bar') NOT NULL,
    voorraad INT UNSIGNED NOT NULL DEFAULT 0,
    minimumvoorraad INT UNSIGNED NOT NULL DEFAULT 5,
    prijs DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    actief TINYINT(1) NOT NULL DEFAULT 1,
    aangemaakt_op TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    bijgewerkt_op TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_voorraad_actief (actief),
    INDEX idx_voorraad_categorie (categorie)
);

INSERT INTO voorraad_items (naam, categorie, voorraad, minimumvoorraad, prijs, actief)
SELECT menu_items.naam, menu_items.categorie, menu_items.voorraad, menu_items.minimumvoorraad, menu_items.prijs, 1
FROM (
    SELECT 'Aceitunas marinadas' AS naam, 'keuken' AS categorie, 43 AS voorraad, 12 AS minimumvoorraad, 3.50 AS prijs UNION ALL
    SELECT 'Pan con tomate', 'keuken', 55, 12, 4.00 UNION ALL
    SELECT 'Tabla de quesos españoles', 'keuken', 18, 5, 9.50 UNION ALL
    SELECT 'Jamón serrano', 'keuken', 22, 6, 8.50 UNION ALL
    SELECT 'Ensalada de atún', 'keuken', 30, 6, 6.50 UNION ALL
    SELECT 'Gazpacho', 'keuken', 35, 8, 4.50 UNION ALL
    SELECT 'Salmorejo', 'keuken', 28, 8, 4.50 UNION ALL
    SELECT 'Ensalada mixta', 'keuken', 33, 8, 5.50 UNION ALL
    SELECT 'Boquerones en vinagre', 'keuken', 20, 6, 5.00 UNION ALL
    SELECT 'Cóctel de gambas', 'keuken', 25, 6, 7.50 UNION ALL
    SELECT 'Patatas bravas', 'keuken', 42, 15, 5.50 UNION ALL
    SELECT 'Patatas alioli', 'keuken', 38, 15, 5.00 UNION ALL
    SELECT 'Gambas al ajillo', 'keuken', 32, 10, 9.50 UNION ALL
    SELECT 'Calamares fritos', 'keuken', 27, 10, 8.00 UNION ALL
    SELECT 'Croquetas', 'keuken', 40, 12, 6.50 UNION ALL
    SELECT 'Dátiles con bacon', 'keuken', 29, 8, 6.00 UNION ALL
    SELECT 'Alitas de pollo', 'keuken', 30, 10, 6.50 UNION ALL
    SELECT 'Pollo al ajillo', 'keuken', 24, 8, 7.50 UNION ALL
    SELECT 'Chorizo a la sidra', 'keuken', 19, 8, 7.00 UNION ALL
    SELECT 'Chorizo al vino', 'keuken', 21, 8, 7.00 UNION ALL
    SELECT 'Pimientos de padrón', 'keuken', 34, 10, 5.00 UNION ALL
    SELECT 'Champiñones al ajillo', 'keuken', 31, 10, 5.50 UNION ALL
    SELECT 'Tortilla española', 'keuken', 26, 10, 6.50 UNION ALL
    SELECT 'Huevos rotos', 'keuken', 23, 8, 8.00 UNION ALL
    SELECT 'Pinchos morunos', 'keuken', 28, 8, 7.50 UNION ALL
    SELECT 'Pulpo a la gallega', 'keuken', 8, 3, 12.50 UNION ALL
    SELECT 'Mini paella de mariscos', 'keuken', 17, 5, 10.50 UNION ALL
    SELECT 'Mini paella de pollo', 'keuken', 19, 5, 9.00 UNION ALL
    SELECT 'Paella mixta (tapa pequeña)', 'keuken', 15, 5, 10.00 UNION ALL
    SELECT 'Empanadillas', 'keuken', 37, 10, 6.00 UNION ALL
    SELECT 'Berenjenas fritas con miel', 'keuken', 29, 8, 5.50 UNION ALL
    SELECT 'Lomo a la plancha', 'keuken', 22, 8, 8.50 UNION ALL
    SELECT 'Montaditos variados', 'keuken', 31, 10, 7.00 UNION ALL
    SELECT 'Pan con alioli', 'keuken', 44, 12, 3.50 UNION ALL
    SELECT 'Crema catalana', 'keuken', 24, 8, 5.00 UNION ALL
    SELECT 'Flan', 'keuken', 28, 8, 4.00 UNION ALL
    SELECT 'Arroz con leche', 'keuken', 5, 6, 4.50 UNION ALL
    SELECT 'Churros con chocolate', 'keuken', 35, 10, 5.50 UNION ALL
    SELECT 'Tarta de Santiago', 'keuken', 18, 5, 5.50 UNION ALL
    SELECT 'Leche frita', 'keuken', 20, 6, 4.50 UNION ALL
    SELECT 'Helado de vainilla', 'keuken', 33, 8, 3.50 UNION ALL
    SELECT 'Natillas', 'keuken', 25, 6, 4.00 UNION ALL
    SELECT 'Fresas con nata', 'keuken', 27, 8, 5.00 UNION ALL
    SELECT 'Vino tinto', 'bar', 72, 15, 4.50 UNION ALL
    SELECT 'Vino blanco', 'bar', 68, 15, 4.50 UNION ALL
    SELECT 'Vino rosado', 'bar', 45, 10, 4.50 UNION ALL
    SELECT 'Sangría', 'bar', 88, 20, 5.50 UNION ALL
    SELECT 'Tinto de verano', 'bar', 65, 15, 4.50 UNION ALL
    SELECT 'Cerveza', 'bar', 130, 30, 3.50 UNION ALL
    SELECT 'Agua mineral', 'bar', 120, 25, 2.50 UNION ALL
    SELECT 'Refrescos', 'bar', 95, 20, 3.00 UNION ALL
    SELECT 'Zumo de naranja', 'bar', 50, 12, 3.50 UNION ALL
    SELECT 'Café solo', 'bar', 85, 20, 2.50 UNION ALL
    SELECT 'Café con leche', 'bar', 80, 20, 3.00 UNION ALL
    SELECT 'Cortado', 'bar', 75, 15, 2.50
) AS menu_items
WHERE NOT EXISTS (
    SELECT 1
    FROM voorraad_items
    WHERE voorraad_items.naam = menu_items.naam
);

UPDATE voorraad_items
SET actief = 0
WHERE naam IN (
    'Manchego Reserva',
    'Jamón Ibérico',
    'Gamba al Ajillo',
    'Albóndigas',
    'Olijven Mix',
    'Sangria Casa',
    'Cerveza Estrella',
    'Verdejo Glas'
);