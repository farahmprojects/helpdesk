<?php
/**
 * Page de création d'un nouveau ticket.
 * Réservée aux étudiants.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$current_user = require_login();

// Seul un étudiant peut créer un ticket
if ($current_user['role'] !== 'etudiant') {
    redirect('dashboard.php');
}

$errors = [];
$values = [
    'title'       => '',
    'description' => '',
    'category'    => 'Cours',
    'priority'    => 'Moyenne',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ----- Récupération + validation côté serveur -----
    $values['title']       = post_param('title', '');
    $values['description'] = post_param('description', '');
    $values['category']    = post_param('category', '');
    $values['priority']    = post_param('priority', '');

    if ($values['title'] === '' || mb_strlen($values['title']) > 150) {
        $errors[] = 'Le titre est obligatoire et doit faire au plus 150 caractères.';
    }
    if ($values['description'] === '' || mb_strlen($values['description']) > 5000) {
        $errors[] = 'La description est obligatoire et doit faire au plus 5000 caractères.';
    }
    if (!validate_in(CATEGORIES, $values['category'])) {
        $errors[] = 'Catégorie invalide.';
    }
    if (!validate_in(PRIORITIES, $values['priority'])) {
        $errors[] = 'Priorité invalide.';
    }

    if (empty($errors)) {
        $id = create_ticket(
            (int)$current_user['id'],
            $values['title'],
            $values['description'],
            $values['category'],
            $values['priority']
        );
        redirect('ticket.php?id=' . $id);
    }
}

$page_title = 'Nouveau ticket';
include __DIR__ . '/includes/header.php';
?>
<a class="back-link" href="dashboard.php">← Retour aux tickets</a>

<div class="page-header">
    <div>
        <h1>Nouveau ticket</h1>
        <p class="lead">Décrivez précisément votre question ou problème pour faciliter la réponse du tuteur.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <form method="post" action="create_ticket.php" novalidate>
        <div class="form-group">
            <label for="title">Titre</label>
            <input type="text" id="title" name="title" maxlength="150"
                   value="<?= e($values['title']) ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="category">Catégorie</label>
                <select id="category" name="category" required>
                    <?php foreach (CATEGORIES as $cat): ?>
                        <option value="<?= e($cat) ?>"
                            <?= $values['category'] === $cat ? 'selected' : '' ?>>
                            <?= e($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="priority">Priorité</label>
                <select id="priority" name="priority" required>
                    <?php foreach (PRIORITIES as $prio): ?>
                        <option value="<?= e($prio) ?>"
                            <?= $values['priority'] === $prio ? 'selected' : '' ?>>
                            <?= e($prio) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="6"
                      maxlength="5000" required><?= e($values['description']) ?></textarea>
        </div>

        <button type="submit" class="btn">Créer le ticket</button>
        <a class="btn btn-secondary" href="dashboard.php">Annuler</a>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
