CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE plz (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ortschaftsname VARCHAR(150) NOT NULL,
    plz4 CHAR(4) NOT NULL,
    zusatzziffer VARCHAR(10) NULL,
    zip_id VARCHAR(20) NULL,
    gemeindename VARCHAR(150) NULL,
    bfs_nr VARCHAR(20) NULL,
    kantonskuerzel VARCHAR(10) NULL,
    adressenanteil DECIMAL(5,2) NULL,
    ist_eintrag_aktiv TINYINT(1) NOT NULL DEFAULT 1,
    sprache VARCHAR(10) NULL,
    validity_from DATE NULL,
    validity_to DATE NULL
);

CREATE TABLE adressen (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    creator_adress_id INT UNSIGNED NULL,
    modifier_adress_id INT UNSIGNED NULL,
    anrede VARCHAR(50) NULL,
    firmen_anrede VARCHAR(100) NULL,
    nachname VARCHAR(150) NOT NULL,
    vorname VARCHAR(150) NULL,
    zusatz VARCHAR(150) NULL,
    strasse VARCHAR(190) NULL,
    postfach VARCHAR(100) NULL,
    nation VARCHAR(100) NULL,
    plz_id INT UNSIGNED NULL,
    telefon VARCHAR(50) NULL,
    email VARCHAR(190) NULL,
    notiz TEXT NULL,
    geburtsdatum DATE NULL,
    lizenz VARCHAR(100) NULL,
    passwort VARCHAR(255) NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_adressen_creator_adress
        FOREIGN KEY (creator_adress_id) REFERENCES adressen(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_adressen_modifier_adress
        FOREIGN KEY (modifier_adress_id) REFERENCES adressen(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_adressen_plz
        FOREIGN KEY (plz_id) REFERENCES plz(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_adressen_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_adressen_updated_by
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE gaben (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    preis DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gaben_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_gaben_updated_by
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE anlass (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fk_adress_id_creator INT UNSIGNED NULL,
    fk_adress_id_modifier INT UNSIGNED NULL,
    name_anlass VARCHAR(190) NOT NULL,
    shortname_anlass VARCHAR(100) NULL,
    start_anlass DATE NULL,
    end_anlass DATE NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_anlass_adress_creator
        FOREIGN KEY (fk_adress_id_creator) REFERENCES adressen(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_anlass_adress_modifier
        FOREIGN KEY (fk_adress_id_modifier) REFERENCES adressen(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_anlass_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_anlass_updated_by
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE stich (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_anlass INT UNSIGNED NOT NULL,
    id_disziplin INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    short_name VARCHAR(50) NULL,
    anzeige_id VARCHAR(50) NULL,
    scheibe VARCHAR(50) NULL,
    wertigkeit DECIMAL(10,2) NULL,
    anzahl_schuss SMALLINT UNSIGNED NULL,
    anzahl_passen SMALLINT UNSIGNED NULL,
    preis DECIMAL(10,2) NULL,
    verbindung VARCHAR(100) NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_stich_anlass
        FOREIGN KEY (id_anlass) REFERENCES anlass(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_stich_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_stich_updated_by
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE auszeichnungslimiten (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stich_id INT UNSIGNED NOT NULL,
    gaben_id INT UNSIGNED NULL,
    min_wert DECIMAL(10,2) NULL,
    max_wert DECIMAL(10,2) NULL,
    min_alter SMALLINT UNSIGNED NULL,
    max_alter SMALLINT UNSIGNED NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_auszeichnungslimiten_stich
        FOREIGN KEY (stich_id) REFERENCES stich(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_auszeichnungslimiten_gaben
        FOREIGN KEY (gaben_id) REFERENCES gaben(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_auszeichnungslimiten_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_auszeichnungslimiten_updated_by
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE standblatt (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_anlass INT UNSIGNED NOT NULL,
    id_adresse INT UNSIGNED NOT NULL,
    datum DATE NULL,
    kosten DECIMAL(10,2) NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_standblatt_anlass
        FOREIGN KEY (id_anlass) REFERENCES anlass(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_standblatt_adresse
        FOREIGN KEY (id_adresse) REFERENCES adressen(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_standblatt_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_standblatt_updated_by
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE standblatt_stich (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_standblatt INT UNSIGNED NOT NULL,
    id_stich INT UNSIGNED NOT NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_standblatt_stich_standblatt
        FOREIGN KEY (id_standblatt) REFERENCES standblatt(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_standblatt_stich_stich
        FOREIGN KEY (id_stich) REFERENCES stich(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_standblatt_stich_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_standblatt_stich_updated_by
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_standblatt_stich (id_standblatt, id_stich)
);

CREATE TABLE gaben_abgaben (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gaben_id INT UNSIGNED NOT NULL,
    standblatt_id INT UNSIGNED NOT NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gaben_abgaben_gaben
        FOREIGN KEY (gaben_id) REFERENCES gaben(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_gaben_abgaben_standblatt
        FOREIGN KEY (standblatt_id) REFERENCES standblatt(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_gaben_abgaben_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_gaben_abgaben_updated_by
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    UNIQUE KEY uq_gaben_abgaben (gaben_id, standblatt_id)
);

CREATE TABLE schussdaten (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_anlass INT UNSIGNED NOT NULL,
    start_nr VARCHAR(50) NULL,
    primaerwertung DECIMAL(10,2) NULL,
    schussart VARCHAR(50) NULL,
    bahn_nr VARCHAR(50) NULL,
    sekundaerwertung DECIMAL(10,2) NULL,
    teiler DECIMAL(10,2) NULL,
    schuss_zeit DATETIME NULL,
    mouche TINYINT(1) NOT NULL DEFAULT 0,
    x_koordinate DECIMAL(10,4) NULL,
    y_koordinate DECIMAL(10,4) NULL,
    in_time TINYINT(1) NOT NULL DEFAULT 1,
    time_since_change INT UNSIGNED NULL,
    sweep_direction VARCHAR(20) NULL,
    demonstration TINYINT(1) NOT NULL DEFAULT 0,
    match_index INT UNSIGNED NULL,
    stich_index INT UNSIGNED NULL,
    ins_del TINYINT(1) NOT NULL DEFAULT 0,
    total_art VARCHAR(50) NULL,
    gruppe VARCHAR(100) NULL,
    feuerart VARCHAR(50) NULL,
    log_event VARCHAR(100) NULL,
    log_typ VARCHAR(50) NULL,
    zeit_seit_jahresanfang INT UNSIGNED NULL,
    abloesung VARCHAR(50) NULL,
    waffe VARCHAR(100) NULL,
    position VARCHAR(50) NULL,
    target_id VARCHAR(100) NULL,
    externe_nummer VARCHAR(100) NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_schussdaten_anlass
        FOREIGN KEY (id_anlass) REFERENCES anlass(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_schussdaten_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_schussdaten_updated_by
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE user_remember_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    selector CHAR(24) NOT NULL UNIQUE,
    validator_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_by_user_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by_user_id INT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_remember_tokens_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_user_remember_tokens_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_user_remember_tokens_updated_by
        FOREIGN KEY (updated_by_user_id) REFERENCES users(id)
        ON DELETE SET NULL,
    INDEX idx_user_remember_tokens_user_id (user_id),
    INDEX idx_user_remember_tokens_expires_at (expires_at)
);

-- Beispiel-Benutzer anlegen:
-- Das Passwort muss vorher mit password_hash() erzeugt werden.
-- Beispiel in PHP:
-- echo password_hash('MeinSicheresPasswort123', PASSWORD_DEFAULT);
--
-- INSERT INTO users (username, email, password_hash)
-- VALUES ('max', 'max@example.com', '$2y$10$...');
