<?php
// add_student.php — Ajouter un nouvel étudiant
require_once 'config.php';
requireRole('Admin');

define('PAGE_TITLE', 'Ajouter un Étudiant');
$db = getDB();
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    }

    // Récupération et nettoyage des données
    $matricule = trim($_POST['matricule'] ?? '');
    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $niveau    = trim($_POST['niveau'] ?? '');

    // Validation côté serveur
    if (empty($matricule)) $errors[] = 'Le matricule est obligatoire.';
    if (empty($nom))       $errors[] = 'Le nom est obligatoire.';
    if (empty($prenom))    $errors[] = 'Le prénom est obligatoire.';
    if (empty($date_naissance)) $errors[] = 'La date de naissance est obligatoire.';
    if (empty($email))     $errors[] = 'L\'email est obligatoire.';
    if (empty($niveau))    $errors[] = 'Le niveau est obligatoire.';

    // Vérifier que le matricule est unique
    if (empty($errors)) {
        $check = $db->prepare("SELECT id FROM etudiants WHERE matricule = ?");
        $check->execute([$matricule]);
        if ($check->fetch()) {
            $errors[] = 'Ce matricule existe déjà.';
        }
    }

    // Insertion si pas d'erreurs
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insérer l'étudiant
            $stmt = $db->prepare("INSERT INTO etudiants (matricule, nom, prenom, date_naissance, email, niveau) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$matricule, $nom, $prenom, $date_naissance, $email, $niveau]);
            $studentId = $db->lastInsertId();

            // Créer un compte utilisateur pour l'étudiant
            $defaultPassword = password_hash($matricule, PASSWORD_DEFAULT); // Mot de passe = matricule par défaut
            $stmtUser = $db->prepare("INSERT INTO utilisateurs (username, password, role, id_ref) VALUES (?, ?, 'Etudiant', ?)");
            $stmtUser->execute([$matricule, $defaultPassword, $studentId]);

            $db->commit();

            setFlash('success', "L'étudiant {$prenom} {$nom} a été ajouté avec succès. (Identifiant: {$matricule}, Mot de passe: {$matricule})");
            redirect('dashboard_admin.php');
        } catch (PDOException $ex) {
            $db->rollBack();
            $errors[] = 'Erreur lors de l\'ajout: ' . $ex->getMessage();
        }
    }
}

require 'header.php';
require 'sidebar.php';
?>

<div class="page-header">
    <h2>Ajouter un Étudiant</h2>
    <p>Remplissez le formulaire pour inscrire un nouvel étudiant</p>
</div>

<div class="form-page">
    <div class="card">
        <div class="card-header">
            <h3>Informations de l'étudiant</h3>
            <a href="dashboard_admin.php" class="btn btn-secondary btn-sm">Retour</a>
        </div>
        <div class="card-body">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $err): ?>
                        <div><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="add_student.php">
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="matricule">Matricule *</label>
                    <input type="text" id="matricule" name="matricule" placeholder="Ex: ETU2025006"
                           value="<?= e($_POST['matricule'] ?? '') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" placeholder="Nom de famille"
                               value="<?= e($_POST['nom'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" placeholder="Prénom"
                               value="<?= e($_POST['prenom'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="date_naissance">Date de naissance *</label>
                        <input type="date" id="date_naissance" name="date_naissance"
                               value="<?= e($_POST['date_naissance'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="niveau">Niveau *</label>
                        <select id="niveau" name="niveau" required>
                            <option value="">-- Choisir --</option>
                            <?php
                            $niveaux = ['L1 Informatique','L2 Informatique','L3 Informatique','M1 Informatique','M2 Informatique'];
                            foreach ($niveaux as $niv):
                                $selected = (($_POST['niveau'] ?? '') === $niv) ? 'selected' : '';
                            ?>
                                <option value="<?= $niv ?>" <?= $selected ?>><?= $niv ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" placeholder="email@exemple.dz"
                           value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer l'étudiant</button>
            </form>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>
