<?php
/**
 * Header HTML commun à toutes les pages internes.
 * Variables attendues :
 *   - $page_title (string) : titre affiché dans l'onglet
 *   - $current_user (array, optionnel) : utilisateur connecté pour la navbar
 */
$page_title = $page_title ?? 'Helpdesk';
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> · Helpdesk</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a href="dashboard.php" class="brand">
            <span class="brand-mark">H</span>
            <span class="brand-name">Helpdesk</span>
        </a>
        <?php if (!empty($current_user)): ?>
            <nav class="topnav">
                <a href="dashboard.php">Tickets</a>
                <?php if ($current_user['role'] === 'etudiant'): ?>
                    <a href="create_ticket.php">Nouveau ticket</a>
                <?php endif; ?>
                <span class="user-info">
                    <?= e($current_user['username']) ?>
                    <span class="role-tag"><?= e($current_user['role']) ?></span>
                </span>
                <a class="btn-ghost" href="logout.php">Déconnexion</a>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main class="container">
