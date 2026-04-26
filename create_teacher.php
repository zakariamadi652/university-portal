<?php
// create_teacher.php - Add New Teacher Page (Simplified for beginners)
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
    
    if (isset($_POST['action']) && $_POST['action'] == 'add_teacher') {
        
        // Get data from the form
        $last_name = trim($_POST['last_name']);
        $first_name = trim($_POST['first_name']);
        $email = trim($_POST['email']);
        $specialty = trim($_POST['specialty']);

        // Make sure all fields are filled
        if ($last_name != '' && $first_name != '' && $email != '' && $specialty != '') {
            
            // Check if email is already used by another teacher
            $check_email_query = $db->prepare('SELECT COUNT(*) FROM teachers WHERE email = ?');
            $check_email_query->execute([$email]);
            $email_count = $check_email_query->fetchColumn();
            
            if ($email_count > 0) {
                $status_message = "A teacher with this email already exists.";
                $message_type = 'error';
            } else {
                // Insert the new teacher into the teachers table
                $insert_teacher_query = $db->prepare('INSERT INTO teachers (last_name, first_name, email, specialty) VALUES (?, ?, ?, ?)');
                $insert_teacher_query->execute([$last_name, $first_name, $email, $specialty]);
                
                // Get the ID of the teacher we just created
                $new_teacher_id = $db->lastInsertId();

                // Create a login account for this new teacher
                // The email will be the username, and the default password will be 'teacher123'
                $default_password = 'teacher123';
                $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
                
                $create_user_query = $db->prepare('INSERT INTO users (username, password, role, reference_id) VALUES (?, ?, ?, ?)');
                $create_user_query->execute([$email, $hashed_password, 'Teacher', $new_teacher_id]);

                $status_message = "Teacher added! Login: " . $email . " / Password: " . $default_password;
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
    <title>Add Teacher — University Portal</title>
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

        <a href="dashboard.php?tab=teachers" class="back-link" style="margin-bottom:20px;display:inline-flex;">← Back to
            Teacher List</a>

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

        <h2>Add New Teacher</h2>

        <form method="POST" action="create_teacher.php">
            <input type="hidden" name="action" value="add_teacher">

            <label>First Name:</label>
            <input type="text" name="first_name" required placeholder="Enter first name">

            <label>Last Name:</label>
            <input type="text" name="last_name" required placeholder="Enter last name">

            <label>Email (used for login):</label>
            <input type="email" name="email" required placeholder="teacher@university.dz">

            <label>Specialty:</label>
            <input type="text" name="specialty" required placeholder="e.g. Web Programming">

            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="submit" class="btn">Add Teacher</button>
                <a href="dashboard.php?tab=teachers" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

    </div>

    <div class="footer">
        © 2025/2026 University Portal
    </div>

</body>

</html>
