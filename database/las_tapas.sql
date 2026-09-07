-- =====================================================================
-- LAS TAPAS - Digitaal bestel- en kassasysteem
-- Database schema + voorbeelddata
-- Opdracht: Gilde DevOps Solutions - MBO4 Software Development
-- =====================================================================

CREATE DATABASE IF NOT EXISTS las_tapas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE las_tapas;

-- ---------------------------------------------------------------------
-- Tabel: tafels
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tafels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(50) NOT NULL,
    status ENUM('vrij', 'bezet') NOT NULL DEFAULT 'vrij'
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: gerechten (eten EN drinken; categorie bepaalt keuken/bar-scherm)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gerechten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL,
    categorie ENUM('keuken', 'bar') NOT NULL,
    prijs DECIMAL(6,2) NOT NULL,
    voorraad INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: bestellingen (1 "lopende rekening" per tafel, zolang open)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bestellingen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tafel_id INT NOT NULL,
    aangemaakt_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('open', 'afgerekend') NOT NULL DEFAULT 'open',
    FOREIGN KEY (tafel_id) REFERENCES tafels(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: orderregels (1 regel per besteld gerecht binnen een bestelling)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orderregels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bestelling_id INT NOT NULL,
    gerecht_id INT NOT NULL,
    aantal INT NOT NULL,
    prijs_per_stuk DECIMAL(6,2) NOT NULL, -- prijs op moment van bestellen (historie blijft correct)
    status ENUM('besteld', 'bereid', 'geserveerd') NOT NULL DEFAULT 'besteld',
    besteld_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bestelling_id) REFERENCES bestellingen(id),
    FOREIGN KEY (gerecht_id) REFERENCES gerechten(id)
) ENGINE=InnoDB;

-- =====================================================================
-- Voorbeelddata
-- =====================================================================

INSERT INTO tafels (naam, status) VALUES
    ('Tafel 1', 'vrij'),
    ('Tafel 2', 'vrij'),
    ('Tafel 3', 'vrij'),
    ('Tafel 4', 'vrij'),
    ('Tafel 5', 'vrij'),
    ('Tafel 6', 'vrij'),
    ('Tafel 7', 'vrij'),
    ('Tafel 8', 'vrij'),
    ('Tafel 9', 'vrij'),
    ('Tafel 10 (Sofia & Marcus)', 'vrij');

INSERT INTO gerechten (naam, categorie, prijs, voorraad) VALUES
    ('Gambas al Ajillo', 'keuken', 9.50, 40),
    ('Albondigas', 'keuken', 7.50, 40),
    ('Patatas Bravas', 'keuken', 5.50, 50),
    ('Croquetas de Jamon', 'keuken', 6.50, 35),
    ('Pan con Tomate', 'keuken', 4.00, 60),
    ('Tortilla Espanola', 'keuken', 6.00, 30),
    ('Sangria (glas)', 'bar', 5.50, 100),
    ('Verdejo (glas)', 'bar', 4.50, 80),
    ('Rioja (glas)', 'bar', 5.00, 80),
    ('Cerveza', 'bar', 3.50, 120),
    ('Water (plat/bruis)', 'bar', 2.50, 150);
