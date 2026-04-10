<?php
// interface_enseignant.php — Interface Enseignant
require_once 'config.php';
requireRole('Enseignant');

define('PAGE_TITLE', 'Mes Modules');
$db = getDB();

$idEnseignant = $_SESSION['id_ref'];

// Récupérer les modules de cet enseignant
$stmtModules = $db->prepare("SELECT * FROM modules WHERE id_enseignant = ? ORDER BY code_module");
$stmtModules->execute([$idEnseignant]);
$modules = $stmtModules->fetchAll();

// Module sélectionné (premier par défaut)
$selectedModuleId = (int)($_GET['module'] ?? ($modules[0]['id'] ?? 0));

// Récupérer les infos du module sélectionné
$currentModule = null;
foreach ($modules as $m) {
    if ($m['id'] === $selectedModuleId) {
        $currentModule = $m;
        break;
    }
}

// Traitement de la saisie/modification des notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentModule) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token de sécurité invalide.');
        redirect("interface_enseignant.php?module={$selectedModuleId}");
    }

    $notes = $_POST['notes'] ?? [];
    $count = 0;

    foreach ($notes as $studentId => $noteValue) {
        $noteValue = trim($noteValue);
        if ($noteValue === '') continue; // Ignorer les champs vides

        $noteFloat = (float)$noteValue;
        if ($noteFloat < 0 || $noteFloat > 20) continue; // Validation: 0-20

        // INSERT ou UPDATE (UPSERT)
        $stmtNote = $db->prepare("
            INSERT INTO notes (id_etudiant, id_module, note, date_saisie) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE note = VALUES(note), date_saisie = NOW()
        ");
        $stmtNote->execute([(int)$studentId, $selectedModuleId, $noteFloat]);
        $count++;
    }

    setFlash('success', "{$count} note(s) enregistrée(s) avec succès.");
    redirect("interface_enseignant.php?module={$selectedModuleId}");
}

// Récupérer les étudiants avec leurs notes pour le module sélectionné
$studentsWithNotes = [];
$moduleAverage = 0;
$noteCount = 0;

if ($currentModule) {
    $stmtStudents = $db->prepare("
        SELECT e.id, e.matricule, e.nom, e.prenom, e.niveau, n.note
        FROM etudiants e
        LEFT JOIN notes n ON n.id_etudiant = e.id AND n.id_module = ?
        ORDER BY e.nom, e.prenom
    ");
    $stmtStudents->execute([$selectedModuleId]);
    $studentsWithNotes = $stmtStudents->fetchAll();

    // Calculer la moyenne du module
    foreach ($studentsWithNotes as $s) {
        if ($s['note'] !== null) {
            $moduleAverage += (float)$s['note'];
            $noteCount++;
        }
    }
    if ($noteCount > 0) {
        $moduleAverage = round($moduleAverage / $noteCount, 2);
    }
}

require 'header.php';
require 'sidebar.php';
?>

<div class="page-header">
    <h2>Mes Modules</h2>
    <p>Gérez les notes de vos étudiants</p>
</div>

<!-- Onglets des modules -->
<?php if (count($modules) > 0): ?>
<div class="module-tabs">
    <?php foreach ($modules as $m): ?>
        <a href="interface_enseignant.php?module=<?= $m['id'] ?>"
           class="module-tab <?= ($m['id'] === $selectedModuleId) ? 'active' : '' ?>">
            <?= e($m['code_module']) ?> — <?= e($m['intitule']) ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Contenu du module sélectionné -->
<?php if ($currentModule): ?>
<div class="card">
    <div class="card-header">
        <h3><?= e($currentModule['intitule']) ?> (Coeff. <?= $currentModule['coefficient'] ?>)</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="interface_enseignant.php?module=<?= $selectedModuleId ?>">
            <?= csrfField() ?>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Niveau</th>
                            <th>Note / 20</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentsWithNotes as $s): ?>
                        <tr>
                            <td><strong><?= e($s['matricule']) ?></strong></td>
                            <td><?= e($s['nom']) ?></td>
                            <td><?= e($s['prenom']) ?></td>
                            <td><?= e($s['niveau']) ?></td>
                            <td>
                                <input type="number" class="grade-input"
                                       name="notes[<?= $s['id'] ?>]"
                                       value="<?= ($s['note'] !== null) ? e($s['note']) : '' ?>"
                                       min="0" max="20" step="0.25"
                                       placeholder="--">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 12px; align-items: center;">
                <button type="submit" class="btn btn-primary">💾 Enregistrer les notes</button>
            </div>
        </form>

        <!-- Moyenne du module -->
        <?php if ($noteCount > 0): ?>
        <div class="average-display">
            <div class="average-label">Moyenne du module</div>
            <div class="average-value <?= $moduleAverage >= 14 ? 'good' : ($moduleAverage >= 10 ? 'warning' : 'bad') ?>">
                <?= number_format($moduleAverage, 2) ?> / 20
            </div>
            <div class="average-label" style="margin-top: 4px;">(<?= $noteCount ?> note(s) saisie(s))</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-icon"></div>
            <p>Aucun module ne vous est attribué.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require 'footer.php'; ?>
