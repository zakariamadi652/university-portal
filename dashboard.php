<?php
// dashboard.php - Merged dashboard for all roles (Simplified for beginners)
session_start();

// If the user is not logged in, send them to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_role = $_SESSION['user_role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Connect to the database
try {
    $db = new PDO('mysql:host=localhost;dbname=gestion_scolarite;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

$status_message = '';

// Get the active tab, default to 'students'
$active_tab = 'students';
if (isset($_GET['tab'])) {
    $active_tab = $_GET['tab'];
}


// Handle Admin Form Submissions (POST requests)

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_role == 'Admin') {

    // Action: Edit an existing student
    if (isset($_POST['action']) && $_POST['action'] == 'edit_student') {
        $student_id = $_POST['student_id'];
        $last_name = trim($_POST['last_name']);
        $first_name = trim($_POST['first_name']);
        $email = trim($_POST['email']);
        $date_of_birth = $_POST['date_of_birth'];
        $study_level = $_POST['study_level'];

        if ($last_name != '' && $first_name != '' && $email != '') {
            $update_student_query = $db->prepare('UPDATE students SET last_name=?, first_name=?, email=?, date_of_birth=?, study_level=? WHERE id=?');
            $update_student_query->execute([$last_name, $first_name, $email, $date_of_birth, $study_level, $student_id]);
            $status_message = "Student updated successfully.";
        }
        $active_tab = 'students';
    }

    // Action: Add a new module
    if (isset($_POST['action']) && $_POST['action'] == 'add_module') {
        $module_code = trim($_POST['module_code']);
        $module_name = trim($_POST['module_name']);
        $coefficient = $_POST['coefficient'];
        $teacher_id = $_POST['teacher_id'];

        if ($module_code != '' && $module_name != '' && $coefficient != '' && $teacher_id != '') {
            $insert_module_query = $db->prepare('INSERT INTO modules (module_code, module_name, coefficient, teacher_id) VALUES (?,?,?,?)');
            $insert_module_query->execute([$module_code, $module_name, $coefficient, $teacher_id]);
            $status_message = "Module added successfully.";
        }
        $active_tab = 'modules';
    }

    // Action: Edit an existing module
    if (isset($_POST['action']) && $_POST['action'] == 'edit_module') {
        $module_id = $_POST['module_id'];
        $module_name = trim($_POST['module_name']);
        $coefficient = $_POST['coefficient'];
        $teacher_id = $_POST['teacher_id'];

        if ($module_name != '' && $coefficient != '' && $teacher_id != '') {
            $update_module_query = $db->prepare('UPDATE modules SET module_name=?, coefficient=?, teacher_id=? WHERE id=?');
            $update_module_query->execute([$module_name, $coefficient, $teacher_id, $module_id]);
            $status_message = "Module updated successfully.";
        }
        $active_tab = 'modules';
    }

    // Action: Edit an existing teacher
    if (isset($_POST['action']) && $_POST['action'] == 'edit_teacher') {
        $teacher_id = $_POST['teacher_id'];
        $last_name = trim($_POST['last_name']);
        $first_name = trim($_POST['first_name']);
        $email = trim($_POST['email']);
        $specialty = trim($_POST['specialty']);

        if ($last_name != '' && $first_name != '' && $email != '' && $specialty != '') {
            $update_teacher_query = $db->prepare('UPDATE teachers SET last_name=?, first_name=?, email=?, specialty=? WHERE id=?');
            $update_teacher_query->execute([$last_name, $first_name, $email, $specialty, $teacher_id]);
            $status_message = "Teacher updated successfully.";
        }
        $active_tab = 'teachers';
    }

    // Action: Save grades (Admin)
    if (isset($_POST['action']) && $_POST['action'] == 'admin_save_grades') {
        $module_id = $_POST['module_id'];

        if (isset($_POST['grades'])) {
            $submitted_grades = $_POST['grades'];

            foreach ($submitted_grades as $student_id => $grade_value) {
                if ($grade_value != '') {
                    // Check if a grade already exists for this student and module
                    $check_grade_query = $db->prepare('SELECT id FROM grades WHERE student_id=? AND module_id=?');
                    $check_grade_query->execute([$student_id, $module_id]);
                    $existing_grade = $check_grade_query->fetch();

                    if ($existing_grade != false) {
                        // Update existing grade
                        $update_grade_query = $db->prepare('UPDATE grades SET grade_value=? WHERE student_id=? AND module_id=?');
                        $update_grade_query->execute([$grade_value, $student_id, $module_id]);
                    } else {
                        // Insert new grade
                        $insert_grade_query = $db->prepare('INSERT INTO grades (student_id, module_id, grade_value) VALUES (?,?,?)');
                        $insert_grade_query->execute([$student_id, $module_id, $grade_value]);
                    }
                }
            }
            $status_message = "Grades saved successfully.";
        }
        $active_tab = 'grades';
    }
}


// Handle Teacher Form Submissions (POST requests)

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_role == 'Teacher') {
    if (isset($_POST['action']) && $_POST['action'] == 'save_grades') {
        $module_id = $_POST['module_id'];

        if (isset($_POST['grades'])) {
            $submitted_grades = $_POST['grades'];

            foreach ($submitted_grades as $student_id => $grade_value) {
                if ($grade_value != '') {
                    // Check if a grade already exists
                    $check_grade_query = $db->prepare('SELECT id FROM grades WHERE student_id=? AND module_id=?');
                    $check_grade_query->execute([$student_id, $module_id]);
                    $existing_grade = $check_grade_query->fetch();

                    if ($existing_grade != false) {
                        // Update existing grade
                        $update_grade_query = $db->prepare('UPDATE grades SET grade_value=? WHERE student_id=? AND module_id=?');
                        $update_grade_query->execute([$grade_value, $student_id, $module_id]);
                    } else {
                        // Insert new grade
                        $insert_grade_query = $db->prepare('INSERT INTO grades (student_id, module_id, grade_value) VALUES (?,?,?)');
                        $insert_grade_query->execute([$student_id, $module_id, $grade_value]);
                    }
                }
            }
            $status_message = "Grades saved successfully.";
        }
    }
}


// Handle Admin Delete Actions (GET requests)

if ($user_role == 'Admin' && isset($_GET['delete_student'])) {
    $student_id_to_delete = $_GET['delete_student'];

    // First delete the user account linked to this student
    $delete_user_query = $db->prepare('DELETE FROM users WHERE reference_id=? AND role="Student"');
    $delete_user_query->execute([$student_id_to_delete]);

    // Then delete the student
    $delete_student_query = $db->prepare('DELETE FROM students WHERE id=?');
    $delete_student_query->execute([$student_id_to_delete]);

    $status_message = "Student deleted successfully.";
    $active_tab = 'students';
}

if ($user_role == 'Admin' && isset($_GET['delete_module'])) {
    $module_id_to_delete = $_GET['delete_module'];

    $delete_module_query = $db->prepare('DELETE FROM modules WHERE id=?');
    $delete_module_query->execute([$module_id_to_delete]);

    $status_message = "Module deleted successfully.";
    $active_tab = 'modules';
}

if ($user_role == 'Admin' && isset($_GET['delete_teacher'])) {
    $teacher_id_to_delete = $_GET['delete_teacher'];

    // First delete the user account linked to this teacher
    $delete_user_query = $db->prepare('DELETE FROM users WHERE reference_id=? AND role="Teacher"');
    $delete_user_query->execute([$teacher_id_to_delete]);

    // Then delete the teacher
    $delete_teacher_query = $db->prepare('DELETE FROM teachers WHERE id=?');
    $delete_teacher_query->execute([$teacher_id_to_delete]);

    $status_message = "Teacher deleted successfully.";
    $active_tab = 'teachers';
}


// Get Statistics for Admin Dashboard

$total_students = 0;
$total_teachers = 0;
$total_modules = 0;

try {
    $total_students = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $total_teachers = $db->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
    $total_modules = $db->query("SELECT COUNT(*) FROM modules")->fetchColumn();
} catch (Exception $e) {
    // Keep them at 0 if there's an error
}

// Get the search query if it exists
$search_query = '';
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}
?>
<!DOCTYPE html>
<html lang="en">

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
            <a href="index.php?logout=1">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="user-badge">
            Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong>
            (<?php echo htmlspecialchars($user_role); ?>)
        </div>

        <?php if ($status_message != '') { ?>
            <div class="alert"><?php echo htmlspecialchars($status_message); ?></div>
        <?php } ?>

        <!-- ======================== ADMIN DASHBOARD ======================== -->
        <?php if ($user_role == 'Admin') { ?>
            <h2>Admin Dashboard</h2>

            <!-- Stats -->
            <div class="stats-container">
                <div class="stat-card students">
                    <span class="stat-number"><?php echo $total_students; ?></span>
                    <span class="stat-label">Students</span>
                </div>
                <div class="stat-card teachers">
                    <span class="stat-number"><?php echo $total_teachers; ?></span>
                    <span class="stat-label">Teachers</span>
                </div>
                <div class="stat-card modules">
                    <span class="stat-number"><?php echo $total_modules; ?></span>
                    <span class="stat-label">Modules</span>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="tab-nav">
                <a href="dashboard.php?tab=students"
                    class="<?php if ($active_tab == 'students') {
                        echo 'active';
                    } ?>">Students</a>
                <a href="dashboard.php?tab=modules"
                    class="<?php if ($active_tab == 'modules') {
                        echo 'active';
                    } ?>">Modules</a>
                <a href="dashboard.php?tab=teachers"
                    class="<?php if ($active_tab == 'teachers') {
                        echo 'active';
                    } ?>">Teachers</a>
                <a href="dashboard.php?tab=grades"
                    class="<?php if ($active_tab == 'grades') {
                        echo 'active';
                    } ?>">Grades</a>
            </div>

            <!-- STUDENTS TAB -->
            <?php if ($active_tab == 'students') { ?>
                <div class="section-header">
                    <h3>Student List</h3>
                    <a href="create_student.php" class="btn">+ Add Student</a>
                </div>

                <form method="GET" action="dashboard.php" class="search-bar">
                    <input type="hidden" name="tab" value="students">
                    <input type="search" name="search" placeholder="Search by name or number..."
                        value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn">Search</button>
                    <?php if ($search_query != '') { ?>
                        <a href="dashboard.php?tab=students" class="btn btn-secondary">Clear</a>
                    <?php } ?>
                </form>

                <?php
                // Fetch students from the database
                $sql = "SELECT * FROM students";
                if ($search_query != '') {
                    $sql = $sql . " WHERE last_name LIKE :search_term OR first_name LIKE :search_term OR student_number LIKE :search_term";
                }
                $sql = $sql . " ORDER BY id DESC";

                $get_students_query = $db->prepare($sql);

                if ($search_query != '') {
                    $get_students_query->execute([':search_term' => "%$search_query%"]);
                } else {
                    $get_students_query->execute();
                }

                $student_list = $get_students_query->fetchAll(PDO::FETCH_ASSOC);

                // Check if a student is being edited
                $student_id_to_edit = 0;
                if (isset($_GET['edit_student'])) {
                    $student_id_to_edit = $_GET['edit_student'];
                }
                ?>

                <table>
                    <tr>
                        <th>Student Number</th>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Email</th>
                        <th>Level</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($student_list as $student) { ?>
                        <?php if ($student_id_to_edit == $student['id']) { ?>
                            <!-- Edit Student Form Row -->
                            <tr>
                                <td colspan="6">
                                    <form method="POST" action="dashboard.php?tab=students" class="edit-form-inline">
                                        <input type="hidden" name="action" value="edit_student">
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">

                                        <input type="text" name="last_name"
                                            value="<?php echo htmlspecialchars($student['last_name']); ?>" placeholder="Last Name"
                                            required>
                                        <input type="text" name="first_name"
                                            value="<?php echo htmlspecialchars($student['first_name']); ?>" placeholder="First Name"
                                            required>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>"
                                            placeholder="Email" required>
                                        <input type="date" name="date_of_birth" value="<?php echo $student['date_of_birth']; ?>"
                                            required>

                                        <select name="study_level">
                                            <option value="L1 Computer Science" <?php if ($student['study_level'] == 'L1 Computer Science')
                                                echo 'selected'; ?>>L1 Computer Science</option>
                                            <option value="L2 Computer Science" <?php if ($student['study_level'] == 'L2 Computer Science')
                                                echo 'selected'; ?>>L2 Computer Science</option>
                                            <option value="L3 Computer Science" <?php if ($student['study_level'] == 'L3 Computer Science')
                                                echo 'selected'; ?>>L3 Computer Science</option>
                                        </select>

                                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                                        <a href="dashboard.php?tab=students" class="btn btn-secondary btn-sm">Cancel</a>
                                    </form>
                                </td>
                            </tr>
                        <?php } else { ?>
                            <!-- Display Student Row -->
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['study_level']); ?></td>
                                <td>
                                    <a href="dashboard.php?tab=students&edit_student=<?php echo $student['id']; ?>"
                                        class="btn btn-warning btn-sm">Edit</a>
                                    <a href="dashboard.php?tab=students&delete_student=<?php echo $student['id']; ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </table>

                <!-- MODULES TAB -->
            <?php } elseif ($active_tab == 'modules') { ?>
                <h3 style="margin-bottom:20px;">Manage Modules</h3>

                <?php
                // Get all teachers for the dropdown menu
                $all_teachers = $db->query("SELECT id, last_name, first_name FROM teachers ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);

                // Get all modules with their teacher's name
                $all_modules = $db->query("SELECT modules.*, teachers.last_name as teacher_last_name, teachers.first_name as teacher_first_name FROM modules JOIN teachers ON modules.teacher_id = teachers.id ORDER BY modules.id")->fetchAll(PDO::FETCH_ASSOC);

                // Check if a module is being edited
                $module_id_to_edit = 0;
                if (isset($_GET['edit_module'])) {
                    $module_id_to_edit = $_GET['edit_module'];
                }
                ?>

                <!-- Add New Module Form -->
                <div class="card">
                    <h3>Add New Module</h3>
                    <form method="POST" action="dashboard.php?tab=modules" class="edit-form-inline">
                        <input type="hidden" name="action" value="add_module">

                        <input type="text" name="module_code" placeholder="Code (e.g. PROGWEB)" required>
                        <input type="text" name="module_name" placeholder="Module Name" required>
                        <input type="number" name="coefficient" placeholder="Coeff" min="1" max="10" value="2" required
                            style="width:90px;">

                        <select name="teacher_id" required>
                            <option value="">-- Select Teacher --</option>
                            <?php foreach ($all_teachers as $teacher) { ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                    <?php echo htmlspecialchars($teacher['last_name'] . ' ' . $teacher['first_name']); ?>
                                </option>
                            <?php } ?>
                        </select>

                        <button type="submit" class="btn btn-sm">Add Module</button>
                    </form>
                </div>

                <table>
                    <tr>
                        <th>Code</th>
                        <th>Module Name</th>
                        <th>Coefficient</th>
                        <th>Teacher</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($all_modules as $module) { ?>
                        <?php if ($module_id_to_edit == $module['id']) { ?>
                            <!-- Edit Module Form Row -->
                            <tr>
                                <td colspan="5">
                                    <form method="POST" action="dashboard.php?tab=modules" class="edit-form-inline">
                                        <input type="hidden" name="action" value="edit_module">
                                        <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">

                                        <input type="text" name="module_name"
                                            value="<?php echo htmlspecialchars($module['module_name']); ?>" required>
                                        <input type="number" name="coefficient" value="<?php echo $module['coefficient']; ?>" min="1"
                                            max="10" required style="width:90px;">

                                        <select name="teacher_id" required>
                                            <?php foreach ($all_teachers as $teacher) { ?>
                                                <option value="<?php echo $teacher['id']; ?>" <?php if ($module['teacher_id'] == $teacher['id']) {
                                                       echo 'selected';
                                                   } ?>>
                                                    <?php echo htmlspecialchars($teacher['last_name'] . ' ' . $teacher['first_name']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>

                                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                                        <a href="dashboard.php?tab=modules" class="btn btn-secondary btn-sm">Cancel</a>
                                    </form>
                                </td>
                            </tr>
                        <?php } else { ?>
                            <!-- Display Module Row -->
                            <tr>
                                <td><?php echo htmlspecialchars($module['module_code']); ?></td>
                                <td><?php echo htmlspecialchars($module['module_name']); ?></td>
                                <td><?php echo $module['coefficient']; ?></td>
                                <td><?php echo htmlspecialchars($module['teacher_last_name'] . ' ' . $module['teacher_first_name']); ?>
                                </td>
                                <td>
                                    <a href="dashboard.php?tab=modules&edit_module=<?php echo $module['id']; ?>"
                                        class="btn btn-warning btn-sm">Edit</a>
                                    <a href="dashboard.php?tab=modules&delete_module=<?php echo $module['id']; ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Are you sure you want to delete this module?');">Delete</a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </table>

                <!--  TEACHERS TAB  -->
            <?php } elseif ($active_tab == 'teachers') { ?>
                <div class="section-header">
                    <h3>Teacher List</h3>
                    <a href="create_teacher.php" class="btn">+ Add Teacher</a>
                </div>
                
                <?php
                // Get all teachers
                $all_teachers = $db->query("SELECT * FROM teachers ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
                
                // Check if a teacher is being edited
                $teacher_id_to_edit = 0;
                if (isset($_GET['edit_teacher'])) {
                    $teacher_id_to_edit = $_GET['edit_teacher'];
                }
                ?>
                
                <table>
                    <tr>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>Email</th>
                        <th>Specialty</th>
                        <th>Assigned Modules</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($all_teachers as $teacher) { ?>
                        <?php if ($teacher_id_to_edit == $teacher['id']) { ?>
                            <!-- Edit Teacher Form Row -->
                            <tr>
                                <td colspan="6">
                                    <form method="POST" action="dashboard.php?tab=teachers" class="edit-form-inline">
                                        <input type="hidden" name="action" value="edit_teacher">
                                        <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                        
                                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($teacher['last_name']); ?>" required>
                                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($teacher['first_name']); ?>" required>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                                        <input type="text" name="specialty" value="<?php echo htmlspecialchars($teacher['specialty']); ?>" required>
                                        
                                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                                        <a href="dashboard.php?tab=teachers" class="btn btn-secondary btn-sm">Cancel</a>
                                    </form>
                                </td>
                            </tr>
                        <?php } else { ?>
                            <!-- Display Teacher Row -->
                            <tr>
                                <td><?php echo htmlspecialchars($teacher['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['specialty']); ?></td>
                                <td>
                                    <?php
                                    // Get modules assigned to this teacher
                                    $get_teacher_modules_query = $db->prepare("SELECT module_name FROM modules WHERE teacher_id=?");
                                    $get_teacher_modules_query->execute([$teacher['id']]);
                                    $teacher_modules = $get_teacher_modules_query->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (count($teacher_modules) > 0) {
                                        $module_names = [];
                                        foreach ($teacher_modules as $teacher_module) {
                                            $module_names[] = htmlspecialchars($teacher_module['module_name']);
                                        }
                                        echo implode(", ", $module_names);
                                    } else {
                                        echo "<em>None</em>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="dashboard.php?tab=teachers&edit_teacher=<?php echo $teacher['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <a href="dashboard.php?tab=teachers&delete_teacher=<?php echo $teacher['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this teacher?');">Delete</a>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                </table>

                <!-- ===== GRADES TAB ===== -->
            <?php } elseif ($active_tab == 'grades') { ?>
                <h3 style="margin-bottom:20px;">Manage Grades</h3>

                <?php
                // Get all modules
                $all_modules = $db->query("SELECT * FROM modules ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

                // Check which module is currently selected
                $selected_module_id = 0;
                if (isset($_GET['module'])) {
                    $selected_module_id = $_GET['module'];
                } elseif (count($all_modules) > 0) {
                    $selected_module_id = $all_modules[0]['id'];
                }
                ?>

                <!-- Select Module Buttons -->
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                    <?php foreach ($all_modules as $module) { ?>
                        <?php
                        $button_class = 'btn btn-sm';
                        if ($selected_module_id != $module['id']) {
                            $button_class = $button_class . ' btn-secondary';
                        }
                        ?>
                        <a href="dashboard.php?tab=grades&module=<?php echo $module['id']; ?>" class="<?php echo $button_class; ?>">
                            <?php echo htmlspecialchars($module['module_name']); ?>
                        </a>
                    <?php } ?>
                </div>

                <?php if ($selected_module_id != 0) { ?>
                    <form method="POST" action="dashboard.php?tab=grades&module=<?php echo $selected_module_id; ?>">
                        <input type="hidden" name="action" value="admin_save_grades">
                        <input type="hidden" name="module_id" value="<?php echo $selected_module_id; ?>">

                        <table>
                            <tr>
                                <th>Student Name</th>
                                <th>Study Level</th>
                                <th>Grade (/20)</th>
                            </tr>
                            <?php
                            // Get all students
                            $all_students = $db->query("SELECT id, last_name, first_name, study_level FROM students ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($all_students as $student) {
                                // Get the grade for this student in the selected module
                                $get_grade_query = $db->prepare('SELECT grade_value FROM grades WHERE student_id=? AND module_id=?');
                                $get_grade_query->execute([$student['id'], $selected_module_id]);
                                $student_grade = $get_grade_query->fetchColumn();
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['study_level']); ?></td>
                                    <td>
                                        <input type="number" step="0.25" min="0" max="20" name="grades[<?php echo $student['id']; ?>]"
                                            value="<?php if ($student_grade != false) {
                                                echo htmlspecialchars($student_grade);
                                            } ?>"
                                            style="width:100px;margin-bottom:0;">
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>

                        <button type="submit" class="btn">Save Grades</button>
                    </form>
                <?php } ?>

            <?php } ?>

            <!--  TEACHER DASHBOARD  -->
        <?php } elseif ($user_role == 'Teacher') { ?>
            <h2>Teacher Dashboard</h2>

            <?php
            // Get the teacher's actual ID from the reference_id in the users table
            $get_teacher_id_query = $db->query("SELECT reference_id FROM users WHERE id = $user_id");
            $teacher_actual_id = $get_teacher_id_query->fetchColumn();

            // Get all modules assigned to this teacher
            $get_my_modules_query = $db->prepare('SELECT * FROM modules WHERE teacher_id = ?');
            $get_my_modules_query->execute([$teacher_actual_id]);
            $my_modules = $get_my_modules_query->fetchAll(PDO::FETCH_ASSOC);

            // Check which module is currently selected
            $active_module_id = 0;
            if (isset($_GET['module'])) {
                $active_module_id = $_GET['module'];
            } elseif (count($my_modules) > 0) {
                $active_module_id = $my_modules[0]['id'];
            }
            ?>

            <p>Select a module to grade:</p>

            <!-- Select Module Buttons -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                <?php foreach ($my_modules as $module) { ?>
                    <?php
                    $button_class = 'btn btn-sm';
                    if ($active_module_id != $module['id']) {
                        $button_class = $button_class . ' btn-secondary';
                    }
                    ?>
                    <a href="dashboard.php?module=<?php echo $module['id']; ?>" class="<?php echo $button_class; ?>">
                        <?php echo htmlspecialchars($module['module_name']); ?>
                    </a>
                <?php } ?>
            </div>

            <?php if ($active_module_id != 0) { ?>
                <form method="POST" action="dashboard.php?module=<?php echo $active_module_id; ?>">
                    <input type="hidden" name="action" value="save_grades">
                    <input type="hidden" name="module_id" value="<?php echo $active_module_id; ?>">

                    <table>
                        <tr>
                            <th>Student Name</th>
                            <th>Study Level</th>
                            <th>Grade (/20)</th>
                        </tr>
                        <?php
                        // Get all students
                        $all_students = $db->query("SELECT id, last_name, first_name, study_level FROM students ORDER BY last_name");
                        $student_list = $all_students->fetchAll();

                        foreach ($student_list as $student) {
                            // Get the grade for this student in the selected module
                            $get_grade_query = $db->prepare('SELECT grade_value FROM grades WHERE student_id=? AND module_id=?');
                            $get_grade_query->execute([$student['id'], $active_module_id]);
                            $student_grade = $get_grade_query->fetchColumn();
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['study_level']); ?></td>
                                <td>
                                    <input type="number" step="0.25" min="0" max="20" name="grades[<?php echo $student['id']; ?>]"
                                        value="<?php if ($student_grade != false) {
                                            echo htmlspecialchars($student_grade);
                                        } ?>"
                                        style="width:100px;margin-bottom:0;">
                                </td>
                            </tr>
                        <?php } ?>
                    </table>

                    <button type="submit" class="btn">Save Grades</button>
                </form>
            <?php } else { ?>
                <p>You have no modules assigned.</p>
            <?php } ?>

            <!--  STUDENT DASHBOARD  -->
        <?php } elseif ($user_role == 'Student') { ?>
            <h2>Student Dashboard</h2>

            <?php
            // Get the student's actual ID from the reference_id in the users table
            $get_student_id_query = $db->query("SELECT reference_id FROM users WHERE id = $user_id");
            $student_actual_id = $get_student_id_query->fetchColumn();

            // Get the student's information
            $get_student_info_query = $db->query("SELECT * FROM students WHERE id = $student_actual_id");
            $student_info = $get_student_info_query->fetch(PDO::FETCH_ASSOC);
            ?>

            <p>Welcome,
                <strong><?php echo htmlspecialchars($student_info['last_name'] . ' ' . $student_info['first_name']); ?></strong>
            </p>
            <p>Study Level: <strong><?php echo htmlspecialchars($student_info['study_level']); ?></strong></p>

            <div style="margin-bottom:20px;">
                <a href="transcript.php" class="btn" target="_blank">Print Transcript</a>
            </div>

            <table>
                <tr>
                    <th>Module Name</th>
                    <th>Coefficient</th>
                    <th>Grade (/20)</th>
                </tr>
                <?php
                // Get all modules and grades for this student
                $get_student_grades_query = $db->prepare("
                    SELECT modules.module_name, modules.coefficient, grades.grade_value 
                    FROM modules 
                    LEFT JOIN grades ON modules.id = grades.module_id AND grades.student_id = ?
                ");
                $get_student_grades_query->execute([$student_actual_id]);
                $results = $get_student_grades_query->fetchAll(PDO::FETCH_ASSOC);

                $total_points = 0;
                $total_coefficients = 0;

                foreach ($results as $result) {
                    // Calculate total points for the average
                    if ($result['grade_value'] != null) {
                        $points = $result['grade_value'] * $result['coefficient'];
                        $total_points = $total_points + $points;
                        $total_coefficients = $total_coefficients + $result['coefficient'];
                    }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['module_name']); ?></td>
                        <td><?php echo $result['coefficient']; ?></td>
                        <td>
                            <?php
                            if ($result['grade_value'] != null) {
                                echo $result['grade_value'];
                            } else {
                                echo 'N/A'; // Not available
                            }
                            ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>

            <?php
            // Calculate and show the average if they have grades
            if ($total_coefficients > 0) {
                $average = $total_points / $total_coefficients;
                ?>
                <div class="alert" style="text-align:center;font-size:20px;font-weight:bold;">
                    Overall Average: <?php echo number_format($average, 2); ?> / 20
                </div>
            <?php } ?>

        <?php } ?>

    </div>

    <div class="footer">
        © 2025/2026 University Portal
    </div>

</body>

</html>