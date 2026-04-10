<?php
// edit_student.php — Modifier un étudiant existant
require_once 'config.php';
requireRole('Admin');

define('PAGE_TITLE', 'Modifier un Étudiant');
$db = getDB();
$errors = [];

// Récupérer l'étudiant
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Étudiant introuvable.');
    redirect('dashboard_admin.php');
}

$stmt = $db->prepare("SELECT * FROM etudiants WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('error', 'Étudiant introuvable.');
    redirect('dashboard_admin.php');
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    }

    $matricule = trim($_POST['matricule'] ?? '');
    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $niveau    = trim($_POST['niveau'] ?? '');

    if (empty($matricule)) $errors[] = 'Le matricule est obligatoire.';
    if (empty($nom))       $errors[] = 'Le nom est obligatoire.';
    if (empty($prenom))    $errors[] = 'Le prénom est obligatoire.';
    if (empty($date_naissance)) $errors[] = 'La date de naissance est obligatoire.';
    if (empty($email))     $errors[] = 'L\'email est obligatoire.';
    if (empty($niveau))    $errors[] = 'Le niveau est obligatoire.';

    // Vérifier unicité du matricule (sauf pour cet étudiant)
    if (empty($errors)) {
        $check = $db->prepare("SELECT id FROM etudiants WHERE matricule = ? AND id != ?");
        $check->execute([$matricule, $id]);
        if ($check->fetch()) {
            $errors[] = 'Ce matricule est déjà utilisé par un autre étudiant.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE etudiants SET matricule = ?, nom = ?, prenom = ?, date_naissance = ?, email = ?, niveau = ? WHERE id = ?");
            $stmt->execute([$matricule, $nom, $prenom, $date_naissance, $email, $niveau, $id]);

            setFlash('success', "L'étudiant {$prenom} {$nom} a été modifié avec succès.");
            redirect('dashboard_admin.php');
        } catch (PDOException $ex) {
            $errors[] = 'Erreur lors de la modification: ' . $ex->getMessage();
        }
    }

    // Mettre à jour les valeurs affichées avec les données soumises
    $student['matricule'] = $matricule;
    $student['nom'] = $nom;
    $student['prenom'] = $prenom;
    $student['date_naissance'] = $date_naissance;
    $student['email'] = $email;
    $student['niveau'] = $niveau;
}

require 'header.php';
require 'sidebar.php';
?>

<div class="page-header">
    <h2>Modifier l'Étudiant</h2>
    <p>Modifiez les informations de <?= e($student['prenom']) ?> <?= e($student['nom']) ?></p>
</div>

<div class="form-page">
    <div class="card">
        <div class="card-header">
            <h3>Informations de l'étudiant</h3>
            <a href="dashboard_admin.php" class="btn btn-secondary btn-sm">← Retour</a>
        </div>
        <div class="card-body">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $err): ?>
                        <div><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="edit_student.php?id=<?= $id ?>">
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="matricule">Matricule *</label>
                    <input type="text" id="matricule" name="matricule"
                           value="<?= e($student['matricule']) ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom"
                               value="<?= e($student['nom']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom"
                               value="<?= e($student['prenom']) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_naissance">Date de naissance *</label>
                        <input type="date" id="date_naissance" name="date_naissance"
                               value="<?= e($student['date_naissance']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="niveau">Niveau *</label>
                        <select id="niveau" name="niveau" required>
                            <option value="">-- Choisir --</option>
                            <?php
                            $niveaux = ['L1 Informatique','L2 Informatique','L3 Informatique','M1 Informatique','M2 Informatique'];
                            foreach ($niveaux as $niv):
                                $selected = ($student['niveau'] === $niv) ? 'selected' : '';
                            ?>
                                <option value="<?= $niv ?>" <?= $selected ?>><?= $niv ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email"
                           value="<?= e($student['email']) ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </form>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>
