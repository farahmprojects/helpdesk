<?php
/**
 * Dashboard : liste des tickets.
 *  - Étudiant : ses propres tickets uniquement.
 *  - Tuteur   : tous les tickets de la plateforme.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$current_user = require_login();

// Récupération des tickets selon le rôle
if ($current_user['role'] === 'tuteur') {
    $tickets = get_all_tickets();
    $heading = 'Tous les tickets';
    $lead    = 'Vue tuteur — vous voyez l\'ensemble des tickets soumis par les étudiants.';
} else {
    $tickets = get_tickets_for_user((int)$current_user['id']);
    $heading = 'Mes tickets';
    $lead    = 'Retrouvez ici les tickets que vous avez créés.';
}

// On précharge les auteurs pour éviter de relire les utilisateurs en boucle
$author_names = [];
foreach ($tickets as $t) {
    $aid = (int)$t['author_id'];
    if (!isset($author_names[$aid])) {
        $author = find_user_by_id($aid);
        $author_names[$aid] = $author ? $author['username'] : 'inconnu';
    }
}

$page_title = $heading;
include __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <div>
        <h1><?= e($heading) ?></h1>
        <p class="lead"><?= e($lead) ?></p>
    </div>
    <?php if ($current_user['role'] === 'etudiant'): ?>
        <a class="btn" href="create_ticket.php">+ Nouveau ticket</a>
    <?php endif; ?>
</div>

<?php if (empty($tickets)): ?>
    <div class="empty">
        <?php if ($current_user['role'] === 'etudiant'): ?>
            Vous n'avez encore créé aucun ticket.<br>
            <a href="create_ticket.php">Créer votre premier ticket</a>
        <?php else: ?>
            Aucun ticket n'a été soumis pour le moment.
        <?php endif; ?>
    </div>
<?php else: ?>
    <table class="tickets-table">
        <thead>
            <tr>
                <th style="width:60px;">#</th>
                <th>Titre</th>
                <?php if ($current_user['role'] === 'tuteur'): ?>
                    <th>Auteur</th>
                <?php endif; ?>
                <th>Catégorie</th>
                <th>Priorité</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
            <tr>
                <td>#<?= (int)$t['id'] ?></td>
                <td>
                    <a class="title-link" href="ticket.php?id=<?= (int)$t['id'] ?>">
                        <?= e($t['title']) ?>
                    </a>
                </td>
                <?php if ($current_user['role'] === 'tuteur'): ?>
                    <td><?= e($author_names[(int)$t['author_id']]) ?></td>
                <?php endif; ?>
                <td><span class="badge badge-cat"><?= e($t['category']) ?></span></td>
                <td><span class="badge <?= e(priority_class($t['priority'])) ?>"><?= e($t['priority']) ?></span></td>
                <td><span class="badge <?= e(status_class($t['status'])) ?>"><?= e($t['status']) ?></span></td>
                <td><?= e(format_date($t['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
