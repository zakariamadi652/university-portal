<?php
// delete_student.php — Supprimer un étudiant
require_once 'config.php';
requireRole('Admin');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    setFlash('error', 'Étudiant introuvable.');
    redirect('dashboard_admin.php');
}

// Vérifier que l'étudiant existe
$stmt = $db->prepare("SELECT * FROM etudiants WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('error', 'Étudiant introuvable.');
    redirect('dashboard_admin.php');
}

try {
    $db->beginTransaction();

    // Supprimer le compte utilisateur associé
    $stmtUser = $db->prepare("DELETE FROM utilisateurs WHERE role = 'Etudiant' AND id_ref = ?");
    $stmtUser->execute([$id]);

    // Supprimer les notes (CASCADE le fait aussi, mais on est explicite)
    $stmtNotes = $db->prepare("DELETE FROM notes WHERE id_etudiant = ?");
    $stmtNotes->execute([$id]);

    // Supprimer l'étudiant
    $stmtDel = $db->prepare("DELETE FROM etudiants WHERE id = ?");
    $stmtDel->execute([$id]);

    $db->commit();

    setFlash('success', "L'étudiant {$student['prenom']} {$student['nom']} a été supprimé.");
} catch (PDOException $ex) {
    $db->rollBack();
    setFlash('error', 'Erreur lors de la suppression: ' . $ex->getMessage());
}

redirect('dashboard_admin.php');
