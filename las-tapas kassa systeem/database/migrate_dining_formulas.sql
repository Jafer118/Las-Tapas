USE las_tapas;

ALTER TABLE bestellingen
    ADD COLUMN formule_code VARCHAR(32) NULL AFTER aantal_personen,
    ADD COLUMN formule_naam VARCHAR(100) NULL AFTER formule_code,
    ADD COLUMN formule_prijs DECIMAL(8,2) NULL AFTER formule_naam;
