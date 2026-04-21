USE winklar;

INSERT INTO users (id, username, email, password_hash, created_at) VALUES
(1, 'admin', 'admin@winklar.test', '$2y$10$abcdefghijklmnopqrstuv1234567890abcdefghijklmnopqr', '2026-01-05 08:00:00'),
(2, 'sandra', 'sandra@winklar.test', '$2y$10$bcdefghijklmnopqrstuvw1234567890abcdefghijklmnopqrs', '2026-01-06 09:15:00'),
(3, 'lukas', 'lukas@winklar.test', '$2y$10$cdefghijklmnopqrstuvwx1234567890abcdefghijklmnopqrst', '2026-01-07 10:30:00'),
(4, 'maria', 'maria@winklar.test', '$2y$10$defghijklmnopqrstuvwxy1234567890abcdefghijklmnopqrstu', '2026-01-08 11:45:00'),
(5, 'jonas', 'jonas@winklar.test', '$2y$10$efghijklmnopqrstuvwxyz1234567890abcdefghijklmnopqrstuv', '2026-01-09 13:00:00');

INSERT INTO plz (
    id, ortschaftsname, plz4, zusatzziffer, zip_id, gemeindename, bfs_nr,
    kantonskuerzel, adressenanteil, ist_eintrag_aktiv, sprache, validity_from, validity_to
) VALUES
(1, 'Zuerich', '8001', '00', 'ZIP800100', 'Zuerich', '261', 'ZH', 12.50, 1, 'de', '2020-01-01', NULL),
(2, 'Winterthur', '8400', '00', 'ZIP840000', 'Winterthur', '230', 'ZH', 8.20, 1, 'de', '2020-01-01', NULL),
(3, 'Bern', '3000', '01', 'ZIP300001', 'Bern', '351', 'BE', 10.10, 1, 'de', '2020-01-01', NULL),
(4, 'Luzern', '6003', '00', 'ZIP600300', 'Luzern', '1061', 'LU', 6.75, 1, 'de', '2020-01-01', NULL),
(5, 'St. Gallen', '9000', '00', 'ZIP900000', 'St. Gallen', '3203', 'SG', 5.90, 1, 'de', '2020-01-01', NULL);

INSERT INTO adressen (
    id, creator_adress_id, modifier_adress_id, anrede, firmen_anrede, nachname, vorname,
    zusatz, strasse, postfach, nation, plz_id, telefon, email, notiz, geburtsdatum,
    lizenz, passwort, created_by_user_id, created_at, updated_by_user_id, updated_at
) VALUES
(1, NULL, NULL, 'Herr', NULL, 'Muster', 'Max', NULL, 'Bahnhofstrasse 1', NULL, 'Schweiz', 1, '+41 44 111 11 11', 'max.muster@example.com', 'Vereinsmitglied', '1985-02-14', 'A-1001', 'pass-max', 1, '2026-01-10 08:00:00', 1, '2026-01-10 08:00:00'),
(2, NULL, NULL, 'Frau', NULL, 'Keller', 'Sandra', NULL, 'Marktgasse 12', NULL, 'Schweiz', 2, '+41 52 222 22 22', 'sandra.keller@example.com', 'Standaufsicht', '1990-06-08', 'B-1002', 'pass-sandra', 2, '2026-01-10 08:30:00', 2, '2026-01-10 08:30:00'),
(3, NULL, NULL, 'Herr', NULL, 'Meier', 'Lukas', NULL, 'Bundesplatz 7', NULL, 'Schweiz', 3, '+41 31 333 33 33', 'lukas.meier@example.com', 'Jungschuetze', '2001-11-22', 'C-1003', 'pass-lukas', 3, '2026-01-10 09:00:00', 3, '2026-01-10 09:00:00'),
(4, NULL, NULL, 'Frau', NULL, 'Schmid', 'Maria', NULL, 'Pilatusstrasse 5', 'Postfach 44', 'Schweiz', 4, '+41 41 444 44 44', 'maria.schmid@example.com', 'Gastschuetze', '1978-03-19', 'D-1004', 'pass-maria', 4, '2026-01-10 09:30:00', 4, '2026-01-10 09:30:00'),
(5, NULL, NULL, 'Herr', 'Sport AG', 'Baumann', 'Jonas', 'c/o Sport AG', 'Multergasse 9', NULL, 'Schweiz', 5, '+41 71 555 55 55', 'jonas.baumann@example.com', 'Sponsor', '1988-09-30', 'E-1005', 'pass-jonas', 5, '2026-01-10 10:00:00', 5, '2026-01-10 10:00:00');

INSERT INTO gaben (
    id, name, preis, created_by_user_id, created_at, updated_by_user_id, updated_at
) VALUES
(1, 'Kranzkarte 10 CHF', 10.00, 1, '2026-01-11 08:00:00', 1, '2026-01-11 08:00:00'),
(2, 'Gutschein 20 CHF', 20.00, 2, '2026-01-11 08:10:00', 2, '2026-01-11 08:10:00'),
(3, 'Naturalgabe Messer', 35.00, 3, '2026-01-11 08:20:00', 3, '2026-01-11 08:20:00'),
(4, 'Weinflasche', 18.50, 4, '2026-01-11 08:30:00', 4, '2026-01-11 08:30:00'),
(5, 'Sachpreis Rucksack', 49.90, 5, '2026-01-11 08:40:00', 5, '2026-01-11 08:40:00');

INSERT INTO anlass (
    id, fk_adress_id_creator, fk_adress_id_modifier, name_anlass, shortname_anlass,
    start_anlass, end_anlass, created_by_user_id, created_at, updated_by_user_id, updated_at
) VALUES
(1, 1, 1, 'Fruehlingsschiessen 2026', 'FS26', '2026-04-10', '2026-04-12', 1, '2026-01-12 08:00:00', 1, '2026-01-12 08:00:00'),
(2, 2, 2, 'Sommercup 2026', 'SC26', '2026-06-20', '2026-06-21', 2, '2026-01-12 08:15:00', 2, '2026-01-12 08:15:00'),
(3, 3, 3, 'Herbstmeisterschaft 2026', 'HM26', '2026-09-05', '2026-09-06', 3, '2026-01-12 08:30:00', 3, '2026-01-12 08:30:00'),
(4, 4, 4, 'Nachtschiessen 2026', 'NS26', '2026-10-17', '2026-10-17', 4, '2026-01-12 08:45:00', 4, '2026-01-12 08:45:00'),
(5, 5, 5, 'Jahresfinal 2026', 'JF26', '2026-11-14', '2026-11-15', 5, '2026-01-12 09:00:00', 5, '2026-01-12 09:00:00');

INSERT INTO stich (
    id, id_anlass, id_disziplin, name, short_name, anzeige_id, scheibe, wertigkeit,
    anzahl_schuss, anzahl_passen, preis, verbindung, created_by_user_id, created_at,
    updated_by_user_id, updated_at
) VALUES
(1, 1, 10, 'Vereinsstich', 'VST', 'A01', 'A10', 100.00, 10, 2, 18.00, 'Einzel', 1, '2026-01-13 08:00:00', 1, '2026-01-13 08:00:00'),
(2, 2, 20, 'Auszahlungsstich', 'AZS', 'A02', 'A100', 95.00, 6, 2, 22.00, 'Einzel', 2, '2026-01-13 08:15:00', 2, '2026-01-13 08:15:00'),
(3, 3, 30, 'Gruppenstich', 'GRP', 'A03', 'B4', 98.00, 8, 2, 20.00, 'Gruppe', 3, '2026-01-13 08:30:00', 3, '2026-01-13 08:30:00'),
(4, 4, 40, 'Nachtstich', 'NGT', 'A04', 'A5', 92.50, 5, 1, 15.00, 'Einzel', 4, '2026-01-13 08:45:00', 4, '2026-01-13 08:45:00'),
(5, 5, 50, 'Finalstich', 'FIN', 'A05', 'A10', 105.00, 10, 2, 25.00, 'Finale', 5, '2026-01-13 09:00:00', 5, '2026-01-13 09:00:00');

INSERT INTO auszeichnungslimiten (
    id, stich_id, gaben_id, min_wert, max_wert, min_alter, max_alter,
    created_by_user_id, created_at, updated_by_user_id, updated_at
) VALUES
(1, 1, 1, 85.00, 100.00, 18, 65, 1, '2026-01-14 08:00:00', 1, '2026-01-14 08:00:00'),
(2, 2, 2, 88.00, 100.00, 18, 70, 2, '2026-01-14 08:10:00', 2, '2026-01-14 08:10:00'),
(3, 3, 3, 90.00, 100.00, 16, 30, 3, '2026-01-14 08:20:00', 3, '2026-01-14 08:20:00'),
(4, 4, 4, 80.00, 95.00, 21, 75, 4, '2026-01-14 08:30:00', 4, '2026-01-14 08:30:00'),
(5, 5, 5, 92.00, 105.00, 18, 80, 5, '2026-01-14 08:40:00', 5, '2026-01-14 08:40:00');

INSERT INTO standblatt (
    id, id_anlass, id_adresse, datum, kosten, created_by_user_id, created_at,
    updated_by_user_id, updated_at
) VALUES
(1, 1, 1, '2026-04-10', 18.00, 1, '2026-02-01 08:00:00', 1, '2026-02-01 08:00:00'),
(2, 2, 2, '2026-06-20', 22.00, 2, '2026-02-01 08:15:00', 2, '2026-02-01 08:15:00'),
(3, 3, 3, '2026-09-05', 20.00, 3, '2026-02-01 08:30:00', 3, '2026-02-01 08:30:00'),
(4, 4, 4, '2026-10-17', 15.00, 4, '2026-02-01 08:45:00', 4, '2026-02-01 08:45:00'),
(5, 5, 5, '2026-11-14', 25.00, 5, '2026-02-01 09:00:00', 5, '2026-02-01 09:00:00');

INSERT INTO standblatt_stich (
    id, id_standblatt, id_stich, created_by_user_id, created_at, updated_by_user_id, updated_at
) VALUES
(1, 1, 1, 1, '2026-02-02 08:00:00', 1, '2026-02-02 08:00:00'),
(2, 2, 2, 2, '2026-02-02 08:10:00', 2, '2026-02-02 08:10:00'),
(3, 3, 3, 3, '2026-02-02 08:20:00', 3, '2026-02-02 08:20:00'),
(4, 4, 4, 4, '2026-02-02 08:30:00', 4, '2026-02-02 08:30:00'),
(5, 5, 5, 5, '2026-02-02 08:40:00', 5, '2026-02-02 08:40:00');

INSERT INTO gaben_abgaben (
    id, gaben_id, standblatt_id, created_by_user_id, created_at, updated_by_user_id, updated_at
) VALUES
(1, 1, 1, 1, '2026-02-03 08:00:00', 1, '2026-02-03 08:00:00'),
(2, 2, 2, 2, '2026-02-03 08:10:00', 2, '2026-02-03 08:10:00'),
(3, 3, 3, 3, '2026-02-03 08:20:00', 3, '2026-02-03 08:20:00'),
(4, 4, 4, 4, '2026-02-03 08:30:00', 4, '2026-02-03 08:30:00'),
(5, 5, 5, 5, '2026-02-03 08:40:00', 5, '2026-02-03 08:40:00');

INSERT INTO schussdaten (
    id, id_anlass, start_nr, primaerwertung, schussart, bahn_nr, sekundaerwertung, teiler,
    schuss_zeit, mouche, x_koordinate, y_koordinate, in_time, time_since_change,
    sweep_direction, demonstration, match_index, stich_index, ins_del, total_art, gruppe,
    feuerart, log_event, log_typ, zeit_seit_jahresanfang, abloesung, waffe, position,
    target_id, externe_nummer, created_by_user_id, created_at, updated_by_user_id, updated_at
) VALUES
(1, 1, '1001', 9.80, 'Probe', '1', 98.00, 45.30, '2026-04-10 09:00:00', 1, 0.1250, -0.2450, 1, 12, 'left', 0, 1, 1, 0, 'Total', 'Gruppe A', 'Einzelfeuer', 'SHOT', 'INFO', 8640000, 'Abl. 1', 'Stgw 90', 'stehend', 'T-001', 'EXT-1001', 1, '2026-02-04 08:00:00', 1, '2026-02-04 08:00:00'),
(2, 2, '1002', 9.50, 'Wertung', '2', 95.00, 62.10, '2026-06-20 10:15:00', 0, -0.3340, 0.1180, 1, 18, 'right', 0, 1, 2, 0, 'Total', 'Gruppe B', 'Serie', 'SHOT', 'INFO', 14774400, 'Abl. 2', 'Karabiner', 'kniend', 'T-002', 'EXT-1002', 2, '2026-02-04 08:10:00', 2, '2026-02-04 08:10:00'),
(3, 3, '1003', 10.20, 'Wertung', '3', 102.00, 21.75, '2026-09-05 11:30:00', 1, 0.0520, 0.0410, 1, 9, 'left', 0, 2, 3, 0, 'Total', 'Gruppe C', 'Serie', 'SHOT', 'INFO', 21340800, 'Abl. 3', 'Freigewehr', 'liegend', 'T-003', 'EXT-1003', 3, '2026-02-04 08:20:00', 3, '2026-02-04 08:20:00'),
(4, 4, '1004', 8.70, 'Final', '4', 87.00, 88.40, '2026-10-17 20:45:00', 0, -0.4410, -0.3890, 0, 25, 'right', 1, 2, 4, 0, 'Total', 'Gruppe D', 'Schnellfeuer', 'TIMEOUT', 'WARN', 25029900, 'Abl. 1', 'Pistole', 'stehend', 'T-004', 'EXT-1004', 4, '2026-02-04 08:30:00', 4, '2026-02-04 08:30:00'),
(5, 5, '1005', 10.50, 'Final', '5', 105.00, 10.05, '2026-11-14 14:05:00', 1, 0.0100, -0.0150, 1, 6, 'left', 0, 3, 5, 0, 'Total', 'Gruppe E', 'Einzelfeuer', 'SHOT', 'INFO', 27453900, 'Abl. 2', 'Stgw 57', 'stehend', 'T-005', 'EXT-1005', 5, '2026-02-04 08:40:00', 5, '2026-02-04 08:40:00');

INSERT INTO user_remember_tokens (
    id, user_id, selector, validator_hash, expires_at, created_by_user_id, created_at,
    updated_by_user_id, updated_at
) VALUES
(1, 1, 'sel000000000000000000001', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2026-12-31 23:59:59', 1, '2026-02-05 08:00:00', 1, '2026-02-05 08:00:00'),
(2, 2, 'sel000000000000000000002', 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', '2026-12-31 23:59:59', 2, '2026-02-05 08:10:00', 2, '2026-02-05 08:10:00'),
(3, 3, 'sel000000000000000000003', 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc', '2026-12-31 23:59:59', 3, '2026-02-05 08:20:00', 3, '2026-02-05 08:20:00'),
(4, 4, 'sel000000000000000000004', 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd', '2026-12-31 23:59:59', 4, '2026-02-05 08:30:00', 4, '2026-02-05 08:30:00'),
(5, 5, 'sel000000000000000000005', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', '2026-12-31 23:59:59', 5, '2026-02-05 08:40:00', 5, '2026-02-05 08:40:00');
