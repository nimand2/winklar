ALTER TABLE standblatt
    ADD COLUMN final_teilnahme TINYINT(1) NOT NULL DEFAULT 0 AFTER gaben_geprueft;
