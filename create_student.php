<?php
// create_student.php - Add New Student Page
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['user_role'];
$username = $_SESSION['username'];

try {
    $db = new PDO('mysql:host=localhost;dbname=gestion_scolarite;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_student') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $date_naissance = $_POST['date_naissance'] ?? '';
        $niveau = $_POST['niveau'] ?? '';

        if ($nom && $prenom && $email && $date_naissance && $niveau) {
            // Check if email is already used
            $check = $db->prepare('SELECT COUNT(*) FROM etudiants WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetchColumn() > 0) {
                $message = "A student with this email already exists.";
                $msg_type = 'error';
            } else {
                // Generate matricule
                $year = date('Y');
                $count = $db->query("SELECT COUNT(*) FROM etudiants")->fetchColumn() + 1;
                $matricule = "ETU" . $year . str_pad($count, 3, '0', STR_PAD_LEFT);

                // Insert student
                $stmt = $db->prepare('INSERT INTO etudiants (matricule, nom, prenom, email, date_naissance, niveau) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$matricule, $nom, $prenom, $email, $date_naissance, $niveau]);
                $etu_id = $db->lastInsertId();

                // Create login account: email is the username, default password is the matricule
                $pw = password_hash($matricule, PASSWORD_DEFAULT);
                $stmt2 = $db->prepare('INSERT INTO utilisateurs (username, password, role, id_ref) VALUES (?, ?, ?, ?)');
                $stmt2->execute([$email, $pw, 'Etudiant', $etu_id]);

                $message = "Student added! Matricule: $matricule — Login: $email / Password: $matricule";
                $msg_type = 'success';
            }
        } else {
            $message = "Please fill in all fields.";
            $msg_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student — University Portal</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>

<body>

    <div class="header">
        <div style="display:flex;align-items:center;">
            <img src="logo.png?v=2" alt="Logo">
            <h1>University Portal</h1>
        </div>
        <div class="header-links">
            <a href="home.php?logout=1">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="user-badge">
            Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong>
            (<?php echo htmlspecialchars($role); ?>)
        </div>

        <a href="dashboard.php?tab=students" class="back-link" style="margin-bottom:20px;display:inline-flex;">← Back to
            Student List</a>

        <?php if ($message): ?>
            <div class="alert <?php echo $msg_type === 'error' ? 'error' : ''; ?>"><?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <h2>Add New Student</h2>

        <form method="POST" action="create_student.php">
            <input type="hidden" name="action" value="add_student">

            <label>First Name:</label>
            <input type="text" name="prenom" required placeholder="Enter first name">

            <label>Last Name:</label>
            <input type="text" name="nom" required placeholder="Enter last name">

            <label>Email (used for login):</label>
            <input type="email" name="email" required placeholder="student@university.dz">

            <label>Date of Birth:</label>
            <input type="date" name="date_naissance" required>

            <label>Level:</label>
            <select name="niveau" required>
                <option value="L1 Informatique">L1 Informatique</option>
                <option value="L2 Informatique">L2 Informatique</option>
                <option value="L3 Informatique" selected>L3 Informatique</option>
            </select>

            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="submit" class="btn">Add Student</button>
                <a href="dashboard.php?tab=students" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

    </div>

    <div class="footer">© 2025/2026 University Portal</div>

</body>

</html>