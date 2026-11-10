<?php
/**
 * Implémentation du stockage en fichiers JSON (Option 1).
 *
 * Chaque entité est stockée dans un fichier JSON dans /data.
 * On utilise flock() pour éviter les corruptions en cas d'écritures
 * concurrentes.
 */

/**
 * Lit un fichier JSON et renvoie son contenu décodé.
 * Renvoie [] si le fichier n'existe pas.
 */
function json_read(string $file): array {
    if (!file_exists($file)) {
        return [];
    }
    $fp = fopen($file, 'r');
    if (!$fp) {
        return [];
    }
    flock($fp, LOCK_SH);
    $contents = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    if ($contents === false || $contents === '') {
        return [];
    }
    $data = json_decode($contents, true);
    return is_array($data) ? $data : [];
}

/**
 * Écrit un tableau dans un fichier JSON de façon atomique.
 */
function json_write(string $file, array $data): void {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $fp = fopen($file, 'c+');
    if (!$fp) {
        throw new RuntimeException("Impossible d'ouvrir $file");
    }
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * Renvoie le prochain identifiant à utiliser dans un tableau d'éléments.
 */
function json_next_id(array $items): int {
    $max = 0;
    foreach ($items as $item) {
        if (isset($item['id']) && (int)$item['id'] > $max) {
            $max = (int)$item['id'];
        }
    }
    return $max + 1;
}

/**
 * Initialise les fichiers JSON et crée les comptes par défaut si besoin.
 */
function storage_init(): void {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    if (!file_exists(USERS_FILE)) {
        $users = [
            [
                'id' => 1,
                'username' => 'etudiant1',
                'password_hash' => password_hash('motdepasse', PASSWORD_DEFAULT),
                'role' => 'etudiant',
            ],
            [
                'id' => 2,
                'username' => 'etudiant2',
                'password_hash' => password_hash('motdepasse', PASSWORD_DEFAULT),
                'role' => 'etudiant',
            ],
            [
                'id' => 3,
                'username' => 'tuteur',
                'password_hash' => password_hash('motdepasse', PASSWORD_DEFAULT),
                'role' => 'tuteur',
            ],
        ];
        json_write(USERS_FILE, $users);
    }
    if (!file_exists(TICKETS_FILE)) {
        json_write(TICKETS_FILE, []);
    }
    if (!file_exists(COMMENTS_FILE)) {
        json_write(COMMENTS_FILE, []);
    }
}

// ----- Utilisateurs ---------------------------------------------------------

function find_user_by_username(string $username): ?array {
    foreach (json_read(USERS_FILE) as $user) {
        if ($user['username'] === $username) {
            return $user;
        }
    }
    return null;
}

function find_user_by_id(int $id): ?array {
    foreach (json_read(USERS_FILE) as $user) {
        if ((int)$user['id'] === $id) {
            return $user;
        }
    }
    return null;
}

/**
 * Crée un nouvel utilisateur étudiant.
 * Renvoie l'identifiant créé.
 * Le rôle est volontairement forcé à 'etudiant' : on ne permet pas
 * de créer un tuteur depuis le formulaire d'inscription public.
 */
function create_user(string $username, string $password): int {
    $users = json_read(USERS_FILE);
    $id = json_next_id($users);
    $users[] = [
        'id'            => $id,
        'username'      => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role'          => 'etudiant',
    ];
    json_write(USERS_FILE, $users);
    return $id;
}

// ----- Tickets --------------------------------------------------------------

function get_all_tickets(): array {
    $tickets = json_read(TICKETS_FILE);
    // Trier par date de création décroissante
    usort($tickets, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
    return $tickets;
}

function get_tickets_for_user(int $userId): array {
    $tickets = array_filter(
        json_read(TICKETS_FILE),
        fn($t) => (int)$t['author_id'] === $userId
    );
    usort($tickets, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
    return array_values($tickets);
}

function find_ticket(int $id): ?array {
    foreach (json_read(TICKETS_FILE) as $ticket) {
        if ((int)$ticket['id'] === $id) {
            return $ticket;
        }
    }
    return null;
}

function create_ticket(int $authorId, string $title, string $description, string $category, string $priority): int {
    $tickets = json_read(TICKETS_FILE);
    $id = json_next_id($tickets);
    $tickets[] = [
        'id'          => $id,
        'author_id'   => $authorId,
        'title'       => $title,
        'description' => $description,
        'category'    => $category,
        'priority'    => $priority,
        'status'      => 'Ouvert',
        'created_at'  => date('Y-m-d H:i:s'),
    ];
    json_write(TICKETS_FILE, $tickets);
    return $id;
}

function update_ticket_status(int $ticketId, string $status): bool {
    $tickets = json_read(TICKETS_FILE);
    foreach ($tickets as &$ticket) {
        if ((int)$ticket['id'] === $ticketId) {
            $ticket['status'] = $status;
            json_write(TICKETS_FILE, $tickets);
            return true;
        }
    }
    return false;
}

// ----- Commentaires ---------------------------------------------------------

function get_comments_for_ticket(int $ticketId): array {
    $comments = array_filter(
        json_read(COMMENTS_FILE),
        fn($c) => (int)$c['ticket_id'] === $ticketId
    );
    usort($comments, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));
    return array_values($comments);
}

function add_comment(int $ticketId, int $authorId, string $message): int {
    $comments = json_read(COMMENTS_FILE);
    $id = json_next_id($comments);
    $comments[] = [
        'id'         => $id,
        'ticket_id'  => $ticketId,
        'author_id'  => $authorId,
        'message'    => $message,
        'created_at' => date('Y-m-d H:i:s'),
    ];
    json_write(COMMENTS_FILE, $comments);
    return $id;
}
