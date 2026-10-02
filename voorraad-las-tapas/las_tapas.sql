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
SELECT 'Patatas Bravas', 'keuken', 42, 15, 1.80
WHERE NOT EXISTS (SELECT 1 FROM voorraad_items);

INSERT INTO voorraad_items (naam, categorie, voorraad, minimumvoorraad, prijs)
SELECT * FROM (
    SELECT 'Manchego Reserva', 'keuken', 8, 10, 8.50 UNION ALL
    SELECT 'Jamón Ibérico', 'keuken', 4, 5, 14.25 UNION ALL
    SELECT 'Gamba al Ajillo', 'keuken', 24, 12, 5.20 UNION ALL
    SELECT 'Albóndigas', 'keuken', 31, 10, 3.75 UNION ALL
    SELECT 'Olijven Mix', 'keuken', 6, 8, 2.10 UNION ALL
    SELECT 'Sangria Casa', 'bar', 18, 12, 4.50 UNION ALL
    SELECT 'Cerveza Estrella', 'bar', 76, 24, 1.15 UNION ALL
    SELECT 'Verdejo Glas', 'bar', 3, 8, 2.40 UNION ALL
    SELECT 'Agua Mineral', 'bar', 40, 15, 0.55
) AS startdata
WHERE (SELECT COUNT(*) FROM voorraad_items) = 1;