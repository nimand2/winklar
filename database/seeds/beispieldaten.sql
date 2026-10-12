-- Nur Beispieldaten; vorhandene IDs führen zum Abbruch ohne Teilimport.
USE winklar;
START TRANSACTION;

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
(1, NULL, NULL, 'Herr', NULL, 'Muster', 'Max', NULL, 'Bahnhofstrasse 1', NULL, 'Schweiz', 1, '+41 44 111 11 11', 'max.muster@example.com', 'Vereinsmitglied', '1985-02-14', 'A-1001', NULL, 1, '2026-01-10 08:00:00', 1, '2026-01-10 08:00:00'),
(2, NULL, NULL, 'Frau', NULL, 'Keller', 'Sandra', NULL, 'Marktgasse 12', NULL, 'Schweiz', 2, '+41 52 222 22 22', 'sandra.keller@example.com', 'Standaufsicht', '1990-06-08', 'B-1002', NULL, 2, '2026-01-10 08:30:00', 2, '2026-01-10 08:30:00'),
(3, NULL, NULL, 'Herr', NULL, 'Meier', 'Lukas', NULL, 'Bundesplatz 7', NULL, 'Schweiz', 3, '+41 31 333 33 33', 'lukas.meier@example.com', 'Jungschuetze', '2001-11-22', 'C-1003', NULL, 3, '2026-01-10 09:00:00', 3, '2026-01-10 09:00:00'),
(4, NULL, NULL, 'Frau', NULL, 'Schmid', 'Maria', NULL, 'Pilatusstrasse 5', 'Postfach 44', 'Schweiz', 4, '+41 41 444 44 44', 'maria.schmid@example.com', 'Gastschuetze', '1978-03-19', 'D-1004', NULL, 4, '2026-01-10 09:30:00', 4, '2026-01-10 09:30:00'),
(5, NULL, NULL, 'Herr', 'Sport AG', 'Baumann', 'Jonas', 'c/o Sport AG', 'Multergasse 9', NULL, 'Schweiz', 5, '+41 71 555 55 55', 'jonas.baumann@example.com', 'Sponsor', '1988-09-30', 'E-1005', NULL, 5, '2026-01-10 10:00:00', 5, '2026-01-10 10:00:00');

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
(1, 1, 10, 'Vereinsstich', 'VST', '901', 'A10', 100.00, 10, 2, 18.00, 'Einzel', 1, '2026-01-13 08:00:00', 1, '2026-01-13 08:00:00'),
(2, 2, 20, 'Auszahlungsstich', 'AZS', '902', 'A100', 95.00, 6, 2, 22.00, 'Einzel', 2, '2026-01-13 08:15:00', 2, '2026-01-13 08:15:00'),
(3, 3, 30, 'Gruppenstich', 'GRP', '903', 'B4', 98.00, 8, 2, 20.00, 'Gruppe', 3, '2026-01-13 08:30:00', 3, '2026-01-13 08:30:00'),
(4, 4, 40, 'Nachtstich', 'NGT', '904', 'A5', 92.50, 5, 1, 15.00, 'Einzel', 4, '2026-01-13 08:45:00', 4, '2026-01-13 08:45:00'),
(5, 5, 50, 'Finalstich', 'FIN', '905', 'A10', 105.00, 10, 2, 25.00, 'Finale', 5, '2026-01-13 09:00:00', 5, '2026-01-13 09:00:00');

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
    id, id_standblatt, id_stich, anzahl_stiche, preis, kosten, preis_quelle, created_by_user_id, created_at, updated_by_user_id, updated_at
) VALUES
(1, 1, 1, 1, 18.00, 18.00, 'erfasst', 1, '2026-02-02 08:00:00', 1, '2026-02-02 08:00:00'),
(2, 2, 2, 1, 22.00, 22.00, 'erfasst', 2, '2026-02-02 08:10:00', 2, '2026-02-02 08:10:00'),
(3, 3, 3, 1, 20.00, 20.00, 'erfasst', 3, '2026-02-02 08:20:00', 3, '2026-02-02 08:20:00'),
(4, 4, 4, 1, 15.00, 15.00, 'erfasst', 4, '2026-02-02 08:30:00', 4, '2026-02-02 08:30:00'),
(5, 5, 5, 1, 25.00, 25.00, 'erfasst', 5, '2026-02-02 08:40:00', 5, '2026-02-02 08:40:00');

INSERT INTO gaben_abgaben (
    id, gaben_id, standblatt_id, stich_id, name_snapshot, preis_snapshot, punktwert_snapshot, anzahl_snapshot, ist_geld_snapshot, stich_name_snapshot, created_by_user_id, created_at, updated_by_user_id, updated_at
) VALUES
(1, 1, 1, 1, 'Kranzkarte 10 CHF', 10.00, 0, 0, 0, 'Vereinsstich', 1, '2026-02-03 08:00:00', 1, '2026-02-03 08:00:00'),
(2, 2, 2, 2, 'Gutschein 20 CHF', 20.00, 0, 0, 0, 'Auszahlungsstich', 2, '2026-02-03 08:10:00', 2, '2026-02-03 08:10:00'),
(3, 3, 3, 3, 'Naturalgabe Messer', 35.00, 0, 0, 0, 'Gruppenstich', 3, '2026-02-03 08:20:00', 3, '2026-02-03 08:20:00'),
(4, 4, 4, 4, 'Weinflasche', 18.50, 0, 0, 0, 'Nachtstich', 4, '2026-02-03 08:30:00', 4, '2026-02-03 08:30:00'),
(5, 5, 5, 5, 'Sachpreis Rucksack', 49.90, 0, 0, 0, 'Finalstich', 5, '2026-02-03 08:40:00', 5, '2026-02-03 08:40:00');

INSERT INTO schussdaten (id_anlass,start_nr,primaerwertung,schussart,bahn_nr,schuss_zeit,match_index,stich_index,ins_del,log_typ,externe_nummer,import_hash,created_by_user_id,updated_by_user_id) VALUES
(1,'1',9,'1','1','2026-04-10 10:00:01',1,1,0,'3','901','f7d1085972c80c395a1e0aacbf2b37cfd093a453c4ae8d73a39451f3a39bf946',1,1),
(1,'1',9,'1','1','2026-04-10 10:00:02',2,1,0,'3','901','c5a2f02850abe08a55605a0cd732aafc584ff39076923c1c48f2236f14e384c9',1,1),
(1,'1',9,'1','1','2026-04-10 10:00:03',3,1,0,'3','901','4be2f044794a2c790d9e08e676e4511c7e32c1c3827f3b4262f9a2a79bde9ce2',1,1),
(1,'1',9,'1','1','2026-04-10 10:00:04',4,1,0,'3','901','2a020c9f71813d68d5a0526c17bd12edfa68cab708662225a5a113e3255dc7cb',1,1),
(1,'1',9,'1','1','2026-04-10 10:00:05',5,1,0,'3','901','cd42d8a7684638e4d3fa50a0ec52b4d65197e7c7fc7c6189edcf35dd9b4d4386',1,1),
(1,'1',9,'1','1','2026-04-10 10:00:06',6,1,0,'3','901','29ed863dc849e4026110a0df6125d542c483144be837ecf82016f1aa75e39bae',1,1),
(1,'1',9,'1','1','2026-04-10 10:00:07',7,1,0,'3','901','5dc0c01e222d0e065557f1eee77ea02e540bf34ff29c15d5293c04fef52a8ecf',1,1),
(1,'1',9,'1','1','2026-04-10 10:00:08',8,1,0,'3','901','297dc03d4ef8c9adebba121ec87850e63089a8db467373c23affb1c43590fc55',1,1),
(1,'1',9,'1','1','2026-04-10 10:00:09',9,1,0,'3','901','e31e406cc8fa6d82f39db1d14758d569f2638a44119a3adfea54bdd9a609c178',1,1),
(1,'1',9,'1','1','2026-04-10 10:00:10',10,1,0,'3','901','9d093c857077e40c92fcea1e4b0c8ac4b6952f1853564f39878c7fbe6aa12c89',1,1),
(2,'2',15,'1','2','2026-06-20 10:00:01',1,1,0,'3','902','069f8a7bc495db5aba4f69007be9fbc0e64ad79258aa1daf8a511e45b658ed98',2,2),
(2,'2',15,'1','2','2026-06-20 10:00:02',2,1,0,'3','902','ed377761719908098fbdbebb38414d92fc38db273d4870c9f8f3a17105c8dc1f',2,2),
(2,'2',15,'1','2','2026-06-20 10:00:03',3,1,0,'3','902','3206a13933d530b48fc110393821028cbc4ae84b657239cdf8b1a11c34069297',2,2),
(2,'2',15,'1','2','2026-06-20 10:00:04',4,1,0,'3','902','223fe64406bd588e8d046e1759b361b34cbd58bbccb86efdc8ca1483dee0bd2c',2,2),
(2,'2',15,'1','2','2026-06-20 10:00:05',5,1,0,'3','902','6fc091ab0e3ead344b3e7b87f11b37920f26a2e1b3f7ac0e2363de632cf13026',2,2),
(2,'2',15,'1','2','2026-06-20 10:00:06',6,1,0,'3','902','725cbce9bbcebd3af1aad2ff96fb5ee2f43fbf3fd90131ce05af38d0fca995ae',2,2),
(3,'3',12,'1','3','2026-09-05 10:00:01',1,1,0,'3','903','a2fb9bd0a019025135752672ce9c1778ced7481d4dcc214e50dd1e5cc50ad78d',3,3),
(3,'3',12,'1','3','2026-09-05 10:00:02',2,1,0,'3','903','965ccf407ea6757bfe362289688a2b0ffc7bbce00fb084862389cee36e6d70d1',3,3),
(3,'3',12,'1','3','2026-09-05 10:00:03',3,1,0,'3','903','de1ae1f6a6e47df646540c354572f8631d829f4c66e942c17a97280b18a8c948',3,3),
(3,'3',12,'1','3','2026-09-05 10:00:04',4,1,0,'3','903','6ff8670e24f581011d0ff9c99807ec9d62806980335e7207ba12a4c8cecf5203',3,3),
(3,'3',12,'1','3','2026-09-05 10:00:05',5,1,0,'3','903','950d57bdecf6a71904cf9331d16560743b08c2b96c3283a7377d8df589f15f45',3,3),
(3,'3',12,'1','3','2026-09-05 10:00:06',6,1,0,'3','903','edfb489cbe596812b02702a380e88f92a5a122b05f0204499b715941f258d697',3,3),
(3,'3',12,'1','3','2026-09-05 10:00:07',7,1,0,'3','903','9f4431b617349557093742219e2e3f4bd07ab58b5d5838b8eaeec724d971b226',3,3),
(3,'3',12,'1','3','2026-09-05 10:00:08',8,1,0,'3','903','5fcb4391d655e0e656d06030e32cd846c79314949e435af16219e671cd230334',3,3),
(4,'4',17,'1','4','2026-10-17 10:00:01',1,1,0,'3','904','3bb3f1713c30f3a88e571a5aed77db7fe975a02d26586f59860b0601af8ce1e6',4,4),
(4,'4',17,'1','4','2026-10-17 10:00:02',2,1,0,'3','904','fe9391c5b97a58f8f754b018c2b0de7ce133dcc1bbe04ab60f0c6ed1e1970163',4,4),
(4,'4',17,'1','4','2026-10-17 10:00:03',3,1,0,'3','904','6ab28169c0ac67b89cd4b3936f6f8df7d3496c3c795313d4eb2881fd21e05a49',4,4),
(4,'4',17,'1','4','2026-10-17 10:00:04',4,1,0,'3','904','f71dc74cf912e3c30a1400b31f6b2205ecf4f0d3611707cc8db50c4b1adb320a',4,4),
(4,'4',17,'1','4','2026-10-17 10:00:05',5,1,0,'3','904','6e0f4719a5da5cfa922e886a2942b4bbe9d98147fbad5e0acdf554ba6a0b1426',4,4),
(5,'5',10,'1','5','2026-11-14 10:00:01',1,1,0,'3','905','482ee7453d756d7b850cf7e49cf207fb9dd4ec028d9c58a6e9dd3323f9c7392a',5,5),
(5,'5',10,'1','5','2026-11-14 10:00:02',2,1,0,'3','905','d92946acf27c04237bd57377bc9e1be47151a2a1a203ad96783098f9ed16a4dd',5,5),
(5,'5',10,'1','5','2026-11-14 10:00:03',3,1,0,'3','905','2c05158ca1803d7d4e994f014623f0bbe3c20886a881fd8c306e4ce7520ed017',5,5),
(5,'5',10,'1','5','2026-11-14 10:00:04',4,1,0,'3','905','d1a4cb7f991c2eb0eefd8b7754692da89d2d6279208ab58bd25bbdbff93d9246',5,5),
(5,'5',10,'1','5','2026-11-14 10:00:05',5,1,0,'3','905','9b20f41e1c308d5ed9330b8596d306145a131ae2793e815c1df9284d8f327ab1',5,5),
(5,'5',10,'1','5','2026-11-14 10:00:06',6,1,0,'3','905','89e9ae6ab7c869ff22c1c67e97f0fcc8ad22a29940e8810ba75cb73ea56adb7a',5,5),
(5,'5',10,'1','5','2026-11-14 10:00:07',7,1,0,'3','905','484d6bd869aef2210b2d07a5c3521daf4344804e2802729429ee3ffc2f72008b',5,5),
(5,'5',10,'1','5','2026-11-14 10:00:08',8,1,0,'3','905','a81d2b096e63657cca6d07b61e4c9e2eead2e03970371a8c1985aada74e6ed85',5,5),
(5,'5',10,'1','5','2026-11-14 10:00:09',9,1,0,'3','905','b5533fb0512f55cba9f4aff7ec81767080d5675cf57de2562f9cb65a82280e47',5,5),
(5,'5',10,'1','5','2026-11-14 10:00:10',10,1,0,'3','905','fa514297766588e4d970ae7f00c477db7a347fa7e148bacd8d23147c32aa3e43',5,5);

INSERT INTO user_remember_tokens (
    id, user_id, selector, validator_hash, expires_at, created_by_user_id, created_at,
    updated_by_user_id, updated_at
) VALUES
(1, 1, 'sel000000000000000000001', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', '2026-12-31 23:59:59', 1, '2026-02-05 08:00:00', 1, '2026-02-05 08:00:00'),
(2, 2, 'sel000000000000000000002', 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', '2026-12-31 23:59:59', 2, '2026-02-05 08:10:00', 2, '2026-02-05 08:10:00'),
(3, 3, 'sel000000000000000000003', 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc', '2026-12-31 23:59:59', 3, '2026-02-05 08:20:00', 3, '2026-02-05 08:20:00'),
(4, 4, 'sel000000000000000000004', 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd', '2026-12-31 23:59:59', 4, '2026-02-05 08:30:00', 4, '2026-02-05 08:30:00'),
(5, 5, 'sel000000000000000000005', 'eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', '2026-12-31 23:59:59', 5, '2026-02-05 08:40:00', 5, '2026-02-05 08:40:00');

COMMIT;
