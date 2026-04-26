<?php
// dashboard.php - Merged dashboard for all type_of_accounts
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$type_of_account= $_SESSION['user_type_of_account'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

try {
    $db = new PDO('mysql:host=localhost;dbname=gestion_scolarite;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

// Helper: compute weighted average for a student
function getStudentAverage(PDO $db, int $student_id): ?float {
    $student = $db->prepare("SELECT SUM(n.note * m.coefficient) / SUM(m.coefficient) as avg FROM notes n JOIN modules m ON n.id_module = m.id WHERE n.id_etudiant = ?");
    $student->execute([$student_id]);
    $val = $student->fetchColumn();
    return ($val !== false && $val !== null) ? (float)$val : null;
}

$message = '';
$active_tab = $_GET['tab'] ?? 'students';

// Admin POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $type_of_account === 'Admin') {

    // Edit student
    if (isset($_POST['action']) && $_POST['action'] === 'edit_student') {
        $student_id = (int) $_POST['studentid'];
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $date_of_birth = $_POST['date_of_birth'];
        $niveau = $_POST['niveau'];
        if ($nom && $prenom && $email) {
            $db->prepare('UPDATE etu// dashboard.php - Merged dashboard for all type_of_accountsdiants SET nom=?, prenom=?, email=?, date_naissance=?, niveau=? WHERE id=?')
                ->execute([$nom, $prenom, $email, $date_of_birth, $niveau, $student_id]);
            $message = "Student updated.";
        }
        $active_tab = 'students';
    }

    // Add module
    if (isset($_POST['action']) && $_POST['action'] === 'add_module') {
        $code = trim($_POST['code_module']);
        $intitule = trim($_POST['intitule']);
        $coeff = (int) $_POST['coefficient'];
        $teacher_id_value = (int) $_POST['id_enseignant'];
        if ($code && $intitule && $coeff && $teacher_id_value) {
            $db->prepare('INSERT INTO modules (code_module, intitule, coefficient, id_enseignant) VALUES (?,?,?,?)')
                ->execute([$code, $intitule, $coeff, $teacher_id_value]);
            $message = "Module added.";
        }
        $active_tab = 'modules';
    }

    // Edit module
    if (isset($_POST['action']) && $_POST['action'] === 'edit_module') {
        $module_id = (int) $_POST['mid'];
        $intitule = trim($_POST['intitule']);
        $coeff = (int) $_POST['coefficient'];
        $teacher_id_value = (int) $_POST['id_enseignant'];
        if ($intitule && $coeff && $teacher_id_value) {
            $db->prepare('UPDATE modules SET intitule=?, coefficient=?, id_enseignant=? WHERE id=?')
                ->execute([$intitule, $coeff, $teacher_id_value, $module_id]);
            $message = "Module updated.";
        }
        $active_tab = 'modules';
    }

    // Add teacher
    if (isset($_POST['action']) && $_POST['action'] === 'add_teacher') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $specialite = trim($_POST['specialite'] ?? '');
        if ($nom && $prenom && $email && $specialite) {
            $check_existing = $db->prepare('SELECT COUNT(*) FROM enseignants WHERE email = ?');
            $check_existing->execute([$email]);
            if ($check_existing->fetchColumn() > 0) {
                $message = "A teacher with this email already exists.";
            } else {
                $db->prepare('INSERT INTO enseignants (nom, prenom, email, specialite) VALUES (?,?,?,?)')
                    ->execute([$nom, $prenom, $email, $specialite]);
                $teacher_id_value = $db->lastInsertId();
                $hashed_password = password_hash('prof123', PASSWORD_DEFAULT);
                $db->prepare("INSERT INTO utilisateurs (username, password, type_of_account, id_ref) VALUES (?,?,'Enseignant',?)")
                    ->execute([$email, $hashed_password, $teacher_id_value]);
                $message = "Teacher added. Login: $email / Password: prof123";
            }
        } else {
            $message = "Please fill in all fields";
        }
        $active_tab = 'teachers';
    }

    // Edit teacher
    if (isset($_POST['action']) && $_POST['action'] === 'edit_teacher') {
        $teacher_id = (int)$_POST['tid'];
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $specialite = trim($_POST['specialite'] ?? '');
        if ($nom && $prenom && $email && $specialite) {
            $db->prepare('UPDATE enseignants SET nom=?, prenom=?, email=?, specialite=? WHERE id=?')
                ->execute([$nom, $prenom, $email, $specialite, $teacher_id]);
            $message = "Teacher updated.";
        }
        $active_tab = 'teachers';
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $type_of_account === 'Enseignant') {
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
if ($type_of_account === 'Admin' && isset($_GET['delete_student'])) {
    $id = (int) $_GET['delete_student'];
    $db->prepare('DELETE FROM utilisateurs WHERE id_ref=? AND type_of_account="Etudiant"')->execute([$id]);
    $db->prepare('DELETE FROM etudiants WHERE id=?')->execute([$id]);
    $message = "Student deleted.";
    $active_tab = 'students';
}

if ($type_of_account === 'Admin' && isset($_GET['delete_module'])) {
    $id = (int) $_GET['delete_module'];
    $db->prepare('DELETE FROM modules WHERE id=?')->execute([$id]);
    $message = "Module deleted.";
    $active_tab = 'modules';
}

if ($type_of_account === 'Admin' && isset($_GET['delete_teacher'])) {
    $id = (int) $_GET['delete_teacher'];
    $db->prepare('DELETE FROM utilisateurs WHERE id_ref=? AND type_of_account="Enseignant"')->execute([$id]);
    $db->prepare('DELETE FROM enseignants WHERE id=?')->execute([$id]);
    $message = "Teacher deleted.";
    $active_tab = 'teachers';
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
            <img src="logo.png?v=2" alt="Logo">
            <h1>USTHB</h1>
        </div>
        <div class="header-links">
            <a href="home.php?logout=1">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="user-badge">
            Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong>
            (<?php echo htmlspecialchars($type_of_account); ?>)
        </div>

        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- ======================== ADMIN ======================== -->
        <?php if ($type_of_account === 'Admin'): ?>
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
                    <a href="create_student.php" class="button">+ Add Student</a>
                </div>

                <form method="GET" action="dashboard.php" class="search-bar">
                    <input type="hidden" name="tab" value="students">
                    <input type="search" name="search" placeholder="Search by name or matricule..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="button">Search</button>
                    <?php if ($search): ?><a href="dashboard.php?tab=students"
                            class="button button-secondary">Clear</a><?php endif; ?>
                </form>

                <?php
                $sql = "SELECT e.*, u.username FROM etudiants e LEFT JOIN utilisateurs u ON u.id_ref=e.id AND u.type_of_account='Etudiant'";
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
                        <th>Moyenne</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($students as $student): ?>
                        <?php if ($edit_id === $student['id']): ?>
                            <tr>
                                <td colspan="8">
                                    <form method="POST" action="dashboard.php?tab=students" class="inline-edit-form">
                                        <input type="hidden" name="action" value="edit_student">
                                        <input type="hidden" name="eid" value="<?php echo $student['id']; ?>">
                                        <input type="text" name="nom" value="<?php echo htmlspecialchars($student['nom']); ?>"
                                            placeholder="Last Name" required>
                                        <input type="text" name="prenom" value="<?php echo htmlspecialchars($student['prenom']); ?>"
                                            placeholder="First Name" required>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>"
                                            placeholder="Email" required>
                                        <input type="date" name="date_naissance" value="<?php echo $student['date_naissance']; ?>" required>
                                        <select name="niveau">
                                            <?php foreach (['L1 Informatique', 'L2 Informatique', 'L3 Informatique'] as $n): ?>
                                                <option value="<?php echo $n; ?>" <?php echo $student['niveau'] === $n ? 'selected' : ''; ?>>
                                                    <?php echo $n; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="button button-success button-sm">Save</button>
                                        <a href="dashboard.php?tab=students" class="button button-secondary button-sm">Cancel</a>
                                    </form>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <?php
                                $average_grade = getStudentAverage($db, $student['id']);
                                $student_status = ($average_grade !== null) ? ($average_grade >= 10 ? 'Admis' : 'Ajourné') : '—';
                                $status_color = ($average_grade !== null && $average_grade >= 10) ? '#3B6D11' : '#A32D2D';
                                $status_background = ($average_grade !== null && $average_grade >= 10) ? '#EAF3DE' : '#FCEBEB';
                                ?>
                                <td><?php echo htmlspecialchars($student['matricule']); ?></td>
                                <td><?php echo htmlspecialchars($student['nom']); ?></td>
                                <td><?php echo htmlspecialchars($student['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['niveau']); ?></td>
                                <td><?php echo ($average_grade !== null) ? number_format($average_grade, 2) . ' / 20' : 'N/A'; ?></td>
                                <td><span style="background:<?php echo $status_background; ?>;color:<?php echo $status_color; ?>;padding:3px 10px;border-radius:6px;font-size:13px;font-weight:500;"><?php echo $student_status; ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: nowrap;">
                                        <a href="dashboard.php?tab=students&edit_student=<?php echo $student['id']; ?>"
                                            class="button button-warning button-sm">Edit</a>
                                        <a href="dashboard.php?tab=students&delete_student=<?php echo $student['id']; ?>"
                                            class="button button-danger button-sm" onclick="return confirm('Delete this student?');">Delete</a>
                                    </div>
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
                    <form method="POST" action="dashboard.php?tab=modules" class="inline-edit-form">
                        <input type="hidden" name="action" value="add_module">
                        <input type="text" name="code_module" placeholder="Code (e.g. PROGWEB)" required>
                        <input type="text" name="intitule" placeholder="Module Name" required>
                        <input type="number" name="coefficient" placeholder="Coeff" min="1" max="10" value="2" required
                            style="width:90px;">
                        <select name="id_enseignant" required>
                            <option value="">-- Teacher --</option>
                            <?php foreach ($teachers_all as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['nom'] . ' ' . $teacher['prenom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="button button-sm">Add</button>
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
                    <?php foreach ($modules_all as $module): ?>
                        <?php if ($edit_mod === (int) $module['id']): ?>
                            <tr>
                                <td colspan="5">
                                    <form method="POST" action="dashboard.php?tab=modules" class="inline-edit-form">
                                        <input type="hidden" name="action" value="edit_module">
                                        <input type="hidden" name="mid" value="<?php echo $module['id']; ?>">
                                        <input type="text" name="intitule" value="<?php echo htmlspecialchars($module['intitule']); ?>"
                                            required>
                                        <input type="number" name="coefficient" value="<?php echo $module['coefficient']; ?>" min="1"
                                            max="10" required style="width:90px;">
                                        <select name="id_enseignant" required>
                                            <?php foreach ($teachers_all as $teacher): ?>
                                                <option value="<?php echo $teacher['id']; ?>" <?php echo $module['id_enseignant'] == $teacher['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($teacher['nom'] . ' ' . $teacher['prenom']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="button button-success button-sm">Save</button>
                                        <a href="dashboard.php?tab=modules" class="button button-secondary button-sm">Cancel</a>
                                    </form>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td><?php echo htmlspecialchars($module['code_module']); ?></td>
                                <td><?php echo htmlspecialchars($module['intitule']); ?></td>
                                <td><?php echo $module['coefficient']; ?></td>
                                <td><?php echo htmlspecialchars($module['ens_nom'] . ' ' . $module['ens_prenom']); ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: nowrap;">
                                        <a href="dashboard.php?tab=modules&edit_module=<?php echo $module['id']; ?>"
                                            class="button button-warning button-sm">Edit</a>
                                        <a href="dashboard.php?tab=modules&delete_module=<?php echo $module['id']; ?>"
                                            class="button button-danger button-sm" onclick="return confirm('Delete this module?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>

                <!-- ===== TEACHERS TAB ===== -->
            <?php elseif ($active_tab === 'teachers'): ?>
                <h3 style="margin-bottom:20px;">Manage Teachers</h3>
                <?php
                $teachers_list = $db->query("SELECT * FROM enseignants ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
                $edit_tid = isset($_GET['edit_teacher']) ? (int)$_GET['edit_teacher'] : 0;
                ?>

                <!-- Add teacher form -->
                <div class="card">
                    <h3>Add New Teacher</h3>
                    <form method="POST" action="dashboard.php?tab=teachers" class="inline-edit-form">
                        <input type="hidden" name="action" value="add_teacher">
                        <input type="text" name="nom" placeholder="Last Name" required>
                        <input type="text" name="prenom" placeholder="First Name" required>
                        <input type="email" name="email" placeholder="Email (used as login)" required>
                        <input type="text" name="specialite" placeholder="Specialty" required>
                        <button type="submit" class="button button-sm">Add</button>
                    </form>
                </div>

                <table>
                    <tr>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Email</th>
                        <th>Specialty</th>
                        <th>Modules</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($teachers_list as $teacher): ?>
                        <?php if ($edit_tid === (int)$teacher['id']): ?>
                            <tr>
                                <td colspan="6">
                                    <form method="POST" action="dashboard.php?tab=teachers" class="inline-edit-form">
                                        <input type="hidden" name="action" value="edit_teacher">
                                        <input type="hidden" name="tid" value="<?php echo $teacher['id']; ?>">
                                        <input type="text" name="nom" value="<?php echo htmlspecialchars($teacher['nom']); ?>" placeholder="Last Name" required>
                                        <input type="text" name="prenom" value="<?php echo htmlspecialchars($teacher['prenom']); ?>" placeholder="First Name" required>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" placeholder="Email" required>
                                        <input type="text" name="specialite" value="<?php echo htmlspecialchars($teacher['specialite']); ?>" placeholder="Specialty" required>
                                        <button type="submit" class="button button-success button-sm">Save</button>
                                        <a href="dashboard.php?tab=teachers" class="button button-secondary button-sm">Cancel</a>
                                    </form>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <?php
                                $modules_query = $db->prepare("SELECT intitule FROM modules WHERE id_enseignant=?");
                                $modules_query->execute([$teacher['id']]);
                                $teacher_modules_list = $modules_query->fetchAll(PDO::FETCH_COLUMN);
                                ?>
                                <td><?php echo htmlspecialchars($teacher['nom']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['specialite']); ?></td>
                                <td><?php echo $teacher_modules_list ? htmlspecialchars(implode(', ', $teacher_modules_list)) : '<em>None</em>'; ?></td>
                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: nowrap;">
                                        <a href="dashboard.php?tab=teachers&edit_teacher=<?php echo $teacher['id']; ?>" class="button button-warning button-sm">Edit</a>
                                        <a href="dashboard.php?tab=teachers&delete_teacher=<?php echo $teacher['id']; ?>" class="button button-danger button-sm" onclick="return confirm('Delete this teacher and their account?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>

                <!-- GRADES TAB -->
            <?php elseif ($active_tab === 'grades'): ?>
                <h3 style="margin-bottom:20px;">Manage Grades</h3>
                <?php
                $all_modules = $db->query("SELECT m.*, e.nom as ens_nom FROM modules m JOIN enseignants e ON m.id_enseignant=e.id ORDER BY m.id")->fetchAll(PDO::FETCH_ASSOC);
                $sel_module = $_GET['module'] ?? ($all_modules[0]['id'] ?? 0);
                ?>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                    <?php foreach ($all_modules as $module_item): ?>
                        <a href="dashboard.php?tab=grades&module=<?php echo $module_item['id']; ?>"
                            class="button button-sm <?php echo ($sel_module == $module_item['id']) ? '' : 'button-secondary'; ?>"><?php echo htmlspecialchars($module_item['intitule']); ?></a>
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
                            foreach ($all_students as $student_item):
                                $grade_statement = $db->prepare('SELECT note FROM notes WHERE id_etudiant=? AND id_module=?');
                                $grade_statement->execute([$student_item['id'], $sel_module]);
                                $grade_value = $grade_statement->fetchColumn();
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student_item['nom'] . ' ' . $student_item['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($student_item['niveau']); ?></td>
                                    <td><input type="number" step="0.25" min="0" max="20" name="notes[<?php echo $student_item['id']; ?>]"
                                            value="<?php echo htmlspecialchars($grade_value !== false ? $grade_value : ''); ?>"
                                            style="width:100px;margin-bottom:0;"></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        <button type="submit" class="button">Save Grades</button>
                    </form>
                <?php endif; ?>

            <?php endif; ?>

            <!-- Teacher -->
        <?php elseif ($type_of_account === 'Enseignant'): ?>
            <h2>Teacher Dashboard</h2>
            <?php
            $teacher_statement = $db->prepare("SELECT id_ref FROM utilisateurs WHERE id = ?");
            $teacher_statement->execute([$user_id]);
            $teacher_id_value = $teacher_statement->fetchColumn();
            $modules = $db->prepare('SELECT * FROM modules WHERE id_enseignant = ?');
            $modules->execute([$teacher_id_value]);
            $my_modules = $modules->fetchAll(PDO::FETCH_ASSOC);
            $active_module_id = $_GET['module'] ?? ($my_modules[0]['id'] ?? 0);
            ?>
            <p>Select a module to grade:</p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                <?php foreach ($my_modules as $module): ?>
                    <a href="dashboard.php?module=<?php echo $module['id']; ?>"
                        class="button button-sm <?php echo ($active_module_id == $module['id']) ? '' : 'button-secondary'; ?>"><?php echo htmlspecialchars($module['intitule']); ?></a>
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
                        foreach ($students->fetchAll() as $student):
                            $gradeStmt = $db->prepare('SELECT note FROM notes WHERE id_etudiant=? AND id_module=?');
                            $gradeStmt->execute([$student['id'], $active_module_id]);
                            $grade = $gradeStmt->fetchColumn();
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($student['niveau']); ?></td>
                                <td><input type="number" step="0.25" min="0" max="20" name="notes[<?php echo $student['id']; ?>]"
                                        value="<?php echo htmlspecialchars($grade !== false ? $grade : ''); ?>"
                                        style="width:100px;margin-bottom:0;"></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <button type="submit" class="button">Save Grades</button>
                </form>
            <?php else: ?>
                <p>You have no modules assigned.</p>
            <?php endif; ?>

            <!-- student  -->
        <?php elseif ($type_of_account === 'Etudiant'): ?>
            <h2>Student Dashboard</h2>
            <?php
            $student_statement = $db->prepare("SELECT id_ref FROM utilisateurs WHERE id = ?");
            $student_statement->execute([$user_id]);
            $student_id = $student_statement->fetchColumn();
            $student_info_statement = $db->prepare("SELECT * FROM etudiants WHERE id = ?");
            $student_info_statement->execute([$student_id]);
            $student_info = $student_info_statement->fetch(PDO::FETCH_ASSOC);
            ?>
            <p>Welcome, <strong><?php echo htmlspecialchars($student_info['nom'] . ' ' . $student_info['prenom']); ?></strong>
            </p>
            <p>Level: <strong><?php echo htmlspecialchars($student_info['niveau']); ?></strong></p>
            <div style="margin-bottom:20px;">
                <a href="transcript.php" class="button" target="_blank">Print Transcript</a>
            </div>
            <table>
                <tr>
                    <th>Module</th>
                    <th>Coefficient</th>
                    <th>Grade</th>
                </tr>
                <?php
                $stmt = $db->prepare("SELECT m.intitule as nom_module, m.coefficient, n.note FROM modules m LEFT JOIN notes n ON m.id=n.id_module AND n.id_etudiant=?");
                $stmt->execute([$student_id]);
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
                <?php $average_grade = $total_points / $total_coeffs; ?>
                <div class="alert" style="text-align:center;font-size:20px;font-weight:bold;">
                    Average: <?php echo number_format($average_grade, 2); ?> / 20
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <div class="footer">© 2025/2026 University Portal</div>

</body>

</html>