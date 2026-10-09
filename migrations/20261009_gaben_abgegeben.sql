-- Bestehende Auswahlen bleiben als erste Serie erhalten; die Ausgabe ist unbestaetigt.
ALTER TABLE gaben_abgaben
    ADD COLUMN serie_nummer INT UNSIGNED NOT NULL DEFAULT 1 AFTER stich_id,
    ADD COLUMN abgegeben TINYINT(1) NOT NULL DEFAULT 0 AFTER serie_nummer,
    DROP INDEX uq_gaben_abgaben,
    ADD UNIQUE KEY uq_gaben_abgaben (gaben_id, standblatt_id, stich_id, serie_nummer);
