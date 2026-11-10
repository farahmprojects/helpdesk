-- ===========================================================================
-- Helpdesk de cours — script de création des tables
-- ===========================================================================

-- Table des utilisateurs (étudiants et tuteurs)
CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL CHECK (role IN ('etudiant', 'tuteur'))
);

-- Table des tickets
CREATE TABLE IF NOT EXISTS tickets (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    author_id   INTEGER NOT NULL,
    title       TEXT NOT NULL,
    description TEXT NOT NULL,
    category    TEXT NOT NULL CHECK (category IN ('Cours', 'TD', 'TP')),
    priority    TEXT NOT NULL CHECK (priority IN ('Basse', 'Moyenne', 'Haute')),
    status      TEXT NOT NULL DEFAULT 'Ouvert'
                CHECK (status IN ('Ouvert', 'En cours', 'Résolu')),
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Table des commentaires
CREATE TABLE IF NOT EXISTS comments (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id  INTEGER NOT NULL,
    author_id  INTEGER NOT NULL,
    message    TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Comptes par défaut.
-- Le mot de passe en clair est 'motdepasse' pour les trois comptes.
-- Le hash ci-dessous est généré avec password_hash('motdepasse', PASSWORD_DEFAULT).
-- L'application crée elle-même ces comptes au premier démarrage.
-- INSERT INTO users (username, password_hash, role) VALUES
--   ('etudiant1', '<hash>', 'etudiant'),
--   ('etudiant2', '<hash>', 'etudiant'),
--   ('tuteur',    '<hash>', 'tuteur');
