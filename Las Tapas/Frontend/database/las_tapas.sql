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
SELECT 'sofia', 'Sofia', '$2y$10$d5BDa/W42divn6206WIrke5cmFtQ.4gXdAUyx9icsq8CojoGjB1gS'
WHERE NOT EXISTS (SELECT 1 FROM gebruikers WHERE gebruikersnaam = 'sofia');

INSERT INTO gebruikers (gebruikersnaam, naam, wachtwoord_hash)
SELECT 'marcus', 'Marcus', '$2y$10$gpW/lvX/.OJBcjeNz8nHsOjNsPIY/2LF0GhvYnxmELEAxhEJ/Rwc2'
WHERE NOT EXISTS (SELECT 1 FROM gebruikers WHERE gebruikersnaam = 'marcus');

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

INSERT INTO voorraad_items (naam, categorie, voorraad, minimumvoorraad, prijs)
SELECT menu_items.naam, menu_items.categorie, 0, 0, 0.00
FROM (
    SELECT 'Aceitunas marinadas' AS naam, 'keuken' AS categorie UNION ALL
    SELECT 'Pan con tomate', 'keuken' UNION ALL
    SELECT 'Tabla de quesos españoles', 'keuken' UNION ALL
    SELECT 'Jamón serrano', 'keuken' UNION ALL
    SELECT 'Ensalada de atún', 'keuken' UNION ALL
    SELECT 'Gazpacho', 'keuken' UNION ALL
    SELECT 'Salmorejo', 'keuken' UNION ALL
    SELECT 'Ensalada mixta', 'keuken' UNION ALL
    SELECT 'Boquerones en vinagre', 'keuken' UNION ALL
    SELECT 'Cóctel de gambas', 'keuken' UNION ALL
    SELECT 'Patatas bravas', 'keuken' UNION ALL
    SELECT 'Patatas alioli', 'keuken' UNION ALL
    SELECT 'Gambas al ajillo', 'keuken' UNION ALL
    SELECT 'Calamares fritos', 'keuken' UNION ALL
    SELECT 'Croquetas', 'keuken' UNION ALL
    SELECT 'Dátiles con bacon', 'keuken' UNION ALL
    SELECT 'Alitas de pollo', 'keuken' UNION ALL
    SELECT 'Pollo al ajillo', 'keuken' UNION ALL
    SELECT 'Chorizo a la sidra', 'keuken' UNION ALL
    SELECT 'Chorizo al vino', 'keuken' UNION ALL
    SELECT 'Pimientos de padrón', 'keuken' UNION ALL
    SELECT 'Champiñones al ajillo', 'keuken' UNION ALL
    SELECT 'Tortilla española', 'keuken' UNION ALL
    SELECT 'Huevos rotos', 'keuken' UNION ALL
    SELECT 'Pinchos morunos', 'keuken' UNION ALL
    SELECT 'Pulpo a la gallega', 'keuken' UNION ALL
    SELECT 'Mini paella de mariscos', 'keuken' UNION ALL
    SELECT 'Mini paella de pollo', 'keuken' UNION ALL
    SELECT 'Paella mixta (tapa pequeña)', 'keuken' UNION ALL
    SELECT 'Empanadillas', 'keuken' UNION ALL
    SELECT 'Berenjenas fritas con miel', 'keuken' UNION ALL
    SELECT 'Lomo a la plancha', 'keuken' UNION ALL
    SELECT 'Montaditos variados', 'keuken' UNION ALL
    SELECT 'Pan con alioli', 'keuken' UNION ALL
    SELECT 'Crema catalana', 'keuken' UNION ALL
    SELECT 'Flan', 'keuken' UNION ALL
    SELECT 'Arroz con leche', 'keuken' UNION ALL
    SELECT 'Churros con chocolate', 'keuken' UNION ALL
    SELECT 'Tarta de Santiago', 'keuken' UNION ALL
    SELECT 'Leche frita', 'keuken' UNION ALL
    SELECT 'Helado de vainilla', 'keuken' UNION ALL
    SELECT 'Natillas', 'keuken' UNION ALL
    SELECT 'Fresas con nata', 'keuken' UNION ALL
    SELECT 'Vino tinto', 'bar' UNION ALL
    SELECT 'Vino blanco', 'bar' UNION ALL
    SELECT 'Vino rosado', 'bar' UNION ALL
    SELECT 'Sangría', 'bar' UNION ALL
    SELECT 'Tinto de verano', 'bar' UNION ALL
    SELECT 'Cerveza', 'bar' UNION ALL
    SELECT 'Agua mineral', 'bar' UNION ALL
    SELECT 'Refrescos', 'bar' UNION ALL
    SELECT 'Zumo de naranja', 'bar' UNION ALL
    SELECT 'Café solo', 'bar' UNION ALL
    SELECT 'Café con leche', 'bar' UNION ALL
    SELECT 'Cortado', 'bar'
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