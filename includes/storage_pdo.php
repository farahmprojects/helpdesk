<?php
/**
 * Implémentation du stockage en base SQLite via PDO (Option 2 - bonus).
 *
 * Toutes les requêtes utilisent des requêtes préparées pour éviter
 * l'injection SQL.
 */

/**
 * Renvoie l'instance PDO unique pour la durée de la requête.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        if (!is_dir(DATA_DIR)) {
            mkdir(DATA_DIR, 0755, true);
        }
        $pdo = new PDO('sqlite:' . SQLITE_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}

/**
 * Crée les tables et insère les utilisateurs par défaut au premier accès.
 */
function storage_init(): void {
    $pdo = db();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role          TEXT NOT NULL CHECK (role IN ('etudiant', 'tuteur'))
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tickets (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            author_id   INTEGER NOT NULL,
            title       TEXT NOT NULL,
            description TEXT NOT NULL,
            category    TEXT NOT NULL CHECK (category IN ('Cours', 'TD', 'TP')),
            priority    TEXT NOT NULL CHECK (priority IN ('Basse', 'Moyenne', 'Haute')),
            status      TEXT NOT NULL DEFAULT 'Ouvert' CHECK (status IN ('Ouvert', 'En cours', 'Résolu')),
            created_at  TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (author_id) REFERENCES users(id)
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_id  INTEGER NOT NULL,
            author_id  INTEGER NOT NULL,
            message    TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (author_id) REFERENCES users(id)
        )
    ");

    // Comptes par défaut si la table users est vide
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)'
        );
        $hash = password_hash('motdepasse', PASSWORD_DEFAULT);
        $stmt->execute(['etudiant1', $hash, 'etudiant']);
        $stmt->execute(['etudiant2', $hash, 'etudiant']);
        $stmt->execute(['tuteur',    $hash, 'tuteur']);
    }
}

// ----- Utilisateurs ---------------------------------------------------------

function find_user_by_username(string $username): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function find_user_by_id(int $id): ?array {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Crée un nouvel utilisateur étudiant.
 * Renvoie l'identifiant créé.
 * Le rôle est volontairement forcé à 'etudiant' : on ne permet pas
 * de créer un tuteur depuis le formulaire d'inscription public.
 */
function create_user(string $username, string $password): int {
    $stmt = db()->prepare(
        'INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)'
    );
    $stmt->execute([
        $username,
        password_hash($password, PASSWORD_DEFAULT),
        'etudiant',
    ]);
    return (int)db()->lastInsertId();
}

// ----- Tickets --------------------------------------------------------------

function get_all_tickets(): array {
    $stmt = db()->query('SELECT * FROM tickets ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function get_tickets_for_user(int $userId): array {
    $stmt = db()->prepare(
        'SELECT * FROM tickets WHERE author_id = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function find_ticket(int $id): ?array {
    $stmt = db()->prepare('SELECT * FROM tickets WHERE id = ?');
    $stmt->execute([$id]);
    $ticket = $stmt->fetch();
    return $ticket ?: null;
}

function create_ticket(int $authorId, string $title, string $description, string $category, string $priority): int {
    $stmt = db()->prepare(
        'INSERT INTO tickets (author_id, title, description, category, priority, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $authorId, $title, $description, $category, $priority,
        'Ouvert', date('Y-m-d H:i:s'),
    ]);
    return (int)db()->lastInsertId();
}

function update_ticket_status(int $ticketId, string $status): bool {
    $stmt = db()->prepare('UPDATE tickets SET status = ? WHERE id = ?');
    $stmt->execute([$status, $ticketId]);
    return $stmt->rowCount() > 0;
}

// ----- Commentaires ---------------------------------------------------------

function get_comments_for_ticket(int $ticketId): array {
    $stmt = db()->prepare(
        'SELECT * FROM comments WHERE ticket_id = ? ORDER BY created_at ASC'
    );
    $stmt->execute([$ticketId]);
    return $stmt->fetchAll();
}

function add_comment(int $ticketId, int $authorId, string $message): int {
    $stmt = db()->prepare(
        'INSERT INTO comments (ticket_id, author_id, message, created_at)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$ticketId, $authorId, $message, date('Y-m-d H:i:s')]);
    return (int)db()->lastInsertId();
}
