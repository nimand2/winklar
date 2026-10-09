ALTER TABLE anlass
    ADD COLUMN final_stich_id INT UNSIGNED NULL AFTER end_anlass,
    ADD COLUMN final_anzahl_u18 INT UNSIGNED NOT NULL DEFAULT 6 AFTER final_stich_id,
    ADD COLUMN final_anzahl_ue18 INT UNSIGNED NOT NULL DEFAULT 6 AFTER final_anzahl_u18;

-- Bisherige Regel fuer vorhandene Anlaesse uebernehmen.
UPDATE anlass a
SET final_stich_id = (SELECT MIN(s.id) FROM stich s
    WHERE s.id_anlass = a.id AND LOWER(TRIM(s.name)) = 'chilbistich');
