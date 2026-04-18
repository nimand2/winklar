CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_remember_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    selector CHAR(24) NOT NULL UNIQUE,
    validator_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_remember_tokens_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
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
