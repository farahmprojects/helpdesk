<?php
/**
 * Page détail d'un ticket.
 *  - Vérifie que le paramètre GET id est présent et numérique.
 *  - Vérifie que le ticket existe.
 *  - Refuse l'accès si l'étudiant connecté n'est pas l'auteur.
 *  - Permet d'ajouter un commentaire (étudiant auteur ou tuteur).
 *  - Permet au tuteur de mettre à jour le statut.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$current_user = require_login();

// ----- 1. Vérification du paramètre GET id -----
$idParam = get_param('id');
if ($idParam === null || !ctype_digit($idParam)) {
    redirect('dashboard.php');
}
$ticketId = (int)$idParam;

// ----- 2. Récupération du ticket -----
$ticket = find_ticket($ticketId);
if (!$ticket) {
    http_response_code(404);
    $page_title = 'Ticket introuvable';
    include __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-error">Ticket introuvable.</div>';
    echo '<a class="back-link" href="dashboard.php">← Retour aux tickets</a>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// ----- 3. Contrôle d'accès -----
// Un étudiant ne peut consulter que ses propres tickets.
if ($current_user['role'] === 'etudiant'
    && (int)$ticket['author_id'] !== (int)$current_user['id']) {
    http_response_code(403);
    $page_title = 'Accès refusé';
    include __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-error">Vous n\'avez pas accès à ce ticket.</div>';
    echo '<a class="back-link" href="dashboard.php">← Retour aux tickets</a>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$flash_success = null;
$flash_error   = null;

// ----- 4. Traitement des actions POST -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post_param('action', '');

    if ($action === 'add_comment') {
        $message = post_param('message', '');
        if ($message === '') {
            $flash_error = 'Le commentaire ne peut pas être vide.';
        } elseif (mb_strlen($message) > 2000) {
            $flash_error = 'Le commentaire est trop long (2000 caractères max.).';
        } else {
            add_comment($ticketId, (int)$current_user['id'], $message);
            redirect('ticket.php?id=' . $ticketId);
        }
    } elseif ($action === 'update_status') {
        // Réservée au tuteur
        if ($current_user['role'] !== 'tuteur') {
            $flash_error = 'Action non autorisée.';
        } else {
            $newStatus = post_param('status', '');
            if (!validate_in(STATUSES, $newStatus)) {
                $flash_error = 'Statut invalide.';
            } else {
                update_ticket_status($ticketId, $newStatus);
                redirect('ticket.php?id=' . $ticketId);
            }
        }
    }
}

// ----- 5. Préparation de l'affichage -----
$author   = find_user_by_id((int)$ticket['author_id']);
$comments = get_comments_for_ticket($ticketId);

// Précharger les noms d'auteurs des commentaires
$comment_authors = [];
foreach ($comments as $c) {
    $aid = (int)$c['author_id'];
    if (!isset($comment_authors[$aid])) {
        $u = find_user_by_id($aid);
        $comment_authors[$aid] = $u ? $u : ['username' => 'inconnu', 'role' => ''];
    }
}

$page_title = 'Ticket #' . $ticketId;
include __DIR__ . '/includes/header.php';
?>
<a class="back-link" href="dashboard.php">← Retour aux tickets</a>

<?php if ($flash_error): ?>
    <div class="alert alert-error"><?= e($flash_error) ?></div>
<?php endif; ?>
<?php if ($flash_success): ?>
    <div class="alert alert-success"><?= e($flash_success) ?></div>
<?php endif; ?>

<div class="card">
    <div class="page-header">
        <div>
            <h1>#<?= (int)$ticket['id'] ?> — <?= e($ticket['title']) ?></h1>
            <p class="lead">
                Créé par <strong><?= e($author['username'] ?? 'inconnu') ?></strong>
                le <?= e(format_date($ticket['created_at'])) ?>
            </p>
        </div>
    </div>

    <div class="ticket-meta">
        <div>Catégorie : <span class="badge badge-cat"><?= e($ticket['category']) ?></span></div>
        <div>Priorité : <span class="badge <?= e(priority_class($ticket['priority'])) ?>"><?= e($ticket['priority']) ?></span></div>
        <div>Statut : <span class="badge <?= e(status_class($ticket['status'])) ?>"><?= e($ticket['status']) ?></span></div>
    </div>

    <h3>Description</h3>
    <div class="ticket-description"><?= e($ticket['description']) ?></div>

    <?php if ($current_user['role'] === 'tuteur'): ?>
        <h3 style="margin-top:24px;">Mettre à jour le statut</h3>
        <form method="post" action="ticket.php?id=<?= (int)$ticket['id'] ?>" class="status-form">
            <input type="hidden" name="action" value="update_status">
            <select name="status">
                <?php foreach (STATUSES as $s): ?>
                    <option value="<?= e($s) ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>>
                        <?= e($s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn">Enregistrer</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2 style="margin-top:0;color:var(--burgundy);">Commentaires (<?= count($comments) ?>)</h2>

    <?php if (empty($comments)): ?>
        <div class="empty">Aucun commentaire pour l'instant.</div>
    <?php else: ?>
        <ul class="comments">
            <?php foreach ($comments as $c): ?>
                <?php $au = $comment_authors[(int)$c['author_id']]; ?>
                <li class="comment">
                    <div class="comment-meta">
                        <span class="comment-author"><?= e($au['username']) ?></span>
                        <?php if (!empty($au['role'])): ?>
                            <span class="role-tag" style="background:var(--burgundy-soft);color:var(--burgundy-dark);">
                                <?= e($au['role']) ?>
                            </span>
                        <?php endif; ?>
                        · <?= e(format_date($c['created_at'])) ?>
                    </div>
                    <div class="comment-message"><?= e($c['message']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3 style="margin-top:18px;">Ajouter un commentaire</h3>
    <form method="post" action="ticket.php?id=<?= (int)$ticket['id'] ?>">
        <input type="hidden" name="action" value="add_comment">
        <div class="form-group">
            <textarea name="message" rows="4" maxlength="2000"
                      placeholder="Votre message..." required></textarea>
        </div>
        <button type="submit" class="btn">Publier</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
