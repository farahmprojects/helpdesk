<?php
/**
 * Fonctions utilitaires partagées.
 */

/**
 * Échappe une chaîne pour affichage HTML (protection XSS).
 */
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Récupère un paramètre GET en vérifiant son existence.
 */
function get_param(string $key, $default = null) {
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
}

/**
 * Récupère un paramètre POST en vérifiant son existence.
 */
function post_param(string $key, $default = null) {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

/**
 * Vérifie qu'une valeur appartient à une liste autorisée.
 */
function validate_in(array $allowed, $value): bool {
    return is_string($value) && in_array($value, $allowed, true);
}

/**
 * Formate une date ISO en format lisible français.
 */
function format_date(string $iso): string {
    $ts = strtotime($iso);
    if ($ts === false) {
        return $iso;
    }
    return date('d/m/Y H:i', $ts);
}

/**
 * Renvoie une classe CSS associée à une priorité.
 */
function priority_class(string $priority): string {
    return [
        'Basse'   => 'badge-low',
        'Moyenne' => 'badge-medium',
        'Haute'   => 'badge-high',
    ][$priority] ?? 'badge-low';
}

/**
 * Renvoie une classe CSS associée à un statut.
 */
function status_class(string $status): string {
    return [
        'Ouvert'   => 'badge-open',
        'En cours' => 'badge-progress',
        'Résolu'   => 'badge-done',
    ][$status] ?? 'badge-open';
}

/**
 * Redirige vers une URL puis termine le script.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
