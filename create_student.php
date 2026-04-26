<?php
// create_student.php - Add New Student Page (Simplified for beginners)
session_start();

// Check if the user is an Admin. If not, send them back to login.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Admin') {
    header('Location: login.php');
    exit;
}

$user_role = $_SESSION['user_role'];
$username = $_SESSION['username'];

// Connect to the database
try {
    $db = new PDO('mysql:host=localhost;dbname=gestion_scolarite;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

$status_message = '';
$message_type = '';

// Process the form if it was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] == 'add_student') {
        
        // Get data from the form
        $last_name = trim($_POST['last_name']);
        $first_name = trim($_POST['first_name']);
        $email = trim($_POST['email']);
        $date_of_birth = $_POST['date_of_birth'];
        $study_level = $_POST['study_level'];

        // Make sure all fields are filled
        if ($last_name != '' && $first_name != '' && $email != '' && $date_of_birth != '' && $study_level != '') {
            
            // Check if email is already used by another student
            $check_email_query = $db->prepare('SELECT COUNT(*) FROM students WHERE email = ?');
            $check_email_query->execute([$email]);
            $email_count = $check_email_query->fetchColumn();
            
            if ($email_count > 0) {
                $status_message = "A student with this email already exists.";
                $message_type = 'error';
            } else {
                // Generate a new student number (matricule)
                $current_year = date('Y');
                
                // Count how many students exist to generate the next number
                $count_students_query = $db->query("SELECT COUNT(*) FROM students");
                $number_of_students = $count_students_query->fetchColumn();
                $next_number = $number_of_students + 1;
                
                // Make it look like ETU2025001
                // str_pad adds zeroes to the left if the number is less than 3 digits
                $padded_number = str_pad($next_number, 3, '0', STR_PAD_LEFT);
                $new_student_number = "ETU" . $current_year . $padded_number;

                // Insert the new student into the students table
                $insert_student_query = $db->prepare('INSERT INTO students (student_number, last_name, first_name, email, date_of_birth, study_level) VALUES (?, ?, ?, ?, ?, ?)');
                $insert_student_query->execute([$new_student_number, $last_name, $first_name, $email, $date_of_birth, $study_level]);
                
                // Get the ID of the student we just created
                $new_student_id = $db->lastInsertId();

                // Create a login account for this new student
                // The email will be the username, and the student number will be the password
                $hashed_password = password_hash($new_student_number, PASSWORD_DEFAULT);
                
                $create_user_query = $db->prepare('INSERT INTO users (username, password, role, reference_id) VALUES (?, ?, ?, ?)');
                $create_user_query->execute([$email, $hashed_password, 'Student', $new_student_id]);

                $status_message = "Student added! Number: " . $new_student_number . " — Login: " . $email . " / Password: " . $new_student_number;
                $message_type = 'success';
            }
        } else {
            $status_message = "Please fill in all fields.";
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

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
            <a href="index.php?logout=1">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="user-badge">
            Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong>
            (<?php echo htmlspecialchars($user_role); ?>)
        </div>

        <a href="dashboard.php?tab=students" class="back-link" style="margin-bottom:20px;display:inline-flex;">← Back to
            Student List</a>

        <?php if ($status_message != '') { ?>
            <?php 
            $alert_class = "alert";
            if ($message_type == 'error') {
                $alert_class = "alert error";
            }
            ?>
            <div class="<?php echo $alert_class; ?>">
                <?php echo htmlspecialchars($status_message); ?>
            </div>
        <?php } ?>

        <h2>Add New Student</h2>

        <form method="POST" action="create_student.php">
            <input type="hidden" name="action" value="add_student">

            <label>First Name:</label>
            <input type="text" name="first_name" required placeholder="Enter first name">

            <label>Last Name:</label>
            <input type="text" name="last_name" required placeholder="Enter last name">

            <label>Email (used for login):</label>
            <input type="email" name="email" required placeholder="student@university.dz">

            <label>Date of Birth:</label>
            <input type="date" name="date_of_birth" required>

            <label>Level:</label>
            <select name="study_level" required>
                <option value="L1 Computer Science">L1 Computer Science</option>
                <option value="L2 Computer Science">L2 Computer Science</option>
                <option value="L3 Computer Science" selected>L3 Computer Science</option>
            </select>

            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="submit" class="btn">Add Student</button>
                <a href="dashboard.php?tab=students" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

    </div>

    <div class="footer">
        © 2025/2026 University Portal
    </div>

</body>

</html>