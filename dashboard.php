<?php
// dashboard.php - Merged dashboard for all roles
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['user_role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

try {
    $db = new PDO('mysql:host=localhost;dbname=gestion_scolarite;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

$message = '';
$active_tab = $_GET['tab'] ?? 'students';

// --- Admin POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Admin') {

    // Edit student
    if (isset($_POST['action']) && $_POST['action'] === 'edit_student') {
        $eid = (int) $_POST['eid'];
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $dob = $_POST['date_naissance'];
        $niveau = $_POST['niveau'];
        if ($nom && $prenom && $email) {
            $db->prepare('UPDATE etudiants SET nom=?, prenom=?, email=?, date_naissance=?, niveau=? WHERE id=?')
                ->execute([$nom, $prenom, $email, $dob, $niveau, $eid]);
            $message = "Student updated.";
        }
        $active_tab = 'students';
    }

    // Add module
    if (isset($_POST['action']) && $_POST['action'] === 'add_module') {
        $code = trim($_POST['code_module']);
        $intitule = trim($_POST['intitule']);
        $coeff = (int) $_POST['coefficient'];
        $ens_id = (int) $_POST['id_enseignant'];
        if ($code && $intitule && $coeff && $ens_id) {
            $db->prepare('INSERT INTO modules (code_module, intitule, coefficient, id_enseignant) VALUES (?,?,?,?)')
                ->execute([$code, $intitule, $coeff, $ens_id]);
            $message = "Module added.";
        }
        $active_tab = 'modules';
    }

    // Edit module
    if (isset($_POST['action']) && $_POST['action'] === 'edit_module') {
        $mid = (int) $_POST['mid'];
        $intitule = trim($_POST['intitule']);
        $coeff = (int) $_POST['coefficient'];
        $ens_id = (int) $_POST['id_enseignant'];
        if ($intitule && $coeff && $ens_id) {
            $db->prepare('UPDATE modules SET intitule=?, coefficient=?, id_enseignant=? WHERE id=?')
                ->execute([$intitule, $coeff, $ens_id, $mid]);
            $message = "Module updated.";
        }
        $active_tab = 'modules';
    }

    // Save grades (admin)
    if (isset($_POST['action']) && $_POST['action'] === 'admin_save_grades') {
        $module_id = (int) $_POST['module_id'];
        if (isset($_POST['notes']) && is_array($_POST['notes'])) {
            foreach ($_POST['notes'] as $id_etudiant => $valeur) {
                if ($valeur !== '') {
                    $stmt = $db->prepare('SELECT id FROM notes WHERE id_etudiant=? AND id_module=?');
                    $stmt->execute([$id_etudiant, $module_id]);
                    if ($stmt->fetch()) {
                        $db->prepare('UPDATE notes SET note=? WHERE id_etudiant=? AND id_module=?')
                            ->execute([$valeur, $id_etudiant, $module_id]);
                    } else {
                        $db->prepare('INSERT INTO notes (id_etudiant, id_module, note) VALUES (?,?,?)')
                            ->execute([$id_etudiant, $module_id, $valeur]);
                    }
                }
            }
            $message = "Grades saved.";
        }
        $active_tab = 'grades';
    }
}

// Teacher POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Enseignant') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_grades') {
        $module_id = (int) $_POST['module_id'];
        if (isset($_POST['notes']) && is_array($_POST['notes'])) {
            foreach ($_POST['notes'] as $id_etudiant => $valeur) {
                if ($valeur !== '') {
                    $stmt = $db->prepare('SELECT id FROM notes WHERE id_etudiant=? AND id_module=?');
                    $stmt->execute([$id_etudiant, $module_id]);
                    if ($stmt->fetch()) {
                        $db->prepare('UPDATE notes SET note=? WHERE id_etudiant=? AND id_module=?')
                            ->execute([$valeur, $id_etudiant, $module_id]);
                    } else {
                        $db->prepare('INSERT INTO notes (id_etudiant, id_module, note) VALUES (?,?,?)')
                            ->execute([$id_etudiant, $module_id, $valeur]);
                    }
                }
            }
            $message = "Grades saved.";
        }
    }
}

// Admin GET actions
if ($role === 'Admin' && isset($_GET['delete_student'])) {
    $id = (int) $_GET['delete_student'];
    $db->prepare('DELETE FROM utilisateurs WHERE id_ref=? AND role="Etudiant"')->execute([$id]);
    $db->prepare('DELETE FROM etudiants WHERE id=?')->execute([$id]);
    $message = "Student deleted.";
    $active_tab = 'students';
}

if ($role === 'Admin' && isset($_GET['delete_module'])) {
    $id = (int) $_GET['delete_module'];
    $db->prepare('DELETE FROM modules WHERE id=?')->execute([$id]);
    $message = "Module deleted.";
    $active_tab = 'modules';
}

// Stats
$stats = ['etudiants' => 0, 'enseignants' => 0, 'modules' => 0];
try {
    $stats['etudiants'] = $db->query("SELECT COUNT(*) FROM etudiants")->fetchColumn();
    $stats['enseignants'] = $db->query("SELECT COUNT(*) FROM enseignants")->fetchColumn();
    $stats['modules'] = $db->query("SELECT COUNT(*) FROM modules")->fetchColumn();
} catch (Exception $e) {
}

$search = trim($_GET['search'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — University Portal</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>

<body>

    <div class="header">
        <div style="display:flex;align-items:center;">
            <img src="logo.png" alt="Logo">
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

        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- ======================== ADMIN ======================== -->
        <?php if ($role === 'Admin'): ?>
            <h2>Admin Dashboard</h2>

            <!-- Stats -->
            <div class="stats-container">
                <div class="stat-card students">

                    <span class="stat-number"><?php echo $stats['etudiants']; ?></span>
                    <span class="stat-label">Students</span>
                </div>
                <div class="stat-card teachers">

                    <span class="stat-number"><?php echo $stats['enseignants']; ?></span>
                    <span class="stat-label">Teachers</span>
                </div>
                <div class="stat-card modules">

                    <span class="stat-number"><?php echo $stats['modules']; ?></span>
                    <span class="stat-label">Modules</span>
                </div>
            </div>

            <!-- Tabs -->
            <div class="tab-nav">
                <a href="dashboard.php?tab=students"
                    class="<?php echo $active_tab === 'students' ? 'active' : ''; ?>">Students</a>
                <a href="dashboard.php?tab=modules" class="<?php echo $active_tab === 'modules' ? 'active' : ''; ?>">Modules</a>
                <a href="dashboard.php?tab=teachers"
                    class="<?php echo $active_tab === 'teachers' ? 'active' : ''; ?>">Teachers</a>
                <a href="dashboard.php?tab=grades" class="<?php echo $active_tab === 'grades' ? 'active' : ''; ?>">Grades</a>
            </div>

            <!-- ===== STUDENTS TAB ===== -->
            <?php if ($active_tab === 'students'): ?>
                <div class="section-header">
                    <h3>Student List</h3>
                    <a href="create_student.php" class="btn">+ Add Student</a>
                </div>

                <form method="GET" action="dashboard.php" class="search-bar">
                    <input type="hidden" name="tab" value="students">
                    <input type="search" name="search" placeholder="Search by name or matricule..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Search</button>
                    <?php if ($search): ?><a href="dashboard.php?tab=students"
                            class="btn btn-secondary">Clear</a><?php endif; ?>
                </form>

                <?php
                $sql = "SELECT e.*, u.username FROM etudiants e LEFT JOIN utilisateurs u ON u.id_ref=e.id AND u.role='Etudiant'";
                if ($search) {
                    $sql .= " WHERE e.nom LIKE :s OR e.prenom LIKE :s OR e.matricule LIKE :s";
                }
                $sql .= " ORDER BY e.id DESC";
                $stmt = $db->prepare($sql);
                if ($search) {
                    $stmt->execute([':s' => "%$search%"]);
                } else {
                    $stmt->execute();
                }
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php $edit_id = isset($_GET['edit_student']) ? (int) $_GET['edit_student'] : 0; ?>

                <table>
                    <tr>
                        <th>Matricule</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Email</th>
                        <th>Level</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($students as $s): ?>
                        <?php if ($edit_id === $s['id']): ?>
                            <tr>
                                <td colspan="6">
                                    <form method="POST" action="dashboard.php?tab=students" class="edit-form-inline">
                                        <input type="hidden" name="action" value="edit_student">
                                        <input type="hidden" name="eid" value="<?php echo $s['id']; ?>">
                                        <input type="text" name="nom" value="<?php echo htmlspecialchars($s['nom']); ?>"
                                            placeholder="Last Name" required>
                                        <input type="text" name="prenom" value="<?php echo htmlspecialchars($s['prenom']); ?>"
                                            placeholder="First Name" required>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($s['email']); ?>"
                                            placeholder="Email" required>
                                        <input type="date" name="date_naissance" value="<?php echo $s['date_naissance']; ?>" required>
                                        <select name="niveau">
                                            <?php foreach (['L1 Informatique', 'L2 Informatique', 'L3 Informatique'] as $n): ?>
                                                <option value="<?php echo $n; ?>" <?php echo $s['niveau'] === $n ? 'selected' : ''; ?>>
                                                    <?php echo $n; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                                        <a href="dashboard.php?tab=students" class="btn btn-secondary btn-sm">Cancel</a>
                                    </form>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['matricule']); ?></td>
                                <td><?php echo htmlspecialchars($s['nom']); ?></td>
                                <td><?php echo htmlspecialchars($s['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><?php echo htmlspecialchars($s['niveau']); ?></td>
                                <td>
                                    <a href="dashboard.php?tab=students&edit_student=<?php echo $s['id']; ?>"
                                        class="btn btn-warning btn-sm">Edit</a>
                                    <a href="dashboard.php?tab=students&delete_student=<?php echo $s['id']; ?>"
                                        class="btn btn-danger btn-sm" onclick="return confirm('Delete this student?');">Delete</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>

                <!-- ===== MODULES TAB ===== -->
            <?php elseif ($active_tab === 'modules'): ?>
                <h3 style="margin-bottom:20px;">Manage Modules</h3>

                <?php
                $teachers_all = $db->query("SELECT id, nom, prenom FROM enseignants ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
                $modules_all = $db->query("SELECT m.*, e.nom as ens_nom, e.prenom as ens_prenom FROM modules m JOIN enseignants e ON m.id_enseignant=e.id ORDER BY m.id")->fetchAll(PDO::FETCH_ASSOC);
                $edit_mod = isset($_GET['edit_module']) ? (int) $_GET['edit_module'] : 0;
                ?>

                <!-- Add module form -->
                <div class="card">
                    <h3>Add New Module</h3>
                    <form method="POST" action="dashboard.php?tab=modules" class="edit-form-inline">
                        <input type="hidden" name="action" value="add_module">
                        <input type="text" name="code_module" placeholder="Code (e.g. PROGWEB)" required>
                        <input type="text" name="intitule" placeholder="Module Name" required>
                        <input type="number" name="coefficient" placeholder="Coeff" min="1" max="10" value="2" required
                            style="width:90px;">
                        <select name="id_enseignant" required>
                            <option value="">-- Teacher --</option>
                            <?php foreach ($teachers_all as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nom'] . ' ' . $t['prenom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm">Add</button>
                    </form>
                </div>

                <table>
                    <tr>
                        <th>Code</th>
                        <th>Module</th>
                        <th>Coeff</th>
                        <th>Teacher</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($modules_all as $m): ?>
                        <?php if ($edit_mod === (int) $m['id']): ?>
                            <tr>
                                <td colspan="5">
                                    <form method="POST" action="dashboard.php?tab=modules" class="edit-form-inline">
                                        <input type="hidden" name="action" value="edit_module">
                                        <input type="hidden" name="mid" value="<?php echo $m['id']; ?>">
                                        <input type="text" name="intitule" value="<?php echo htmlspecialchars($m['intitule']); ?>"
                                            required>
                                        <input type="number" name="coefficient" value="<?php echo $m['coefficient']; ?>" min="1"
                                            max="10" required style="width:90px;">
                                        <select name="id_enseignant" required>
                                            <?php foreach ($teachers_all as $t): ?>
                                                <option value="<?php echo $t['id']; ?>" <?php echo $m['id_enseignant'] == $t['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($t['nom'] . ' ' . $t['prenom']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                                        <a href="dashboard.php?tab=modules" class="btn btn-secondary btn-sm">Cancel</a>
                                    </form>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['code_module']); ?></td>
                                <td><?php echo htmlspecialchars($m['intitule']); ?></td>
                                <td><?php echo $m['coefficient']; ?></td>
                                <td><?php echo htmlspecialchars($m['ens_nom'] . ' ' . $m['ens_prenom']); ?></td>
                                <td>
                                    <a href="dashboard.php?tab=modules&edit_module=<?php echo $m['id']; ?>"
                                        class="btn btn-warning btn-sm">Edit</a>
                                    <a href="dashboard.php?tab=modules&delete_module=<?php echo $m['id']; ?>"
                                        class="btn btn-danger btn-sm" onclick="return confirm('Delete this module?');">Delete</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>

                <!-- ===== TEACHERS TAB ===== -->
            <?php elseif ($active_tab === 'teachers'): ?>
                <h3 style="margin-bottom:20px;">Teachers & Assigned Modules</h3>
                <?php
                $teachers_list = $db->query("SELECT * FROM enseignants ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <?php foreach ($teachers_list as $t): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($t['nom'] . ' ' . $t['prenom']); ?></h3>
                        <p style="margin-bottom:4px;"><strong>Email:</strong> <?php echo htmlspecialchars($t['email']); ?></p>
                        <p style="margin-bottom:8px;"><strong>Specialty:</strong> <?php echo htmlspecialchars($t['specialite']); ?>
                        </p>
                        <?php
                        $mods = $db->prepare("SELECT intitule, code_module FROM modules WHERE id_enseignant=?");
                        $mods->execute([$t['id']]);
                        $t_modules = $mods->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        <?php if ($t_modules): ?>
                            <p style="margin-bottom:4px;"><strong>Modules:</strong></p>
                            <ul style="padding-left:20px;">
                                <?php foreach ($t_modules as $tm): ?>
                                    <li><?php echo htmlspecialchars($tm['intitule']); ?>
                                        (<?php echo htmlspecialchars($tm['code_module']); ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p><em>No modules assigned.</em></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <!-- ===== GRADES TAB ===== -->
            <?php elseif ($active_tab === 'grades'): ?>
                <h3 style="margin-bottom:20px;">Manage Grades</h3>
                <?php
                $all_modules = $db->query("SELECT m.*, e.nom as ens_nom FROM modules m JOIN enseignants e ON m.id_enseignant=e.id ORDER BY m.id")->fetchAll(PDO::FETCH_ASSOC);
                $sel_module = $_GET['module'] ?? ($all_modules[0]['id'] ?? 0);
                ?>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                    <?php foreach ($all_modules as $am): ?>
                        <a href="dashboard.php?tab=grades&module=<?php echo $am['id']; ?>"
                            class="btn btn-sm <?php echo ($sel_module == $am['id']) ? '' : 'btn-secondary'; ?>"><?php echo htmlspecialchars($am['intitule']); ?></a>
                    <?php endforeach; ?>
                </div>

                <?php if ($sel_module): ?>
                    <form method="POST" action="dashboard.php?tab=grades&module=<?php echo $sel_module; ?>">
                        <input type="hidden" name="action" value="admin_save_grades">
                        <input type="hidden" name="module_id" value="<?php echo $sel_module; ?>">
                        <table>
                            <tr>
                                <th>Student</th>
                                <th>Level</th>
                                <th>Grade (/20)</th>
                            </tr>
                            <?php
                            $all_students = $db->query("SELECT id, nom, prenom, niveau FROM etudiants ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($all_students as $as):
                                $gs = $db->prepare('SELECT note FROM notes WHERE id_etudiant=? AND id_module=?');
                                $gs->execute([$as['id'], $sel_module]);
                                $g = $gs->fetchColumn();
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($as['nom'] . ' ' . $as['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($as['niveau']); ?></td>
                                    <td><input type="number" step="0.25" min="0" max="20" name="notes[<?php echo $as['id']; ?>]"
                                            value="<?php echo htmlspecialchars($g !== false ? $g : ''); ?>"
                                            style="width:100px;margin-bottom:0;"></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        <button type="submit" class="btn">Save Grades</button>
                    </form>
                <?php endif; ?>

            <?php endif; ?>

            <!-- ======================== TEACHER ======================== -->
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
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                <?php foreach ($my_modules as $m): ?>
                    <a href="dashboard.php?module=<?php echo $m['id']; ?>"
                        class="btn btn-sm <?php echo ($active_module_id == $m['id']) ? '' : 'btn-secondary'; ?>"><?php echo htmlspecialchars($m['intitule']); ?></a>
                <?php endforeach; ?>
            </div>

            <?php if ($active_module_id): ?>
                <form method="POST" action="dashboard.php?module=<?php echo $active_module_id; ?>">
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
                            $gradeStmt = $db->prepare('SELECT note FROM notes WHERE id_etudiant=? AND id_module=?');
                            $gradeStmt->execute([$s['id'], $active_module_id]);
                            $grade = $gradeStmt->fetchColumn();
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['nom'] . ' ' . $s['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($s['niveau']); ?></td>
                                <td><input type="number" step="0.25" min="0" max="20" name="notes[<?php echo $s['id']; ?>]"
                                        value="<?php echo htmlspecialchars($grade !== false ? $grade : ''); ?>"
                                        style="width:100px;margin-bottom:0;"></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <button type="submit" class="btn">Save Grades</button>
                </form>
            <?php else: ?>
                <p>You have no modules assigned.</p>
            <?php endif; ?>

            <!-- ======================== STUDENT ======================== -->
        <?php elseif ($role === 'Etudiant'): ?>
            <h2>Student Dashboard</h2>
            <?php
            $etu_id = $db->query("SELECT id_ref FROM utilisateurs WHERE id = $user_id")->fetchColumn();
            $student_info = $db->query("SELECT * FROM etudiants WHERE id = $etu_id")->fetch(PDO::FETCH_ASSOC);
            ?>
            <p>Welcome, <strong><?php echo htmlspecialchars($student_info['nom'] . ' ' . $student_info['prenom']); ?></strong>
            </p>
            <p>Level: <strong><?php echo htmlspecialchars($student_info['niveau']); ?></strong></p>
            <div style="margin-bottom:20px;">
                <a href="transcript.php" class="btn" target="_blank">Print Transcript</a>
            </div>
            <table>
                <tr>
                    <th>Module</th>
                    <th>Coefficient</th>
                    <th>Grade</th>
                </tr>
                <?php
                $stmt = $db->prepare("SELECT m.intitule as nom_module, m.coefficient, n.note FROM modules m LEFT JOIN notes n ON m.id=n.id_module AND n.id_etudiant=?");
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
                <div class="alert" style="text-align:center;font-size:20px;font-weight:bold;">
                    Average: <?php echo number_format($avg, 2); ?> / 20
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <div class="footer">© 2025/2026 University Portal</div>

</body>

</html>