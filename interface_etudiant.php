<?php
// interface_etudiant.php — Interface Étudiant
require_once 'config.php';
requireRole('Etudiant');

define('PAGE_TITLE', 'Mes Notes');
$db = getDB();

$idEtudiant = $_SESSION['id_ref'];

// Récupérer les informations de l'étudiant
$stmtEtu = $db->prepare("SELECT * FROM etudiants WHERE id = ?");
$stmtEtu->execute([$idEtudiant]);
$etudiant = $stmtEtu->fetch();

// Récupérer les notes avec les informations des modules
$stmtNotes = $db->prepare("
    SELECT m.code_module, m.intitule, m.coefficient, n.note, n.date_saisie
    FROM notes n
    INNER JOIN modules m ON m.id = n.id_module
    WHERE n.id_etudiant = ?
    ORDER BY m.code_module
");
$stmtNotes->execute([$idEtudiant]);
$notes = $stmtNotes->fetchAll();

// Calculer la moyenne générale pondérée
$totalCoeffNote = 0;
$totalCoeff = 0;
foreach ($notes as $n) {
    $totalCoeffNote += (float)$n['note'] * (int)$n['coefficient'];
    $totalCoeff += (int)$n['coefficient'];
}
$moyenneGenerale = ($totalCoeff > 0) ? round($totalCoeffNote / $totalCoeff, 2) : 0;

// Fonction pour déterminer la classe CSS de la note
function noteBadgeClass(float $note): string {
    if ($note >= 16) return 'excellent';
    if ($note >= 14) return 'bien';
    if ($note >= 10) return 'moyen';
    return 'insuffisant';
}

require 'header.php';
require 'sidebar.php';
?>

<div class="page-header">
    <h2>Mes Notes</h2>
    <p>
        <?php if ($etudiant): ?>
            <?= e($etudiant['prenom']) ?> <?= e($etudiant['nom']) ?> — <?= e($etudiant['matricule']) ?> — <?= e($etudiant['niveau']) ?>
        <?php endif; ?>
    </p>
</div>

<!-- Tableau des notes -->
<div class="card">
    <div class="card-header">
        <h3>Relevé de Notes</h3>
        <a href="transcript_pdf.php" class="btn btn-secondary btn-sm">Télécharger le Relevé</a>
    </div>

    <div class="table-wrapper">
        <?php if (count($notes) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Module</th>
                    <th>Note / 20</th>
                    <th>Coefficient</th>
                    <th>Date de saisie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notes as $n): ?>
                <tr>
                    <td><strong><?= e($n['code_module']) ?></strong></td>
                    <td><?= e($n['intitule']) ?></td>
                    <td>
                        <span class="note-badge <?= noteBadgeClass((float)$n['note']) ?>">
                            <?= number_format((float)$n['note'], 2) ?>
                        </span>
                    </td>
                    <td><?= e($n['coefficient']) ?></td>
                    <td><?= e(date('d/m/Y', strtotime($n['date_saisie']))) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"></div>
                <p>Aucune note n'a encore été saisie pour vous.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Moyenne générale -->
<?php if (count($notes) > 0): ?>
<div class="average-display">
    <div class="average-label">Moyenne Générale Pondérée</div>
    <div class="average-value <?= $moyenneGenerale >= 14 ? 'good' : ($moyenneGenerale >= 10 ? 'warning' : 'bad') ?>">
        <?= number_format($moyenneGenerale, 2) ?> / 20
    </div>
    <div class="average-label" style="margin-top: 4px;">
        <?php if ($moyenneGenerale >= 10): ?>
            ✅ Admis(e)
        <?php else: ?>
            ❌ Ajourné(e)
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require 'footer.php'; ?>
