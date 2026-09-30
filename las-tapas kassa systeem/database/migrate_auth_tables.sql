USE las_tapas;

CREATE TABLE IF NOT EXISTS kassa_gebruikers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    naam VARCHAR(120) NOT NULL,
    wachtwoord_hash VARCHAR(255) NOT NULL,
    rol ENUM('beheerder', 'medewerker') NOT NULL DEFAULT 'medewerker',
    actief TINYINT(1) NOT NULL DEFAULT 1,
    aangemaakt_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kassa_wachtwoord_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gebruiker_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    verloopt_op DATETIME NOT NULL,
    gebruikt_op DATETIME NULL,
    FOREIGN KEY (gebruiker_id) REFERENCES kassa_gebruikers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO kassa_gebruikers (email, naam, wachtwoord_hash, rol)
VALUES ('admin@lastapas.nl', 'Beheerder', '$2y$10$zTfGEoiG2GiLQfbOiaXsnuhjmphBgPFkPHH3NHqVPUozIFOtKm4pS', 'beheerder');