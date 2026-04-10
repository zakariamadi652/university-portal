<?php
// dashboard_admin.php — Tableau de bord Administrateur
require_once 'config.php';
requireRole('Admin');

define('PAGE_TITLE', 'Tableau de bord');
$db = getDB();

// --- Statistiques ---
$totalStudents = $db->query("SELECT COUNT(*) FROM etudiants")->fetchColumn();
$totalTeachers = $db->query("SELECT COUNT(*) FROM enseignants")->fetchColumn();
$totalModules  = $db->query("SELECT COUNT(*) FROM modules")->fetchColumn();

// --- Recherche d'étudiants ---
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $db->prepare("SELECT * FROM etudiants WHERE nom LIKE ? OR prenom LIKE ? OR matricule LIKE ? ORDER BY nom, prenom");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $db->query("SELECT * FROM etudiants ORDER BY nom, prenom");
}
$students = $stmt->fetchAll();

require 'header.php';
require 'sidebar.php';
?>

<!-- En-tête de page -->
<div class="page-header">
    <h2>Tableau de bord</h2>
    <p>Vue d'ensemble de la gestion scolaire</p>
</div>

<!-- Cartes de statistiques -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"></div>
        <div class="stat-value"><?= $totalStudents ?></div>
        <div class="stat-label">Étudiants</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"></div>
        <div class="stat-value"><?= $totalTeachers ?></div>
        <div class="stat-label">Enseignants</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"></div>
        <div class="stat-value"><?= $totalModules ?></div>
        <div class="stat-label">Modules</div>
    </div>
</div>

<!-- Liste des étudiants -->
<div class="card">
    <div class="card-header">
        <h3>Liste des Étudiants</h3>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <form method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Rechercher un étudiant..." value="<?= e($search) ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Rechercher</button>
                <?php if ($search): ?>
                    <a href="dashboard_admin.php" class="btn btn-secondary btn-sm">Effacer</a>
                <?php endif; ?>
            </form>
            <a href="add_student.php" class="btn btn-primary btn-sm">➕ Ajouter</a>
        </div>
    </div>

    <div class="table-wrapper">
        <?php if (count($students) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Date de naissance</th>
                    <th>Email</th>
                    <th>Niveau</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><strong><?= e($s['matricule']) ?></strong></td>
                    <td><?= e($s['nom']) ?></td>
                    <td><?= e($s['prenom']) ?></td>
                    <td><?= e($s['date_naissance']) ?></td>
                    <td><?= e($s['email']) ?></td>
                    <td><?= e($s['niveau']) ?></td>
                    <td class="actions">
                        <a href="edit_student.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm">Modifier</a>
                        <a href="delete_student.php?id=<?= $s['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet étudiant ?')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p><?= $search ? "Aucun résultat pour \"" . e($search) . "\"" : "Aucun étudiant enregistré." ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require 'footer.php'; ?>
