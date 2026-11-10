<?php
/**
 * Gestion des sessions et de l'authentification.
 *
 * À inclure en haut de toute page nécessitant un utilisateur connecté.
 * Si la fonction require_login() est appelée alors qu'aucun utilisateur
 * n'est connecté, l'utilisateur est redirigé vers login.php.
 */
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/storage.php';

// Démarrage de la session avec des cookies un minimum sécurisés
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Vérifie qu'un utilisateur est connecté, sinon redirige vers login.php.
 * Renvoie le tableau utilisateur courant.
 */
function require_login(): array {
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
    $user = find_user_by_id((int)$_SESSION['user_id']);
    if (!$user) {
        // Session orpheline : on déconnecte
        session_unset();
        session_destroy();
        redirect('login.php');
    }
    return $user;
}

/**
 * Tente de connecter un utilisateur. Renvoie true en cas de succès.
 */
function attempt_login(string $username, string $password): bool {
    $user = find_user_by_username($username);
    if (!$user) {
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }
    // Régénération de l'identifiant de session pour limiter la fixation
    session_regenerate_id(true);
    $_SESSION['user_id']  = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];
    return true;
}

/**
 * Déconnecte l'utilisateur courant.
 */
function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Renvoie true si l'utilisateur courant est tuteur.
 */
function is_tutor(): bool {
    return ($_SESSION['role'] ?? null) === 'tuteur';
}

/**
 * Renvoie true si l'utilisateur courant est étudiant.
 */
function is_student(): bool {
    return ($_SESSION['role'] ?? null) === 'etudiant';
}
