<?php
/**
 * Page d'inscription.
 * Permet à un nouvel étudiant de créer un compte.
 * Le rôle est forcé à 'etudiant' côté serveur — on ne permet pas
 * de s'inscrire en tant que tuteur depuis ce formulaire public.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Si déjà connecté, on ne reste pas ici
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$errors = [];
$values = [
    'username' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['username'] = post_param('username', '');
    $password           = post_param('password', '');
    $password_confirm   = post_param('password_confirm', '');

    // ----- Validation côté serveur -----
    if ($values['username'] === '') {
        $errors[] = 'L\'identifiant est obligatoire.';
    } elseif (mb_strlen($values['username']) < 3 || mb_strlen($values['username']) > 50) {
        $errors[] = 'L\'identifiant doit faire entre 3 et 50 caractères.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $values['username'])) {
        $errors[] = 'L\'identifiant ne peut contenir que des lettres, chiffres, tirets, points et underscores.';
    }

    if ($password === '') {
        $errors[] = 'Le mot de passe est obligatoire.';
    } elseif (mb_strlen($password) < 6) {
        $errors[] = 'Le mot de passe doit faire au moins 6 caractères.';
    } elseif (mb_strlen($password) > 200) {
        $errors[] = 'Le mot de passe est trop long.';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Les deux mots de passe ne correspondent pas.';
    }

    // Vérification de l'unicité de l'identifiant (seulement si pas d'autre erreur)
    if (empty($errors)) {
        if (find_user_by_username($values['username']) !== null) {
            $errors[] = 'Cet identifiant est déjà utilisé. Veuillez en choisir un autre.';
        }
    }

    // ----- Création du compte -----
    if (empty($errors)) {
        create_user($values['username'], $password);
        // Redirection vers login avec un message de succès
        redirect('login.php?registered=1');
    }
}

$page_title = 'Inscription';
?>
<!DOCTYPE html>
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
        <span class="brand">
            <span class="brand-mark">H</span>
            <span class="brand-name">Helpdesk</span>
        </span>
    </div>
</header>
<div class="login-wrapper">
    <div class="login-card">
        <h1>Inscription</h1>
        <p class="subtitle">Créez votre compte étudiant pour soumettre des tickets.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <div><?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php" novalidate>
            <div class="form-group">
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username"
                       value="<?= e($values['username']) ?>"
                       autocomplete="username" minlength="3" maxlength="50" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password"
                       autocomplete="new-password" minlength="6" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm"
                       autocomplete="new-password" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-block">Créer mon compte</button>
        </form>

        <div class="login-hint">
            Vous avez déjà un compte ? <a href="login.php">Se connecter</a>
        </div>
    </div>
</div>
<footer class="footer">
    <div class="footer-inner">
        Helpdesk de cours · Projet PHP L3 Informatique
    </div>
</footer>
</body>
</html>
