<?php
/**
 * Point d'entrée : redirige vers la bonne page selon l'état de connexion.
 */
require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
