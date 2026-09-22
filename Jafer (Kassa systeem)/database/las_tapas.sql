-- =====================================================================
-- LAS TAPAS - Digitaal bestel- en kassasysteem
-- Database schema + officiële data (plattegrond + menukaart)
-- Opdracht: Gilde DevOps Solutions - MBO4 Software Development
-- =====================================================================

CREATE DATABASE IF NOT EXISTS las_tapas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE las_tapas;

-- ---------------------------------------------------------------------
-- Tabel: tafels
-- Gebaseerd op de officiële plattegrond: 10 tafels begane grond
-- (restaurant + bar), 16 tafels eerste verdieping. Elke tafel heeft
-- plaats voor 4 personen (40 + 64 = 104 zitplaatsen totaal).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tafels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(50) NOT NULL,
    capaciteit INT NOT NULL DEFAULT 4,
    verdieping ENUM('Begane grond', 'Eerste verdieping') NOT NULL DEFAULT 'Begane grond',
    status ENUM('vrij', 'bezet') NOT NULL DEFAULT 'vrij'
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: gerechten
-- 'categorie' bepaalt WIE het maakt (keuken- of barscherm).
-- 'menugroep' bepaalt WAAR het op de menukaart staat (Frías, Calientes,
-- Especialidades, Postres, Bebidas) — dit zijn twee verschillende dingen.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gerechten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(120) NOT NULL,
    beschrijving VARCHAR(255) NULL,
    categorie ENUM('keuken', 'bar') NOT NULL,
    menugroep ENUM('Frías', 'Calientes', 'Especialidades', 'Postres', 'Bebidas') NOT NULL,
    prijs DECIMAL(6,2) NOT NULL,
    voorraad INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: bestellingen (1 "lopende rekening" per tafel, zolang open)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bestellingen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tafel_id INT NOT NULL,
    klant_email VARCHAR(255) NULL,
    aantal_personen INT NULL,
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
-- Tafels — volgens de plattegrond
-- =====================================================================

INSERT INTO tafels (naam, capaciteit, verdieping, status) VALUES
    ('Tafel 1', 4, 'Begane grond', 'vrij'),
    ('Tafel 2', 4, 'Begane grond', 'vrij'),
    ('Tafel 3', 4, 'Begane grond', 'vrij'),
    ('Tafel 4', 4, 'Begane grond', 'vrij'),
    ('Tafel 5', 4, 'Begane grond', 'vrij'),
    ('Tafel 6', 4, 'Begane grond', 'vrij'),
    ('Tafel 7', 4, 'Begane grond', 'vrij'),
    ('Tafel 8', 4, 'Begane grond', 'vrij'),
    ('Tafel 9', 4, 'Begane grond', 'vrij'),
    ('Tafel 10', 4, 'Begane grond', 'vrij'),
    ('Tafel 11', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 12', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 13', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 14', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 15', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 16', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 17', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 18', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 19', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 20', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 21', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 22', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 23', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 24', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 25', 4, 'Eerste verdieping', 'vrij'),
    ('Tafel 26', 4, 'Eerste verdieping', 'vrij');

-- =====================================================================
-- Menukaart — officiële versie
-- Let op: prijzen zijn REALISTISCHE STANDAARDPRIJZEN, niet aangeleverd
-- in de officiële kaart. Pas ze aan via phpMyAdmin (tabel 'gerechten')
-- zodra de echte prijzen bekend zijn.
-- =====================================================================

INSERT INTO gerechten (naam, beschrijving, categorie, menugroep, prijs, voorraad) VALUES

-- ---------- TAPAS FRÍAS (koud, bereid door de keuken) ----------
('Aceitunas marinadas', 'Gemarineerde olijven met olijfolie, knoflook en kruiden.', 'keuken', 'Frías', 3.50, 50),
('Pan con tomate', 'Geroosterd brood met verse tomaat, olijfolie en zout.', 'keuken', 'Frías', 4.00, 60),
('Tabla de quesos españoles', 'Selectie van Spaanse kazen zoals manchego en geitenkaas.', 'keuken', 'Frías', 9.50, 25),
('Jamón serrano', 'Gedroogde Spaanse ham met een zoute, rijke smaak.', 'keuken', 'Frías', 8.50, 30),
('Ensalada de atún', 'Tonijnsalade met ui, tomaat en olijfolie.', 'keuken', 'Frías', 6.50, 35),
('Gazpacho', 'Koude soep van tomaat, paprika en komkommer.', 'keuken', 'Frías', 4.50, 40),
('Salmorejo', 'Dikke koude tomatensoep met brood, ei en ham.', 'keuken', 'Frías', 4.50, 40),
('Ensalada mixta', 'Gemengde salade met sla, tomaat, ei en olijven.', 'keuken', 'Frías', 5.50, 40),
('Boquerones en vinagre', 'Ansjovis gemarineerd in azijn en knoflook.', 'keuken', 'Frías', 5.00, 30),
('Cóctel de gambas', 'Garnalen met een romige cocktailsaus.', 'keuken', 'Frías', 7.50, 30),

-- ---------- TAPAS CALIENTES (warm, bereid door de keuken) ----------
('Patatas bravas', 'Gebakken aardappelen met pittige saus.', 'keuken', 'Calientes', 5.50, 50),
('Patatas alioli', 'Aardappelen met knoflooksaus.', 'keuken', 'Calientes', 5.00, 50),
('Gambas al ajillo', 'Garnalen gebakken in knoflook en olie.', 'keuken', 'Calientes', 9.50, 40),
('Calamares fritos', 'Gefrituurde inktvisringen.', 'keuken', 'Calientes', 8.00, 35),
('Croquetas', 'Gefrituurde kroketjes gevuld met ham, kaas of kip.', 'keuken', 'Calientes', 6.50, 45),
('Dátiles con bacon', 'Dadels omwikkeld met spek.', 'keuken', 'Calientes', 6.00, 35),
('Alitas de pollo', 'Kippenvleugels met kruiden, gefrituurd of gebakken.', 'keuken', 'Calientes', 6.50, 40),
('Pollo al ajillo', 'Kip gebakken met knoflook en olie.', 'keuken', 'Calientes', 7.50, 35),
('Chorizo a la sidra', 'Chorizoworst gekookt in cider.', 'keuken', 'Calientes', 7.00, 30),
('Chorizo al vino', 'Chorizoworst bereid in rode wijnsaus.', 'keuken', 'Calientes', 7.00, 30),
('Pimientos de padrón', 'Kleine groene pepers gebakken met zout.', 'keuken', 'Calientes', 5.00, 40),
('Champiñones al ajillo', 'Champignons in knoflookolie.', 'keuken', 'Calientes', 5.50, 40),

-- ---------- ESPECIALIDADES / PLATOS TÍPICOS (bereid door de keuken) ----------
('Tortilla española', 'Spaanse omelet met aardappel en ui.', 'keuken', 'Especialidades', 6.50, 35),
('Huevos rotos', 'Gebakken eieren met aardappelen en ham.', 'keuken', 'Especialidades', 8.00, 30),
('Pinchos morunos', 'Gekruide vleesspiesjes.', 'keuken', 'Especialidades', 7.50, 35),
('Pulpo a la gallega', 'Octopus met paprikapoeder en olijfolie.', 'keuken', 'Especialidades', 12.50, 20),
('Mini paella de mariscos', 'Kleine paella met zeevruchten zoals garnalen en mosselen.', 'keuken', 'Especialidades', 10.50, 25),
('Mini paella de pollo', 'Kleine paella met kip en groenten.', 'keuken', 'Especialidades', 9.00, 25),
('Paella mixta (tapa pequeña)', 'Kleine portie paella met vlees en vis samen.', 'keuken', 'Especialidades', 10.00, 25),
('Empanadillas', 'Kleine gevulde deegpakketjes met vlees of vis.', 'keuken', 'Especialidades', 6.00, 40),
('Berenjenas fritas con miel', 'Gefrituurde aubergine met honing.', 'keuken', 'Especialidades', 5.50, 35),
('Lomo a la plancha', 'Gegrild varkensvlees.', 'keuken', 'Especialidades', 8.50, 30),
('Montaditos variados', 'Kleine broodjes met verschillende belegsoorten.', 'keuken', 'Especialidades', 7.00, 35),
('Pan con alioli', 'Brood met knoflooksaus.', 'keuken', 'Especialidades', 3.50, 50),

-- ---------- POSTRES (bereid door de keuken) ----------
('Crema catalana', 'Dessert met custard en een krokante suikerlaag.', 'keuken', 'Postres', 5.00, 30),
('Flan', 'Pudding van ei en karamel.', 'keuken', 'Postres', 4.00, 35),
('Arroz con leche', 'Rijst gekookt in melk met suiker en kaneel.', 'keuken', 'Postres', 4.50, 30),
('Churros con chocolate', 'Gefrituurd deeg met warme chocoladesaus.', 'keuken', 'Postres', 5.50, 40),
('Tarta de Santiago', 'Amandeltaart uit Spanje.', 'keuken', 'Postres', 5.50, 25),
('Leche frita', 'Gefrituurd melkgebak met suiker.', 'keuken', 'Postres', 4.50, 25),
('Helado de vainilla', 'Vanille-ijs.', 'keuken', 'Postres', 3.50, 40),
('Natillas', 'Zoete custard met kaneel.', 'keuken', 'Postres', 4.00, 30),
('Fresas con nata', 'Aardbeien met slagroom.', 'keuken', 'Postres', 5.00, 30),

-- ---------- BEBIDAS (bereid door de bar) ----------
('Vino tinto', 'Rode wijn, vaak stevig en vol van smaak (bij vleesgerechten).', 'bar', 'Bebidas', 4.50, 80),
('Vino blanco', 'Witte wijn, fris en licht (bij vis en lichte tapas).', 'bar', 'Bebidas', 4.50, 80),
('Vino rosado', 'Rosé wijn, een frisse mix tussen rood en wit.', 'bar', 'Bebidas', 4.50, 60),
('Sangría', 'Wijn gemengd met fruit, suiker en soms frisdrank.', 'bar', 'Bebidas', 5.50, 100),
('Tinto de verano', 'Rode wijn met frisdrank en ijs, een lichtere variant van sangria.', 'bar', 'Bebidas', 4.50, 80),
('Cerveza', 'Bier, zoals bekende Spaanse merken (bijv. Estrella).', 'bar', 'Bebidas', 3.50, 150),
('Agua mineral', 'Mineraalwater, met of zonder koolzuur.', 'bar', 'Bebidas', 2.50, 150),
('Refrescos', 'Frisdranken zoals cola, sinaasappel en citroenlimonade.', 'bar', 'Bebidas', 3.00, 120),
('Zumo de naranja', 'Sinaasappelsap, vaak vers geperst.', 'bar', 'Bebidas', 3.50, 60),
('Café solo', 'Sterke zwarte koffie (espresso).', 'bar', 'Bebidas', 2.50, 100),
('Café con leche', 'Koffie met melk.', 'bar', 'Bebidas', 3.00, 100),
('Cortado', 'Koffie met een klein beetje melk.', 'bar', 'Bebidas', 2.50, 100);

-- =====================================================================
-- MIGRATIE — alleen nodig als je de database al eerder had aangemaakt.
-- Voer in phpMyAdmin > tabblad SQL de regels uit die op jouw situatie
-- van toepassing zijn. Heb je de database nu voor het EERST
-- geïmporteerd? Dan hoef je niets van onderstaande uit te voeren, want
-- alles staat al goed in de CREATE TABLE + INSERT's hierboven.
-- =====================================================================

-- Eerder toegevoegde kolommen (uit vorige versies):
-- ALTER TABLE bestellingen ADD COLUMN klant_email VARCHAR(255) NULL AFTER tafel_id;
-- ALTER TABLE bestellingen ADD COLUMN aantal_personen INT NULL AFTER klant_email;
-- ALTER TABLE tafels ADD COLUMN capaciteit INT NOT NULL DEFAULT 4 AFTER naam;

-- NIEUW in deze versie — menukaart & plattegrond:
-- ALTER TABLE tafels ADD COLUMN verdieping ENUM('Begane grond','Eerste verdieping') NOT NULL DEFAULT 'Begane grond' AFTER capaciteit;
-- ALTER TABLE gerechten ADD COLUMN beschrijving VARCHAR(255) NULL AFTER naam;
-- ALTER TABLE gerechten ADD COLUMN menugroep ENUM('Frías','Calientes','Especialidades','Postres','Bebidas') NOT NULL DEFAULT 'Calientes' AFTER categorie;

-- Als je liever helemaal opnieuw begint met de officiële menukaart en
-- plattegrond (in plaats van kolommen toe te voegen aan je oude data),
-- is het eenvoudiger om de hele database te verwijderen en dit bestand
-- in zijn geheel opnieuw te importeren:
-- DROP DATABASE las_tapas;
-- (daarna dit hele .sql-bestand opnieuw importeren in phpMyAdmin)
