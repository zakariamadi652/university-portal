<?php
// header.php — En-tête HTML + barre de navigation
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', 'Gestion de Scolarité');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Système de gestion de scolarité — LAACHEMI 2025/2026">
    <title><?= e(PAGE_TITLE) ?> — Gestion Scolarité</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Barre de navigation supérieure -->
    <nav class="topbar">
        <div class="topbar-brand">
            <span class="brand-icon">GS</span>
            <span class="brand-text">Gestion Scolarité</span>
        </div>
        <div class="topbar-user">
            <?php if (isLoggedIn()): ?>
                <span class="user-role-badge"><?= e($_SESSION['user_role'] ?? '') ?></span>
                <span class="user-name"><?= e($_SESSION['username'] ?? '') ?></span>
                <a href="logout.php" class="btn-logout" title="Déconnexion">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">Connexion</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="app-layout">
