<?php
/**
 * index.php — Page d'accueil (Landing Page)
 * Système de Gestion de Scolarité
 */
require_once 'config.php';

// Si l'utilisateur est déjà connecté, on peut le rediriger vers son dashboard
// ou le laisser voir la page d'accueil avec un bouton "Accéder au Dashboard"
$isLogged = isLoggedIn();

// Petite redirection interne si on clique sur "Accéder au Dashboard"
if (isset($_GET['go']) && $_GET['go'] === 'dashboard') {
    redirectToDashboard();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gestion de Scolarité — Système de gestion scolaire.">
    <title>Bienvenue — Gestion Scolarité</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="landing-page">

    <div class="home-center">
        <div class="home-card">
            <div class="home-logo">GS</div>
            <h1 class="home-title">Gestion de Scolarité</h1>
            <p class="home-sub">Système de Gestion Scolaire</p>

            <?php if ($isLogged): ?>
                <a href="index.php?go=dashboard" class="btn btn-primary btn-full">Accéder au Dashboard</a>
                <a href="logout.php" class="btn btn-secondary btn-full" style="margin-top: 10px;">Se Déconnecter</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-full">Se Connecter</a>
            <?php endif; ?>
        </div>

        <p class="home-footer">© 2025/2026 Gestion de Scolarité — Développé par LAACHEMI</p>
    </div>




</body>
</html>
