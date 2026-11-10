<?php
/**
 * Page de connexion.
 * - Affiche le formulaire en GET.
 * - Traite le formulaire en POST.
 * - Redirige vers le dashboard en cas de succès.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Si déjà connecté, on ne reste pas ici
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = null;
$success = null;

// Message de succès après une inscription réussie
if (get_param('registered') === '1') {
    $success = 'Votre compte a bien été créé. Vous pouvez maintenant vous connecter.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post_param('username', '');
    $password = post_param('password', '');

    if ($username === '' || $password === '') {
        $error = 'Veuillez renseigner un identifiant et un mot de passe.';
    } elseif (attempt_login($username, $password)) {
        redirect('dashboard.php');
    } else {
        $error = 'Identifiant ou mot de passe incorrect.';
    }
}

$page_title = 'Connexion';
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
        <h1>Connexion</h1>
        <p class="subtitle">Accédez à votre espace Helpdesk de cours.</p>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" novalidate>
            <div class="form-group">
                <label for="username">Identifiant</label>
                <input type="text" id="username" name="username"
                       value="<?= e(post_param('username', '')) ?>"
                       autocomplete="username" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password"
                       autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-block">Se connecter</button>
        </form>

        <div class="login-hint">
            Pas encore de compte ? <a href="register.php">Créer un compte étudiant</a>
        </div>

        <div class="login-hint">
            <strong>Comptes de démonstration :</strong><br>
            étudiant : <code>etudiant1</code> / <code>motdepasse</code><br>
            étudiant : <code>etudiant2</code> / <code>motdepasse</code><br>
            tuteur : <code>tuteur</code> / <code>motdepasse</code>
        </div>
    </div>
</div>
<footer class="footer">
    <div class="footer-inner">
        Helpdesk de cours · Projet PHP 
    </div>
</footer>
</body>
</html>
