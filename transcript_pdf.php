<?php
// transcript_pdf.php — Relevé de notes imprimable
require_once 'config.php';
requireRole('Etudiant');

define('PAGE_TITLE', 'Relevé de Notes');
$db = getDB();

$idEtudiant = $_SESSION['id_ref'];

// Informations de l'étudiant
$stmtEtu = $db->prepare("SELECT * FROM etudiants WHERE id = ?");
$stmtEtu->execute([$idEtudiant]);
$etudiant = $stmtEtu->fetch();

// Notes
$stmtNotes = $db->prepare("
    SELECT m.code_module, m.intitule, m.coefficient, n.note
    FROM notes n
    INNER JOIN modules m ON m.id = n.id_module
    WHERE n.id_etudiant = ?
    ORDER BY m.code_module
");
$stmtNotes->execute([$idEtudiant]);
$notes = $stmtNotes->fetchAll();

// Moyenne
$totalCoeffNote = 0;
$totalCoeff = 0;
foreach ($notes as $n) {
    $totalCoeffNote += (float)$n['note'] * (int)$n['coefficient'];
    $totalCoeff += (int)$n['coefficient'];
}
$moyenne = ($totalCoeff > 0) ? round($totalCoeffNote / $totalCoeff, 2) : 0;

require 'header.php';
require 'sidebar.php';
?>

<a href="interface_etudiant.php" class="btn btn-secondary btn-sm" style="margin-bottom: 12px;">← Retour</a>
<button onclick="window.print()" class="btn btn-primary btn-sm btn-print">🖨️ Imprimer / Télécharger PDF</button>

<div class="transcript">
    <div class="transcript-header">
        <h1>Relevé de Notes</h1>
        <p>Année Universitaire 2025/2026 — LAACHEMI</p>
    </div>

    <dl class="transcript-info">
        <dt>Matricule</dt>
        <dd><?= e($etudiant['matricule'] ?? '') ?></dd>
        <dt>Nom complet</dt>
        <dd><?= e(($etudiant['prenom'] ?? '') . ' ' . ($etudiant['nom'] ?? '')) ?></dd>
        <dt>Date de naissance</dt>
        <dd><?= e($etudiant['date_naissance'] ? date('d/m/Y', strtotime($etudiant['date_naissance'])) : '') ?></dd>
        <dt>Niveau</dt>
        <dd><?= e($etudiant['niveau'] ?? '') ?></dd>
    </dl>

    <?php if (count($notes) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Module</th>
                <th>Coefficient</th>
                <th>Note / 20</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notes as $n): ?>
            <tr>
                <td><?= e($n['code_module']) ?></td>
                <td><?= e($n['intitule']) ?></td>
                <td style="text-align:center;"><?= e($n['coefficient']) ?></td>
                <td style="text-align:center;"><?= number_format((float)$n['note'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right; font-weight:700;">Moyenne Générale Pondérée :</td>
                <td style="text-align:center; font-weight:700; font-size:1.1rem;"><?= number_format($moyenne, 2) ?> / 20</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 24px; text-align: center; font-size: 0.9rem; color: var(--gray-500);">
        <p><strong>Décision :</strong> 
            <?= $moyenne >= 10 ? '✅ Admis(e)' : '❌ Ajourné(e)' ?>
        </p>
        <p style="margin-top:16px; font-size: 0.8rem;">
            Document généré le <?= date('d/m/Y à H:i') ?>
        </p>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <p>Aucune note disponible.</p>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>
