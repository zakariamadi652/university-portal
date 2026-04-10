<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
// login.php — Page de connexion
require_once 'config.php';

// Si déjà connecté, rediriger vers le dashboard
if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Requête invalide. Veuillez réessayer.';
    }
    // Validation côté serveur
    elseif (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Rechercher l'utilisateur dans la base de données
        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, password, role, id_ref FROM utilisateurs WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Régénérer l'ID de session pour prévenir le fixation de session
            session_regenerate_id(true);

            // Stocker les informations de l'utilisateur en session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['id_ref'] = $user['id_ref'];

            // Rediriger vers le tableau de bord approprié
            redirectToDashboard();
        } else {
            $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Connexion — Gestion de Scolarité">
    <title>Connexion — Gestion Scolarité</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">

    <div class="login-card">
        <a href="index.php" class="btn-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Retour
        </a>
        <div class="login-logo">GS</div>
        <h1>Gestion de Scolarité</h1>
        <p class="subtitle">Connectez-vous à votre compte</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" placeholder="Entrez votre identifiant"
                       value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Se connecter</button>
        </form>
    </div>

</body>
</html>
