USE las_tapas;

DROP TRIGGER IF EXISTS sync_voorraad_naar_voorraad_items;
DROP TRIGGER IF EXISTS sync_voorraad_items_naar_gerechten;

ALTER TABLE voorraad_items ENGINE = InnoDB;
