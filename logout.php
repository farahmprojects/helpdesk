<?php
/**
 * Déconnecte l'utilisateur courant et le ramène à la page de login.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

logout();
redirect('login.php');
