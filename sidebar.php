<?php
// sidebar.php — Navigation latérale selon le rôle
$currentPage = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_role'] ?? '';

// Définir les éléments du menu selon le rôle
$menuItems = [];

if ($role === 'Admin') {
    $menuItems = [
        ['url' => 'dashboard_admin.php', 'label' => 'Tableau de bord', 'icon' => ''],
        ['url' => 'add_student.php',     'label' => 'Ajouter Étudiant', 'icon' => ''],
    ];
} elseif ($role === 'Enseignant') {
    $menuItems = [
        ['url' => 'interface_enseignant.php', 'label' => 'Mes Modules', 'icon' => ''],
    ];
} elseif ($role === 'Etudiant') {
    $menuItems = [
        ['url' => 'interface_etudiant.php', 'label' => 'Mes Notes', 'icon' => ''],
        ['url' => 'transcript_pdf.php',     'label' => 'Relevé de Notes', 'icon' => ''],
    ];
}
?>
<!-- Barre latérale -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-avatar">
            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="sidebar-user-info">
            <span class="sidebar-username"><?= e($_SESSION['username'] ?? '') ?></span>
            <span class="sidebar-role"><?= e($role) ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <?php foreach ($menuItems as $item): ?>
                <li>
                    <a href="<?= $item['url'] ?>" class="<?= ($currentPage === $item['url']) ? 'active' : '' ?>">
                        <span class="nav-icon"><?= $item['icon'] ?></span>
                        <span class="nav-label"><?= e($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <small>© 2025/2026 LAACHEMI</small>
    </div>
</aside>

<!-- Contenu principal -->
<main class="main-content">
    <?php
    // Afficher le message flash s'il existe
    $flash = getFlash();
    if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>
