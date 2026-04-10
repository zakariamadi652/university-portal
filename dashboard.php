<?php
// dashboard.php - Merged dashboard for all roles
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?login=1');
    exit;
}

$role = $_SESSION['user_role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Database connection
try {
    $db = new PDO('mysql:host=localhost;dbname=gestion_scolarite;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($role === 'Admin' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
        $username_input = $_POST['username'] ?? '';
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $niveau = $_POST['niveau'] ?? '';
        
        if ($username_input && $nom && $prenom && $niveau) {
            $stmt = $db->prepare('INSERT INTO etudiants (matricule, nom, prenom, email, date_naissance, niveau) VALUES (?, ?, ?, ?, ?, ?)');
            $matricule = "ETU".time();
            $stmt->execute([$matricule, $nom, $prenom, $username_input.'@etu.dz', '2000-01-01', $niveau]);
            $etu_id = $db->lastInsertId();
            
            $stmt2 = $db->prepare('INSERT INTO utilisateurs (username, password, role, id_ref) VALUES (?, ?, ?, ?)');
            $pw = password_hash('password123', PASSWORD_DEFAULT);
            $stmt2->execute([$username_input, $pw, 'Etudiant', $etu_id]);
            $message = "Student added successfully.";
        }
    }
    
    if ($role === 'Enseignant' && isset($_POST['action']) && $_POST['action'] === 'save_grades') {
        $module_id = (int)$_POST['module_id'];
        if (isset($_POST['notes']) && is_array($_POST['notes'])) {
            foreach ($_POST['notes'] as $id_etudiant => $valeur) {
                if ($valeur !== '') {
                    $stmt = $db->prepare('SELECT id FROM notes WHERE id_etudiant = ? AND id_module = ?');
                    $stmt->execute([$id_etudiant, $module_id]);
                    if ($stmt->fetch()) {
                        $stmt = $db->prepare('UPDATE notes SET note = ? WHERE id_etudiant = ? AND id_module = ?');
                        $stmt->execute([$valeur, $id_etudiant, $module_id]);
                    } else {
                        $stmt = $db->prepare('INSERT INTO notes (id_etudiant, id_module, note) VALUES (?, ?, ?)');
                        $stmt->execute([$id_etudiant, $module_id, $valeur]);
                    }
                }
            }
            $message = "Grades saved.";
        }
    }
}

if ($role === 'Admin' && isset($_GET['delete_student'])) {
    $id_to_delete = (int)$_GET['delete_student'];
    $db->prepare('DELETE FROM utilisateurs WHERE id_ref = ? AND role = "Etudiant"')->execute([$id_to_delete]);
    $db->prepare('DELETE FROM etudiants WHERE id = ?')->execute([$id_to_delete]);
    $message = "Student deleted.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - University Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header clearfix">
    <img src="logo.png" alt="Logo">
    <h1>University Portal</h1>
    <div class="header-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="index.php?logout=1">Logout</a>
    </div>
</div>

<div class="container clearfix">

    <p style="text-align: right;">Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong> (<?php echo htmlspecialchars($role); ?>)</p>

    <?php if ($message): ?>
        <div class="alert"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($role === 'Admin'): ?>
        <h2>Admin Dashboard</h2>
        
        <h3>Add New Student</h3>
        <form method="POST" action="dashboard.php">
            <input type="hidden" name="action" value="add_student">
            <label>Username (Login):</label>
            <input type="text" name="username" required>
            <label>Last Name:</label>
            <input type="text" name="nom" required>
            <label>First Name:</label>
            <input type="text" name="prenom" required>
            <label>Level:</label>
            <select name="niveau">
                <option value="L1 Informatique">L1 Informatique</option>
                <option value="L2 Informatique">L2 Informatique</option>
                <option value="L3 Informatique">L3 Informatique</option>
            </select>
            <button type="submit" class="btn">Add Student</button>
        </form>

        <h3 style="margin-top:40px;">Student List</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Last Name</th>
                <th>First Name</th>
                <th>Level</th>
                <th>Actions</th>
            </tr>
            <?php
            $students = $db->query("
                SELECT u.id as uid, e.id as eid, u.username, e.nom, e.prenom, e.niveau 
                FROM utilisateurs u 
                JOIN etudiants e ON u.id_ref = e.id 
                WHERE u.role = 'Etudiant' 
                ORDER BY e.id DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($students as $s): ?>
                <tr>
                    <td><?php echo $s['eid']; ?></td>
                    <td><?php echo htmlspecialchars($s['username']); ?></td>
                    <td><?php echo htmlspecialchars($s['nom']); ?></td>
                    <td><?php echo htmlspecialchars($s['prenom']); ?></td>
                    <td><?php echo htmlspecialchars($s['niveau']); ?></td>
                    <td>
                        <a href="dashboard.php?delete_student=<?php echo $s['eid']; ?>" class="btn btn-danger" onclick="return confirm('Delete this student?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

    <?php elseif ($role === 'Enseignant'): ?>
        <h2>Teacher Dashboard</h2>
        
        <?php
        $ens_id = $db->query("SELECT id_ref FROM utilisateurs WHERE id = $user_id")->fetchColumn();
        $modules = $db->prepare('SELECT * FROM modules WHERE id_enseignant = ?');
        $modules->execute([$ens_id]);
        $my_modules = $modules->fetchAll(PDO::FETCH_ASSOC);
        
        $active_module_id = $_GET['module'] ?? ($my_modules[0]['id'] ?? 0);
        ?>

        <p>Select a module to grade:</p>
        <?php foreach ($my_modules as $m): ?>
            <a href="dashboard.php?module=<?php echo $m['id']; ?>" class="btn <?php echo ($active_module_id == $m['id']) ? '' : 'btn-danger'; ?>">
                <?php echo htmlspecialchars($m['intitule']); ?>
            </a>
        <?php endforeach; ?>

        <?php if ($active_module_id): ?>
            <form method="POST" action="dashboard.php?module=<?php echo $active_module_id; ?>" style="margin-top: 30px;">
                <input type="hidden" name="action" value="save_grades">
                <input type="hidden" name="module_id" value="<?php echo $active_module_id; ?>">
                
                <table>
                    <tr>
                        <th>Student Name</th>
                        <th>Level</th>
                        <th>Grade (/20)</th>
                    </tr>
                    <?php
                    $students = $db->query("SELECT id, nom, prenom, niveau FROM etudiants");
                    foreach ($students->fetchAll() as $s): 
                        $gradeStmt = $db->prepare('SELECT note FROM notes WHERE id_etudiant = ? AND id_module = ?');
                        $gradeStmt->execute([$s['id'], $active_module_id]);
                        $grade = $gradeStmt->fetchColumn();
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['nom'] . ' ' . $s['prenom']); ?></td>
                        <td><?php echo htmlspecialchars($s['niveau']); ?></td>
                        <td>
                            <input type="number" step="0.25" min="0" max="20" name="notes[<?php echo $s['id']; ?>]" value="<?php echo htmlspecialchars($grade !== false ? $grade : ''); ?>" style="width:100px; margin-bottom:0px;">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <button type="submit" class="btn">Save Grades</button>
            </form>
        <?php else: ?>
            <p>You have no modules assigned.</p>
        <?php endif; ?>

    <?php elseif ($role === 'Etudiant'): ?>
        <h2>Student Dashboard</h2>
        
        <?php
        $etu_id = $db->query("SELECT id_ref FROM utilisateurs WHERE id = $user_id")->fetchColumn();
        $student_info = $db->query("SELECT * FROM etudiants WHERE id = $etu_id")->fetch(PDO::FETCH_ASSOC);
        ?>
        <p>Welcome, <strong><?php echo htmlspecialchars($student_info['nom'] . ' ' . $student_info['prenom']); ?></strong></p>
        <p>Level: <strong><?php echo htmlspecialchars($student_info['niveau']); ?></strong></p>
        
        <div style="margin-bottom: 20px;">
            <a href="transcript.php" class="btn" target="_blank">Print Transcript</a>
        </div>

        <table>
            <tr>
                <th>Module</th>
                <th>Coefficient</th>
                <th>Grade</th>
            </tr>
            <?php
            $stmt = $db->prepare("
                SELECT m.intitule as nom_module, m.coefficient, n.note 
                FROM modules m
                LEFT JOIN notes n ON m.id = n.id_module AND n.id_etudiant = ?
            ");
            $stmt->execute([$etu_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total_points = 0;
            $total_coeffs = 0;

            foreach ($results as $r): 
                if ($r['note'] !== null) {
                    $total_points += $r['note'] * $r['coefficient'];
                    $total_coeffs += $r['coefficient'];
                }
            ?>
            <tr>
                <td><?php echo htmlspecialchars($r['nom_module']); ?></td>
                <td><?php echo $r['coefficient']; ?></td>
                <td><?php echo ($r['note'] !== null) ? $r['note'] : 'N/A'; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <?php if ($total_coeffs > 0): ?>
            <?php $avg = $total_points / $total_coeffs; ?>
            <div class="alert" style="text-align: center; font-size: 20px; font-weight: bold;">
                Average: <?php echo number_format($avg, 2); ?> / 20
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<div class="footer">
    © 2025/2026 University Portal - Simple Version
</div>

</body>
</html>
