<?php
// index.php - Modern Homepage (Simplified for beginners)
session_start();

// Database connection
try {
    $db = new PDO('mysql:host=localhost;dbname=gestion_scolarite;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

// Security: Prevent session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Check if user is logged in
$is_logged_in = false;
if (isset($_SESSION['user_id'])) {
    $is_logged_in = true;
}

// Get statistics for the home page
$total_students = 0;
$total_teachers = 0;
$total_modules = 0;

try {
    // Count students
    $student_query = $db->query("SELECT COUNT(*) FROM students");
    $total_students = $student_query->fetchColumn();
    if ($total_students == false) {
        $total_students = 0;
    }

    // Count teachers
    $teacher_query = $db->query("SELECT COUNT(*) FROM teachers");
    $total_teachers = $teacher_query->fetchColumn();
    if ($total_teachers == false) {
        $total_teachers = 0;
    }

    // Count modules
    $module_query = $db->query("SELECT COUNT(*) FROM modules");
    $total_modules = $module_query->fetchColumn();
    if ($total_modules == false) {
        $total_modules = 0;
    }
} catch (Exception $e) {
    // Keep them at 0 if there is an error
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="University Portal — Modern student and faculty management system.">
    <title>University Portal — Home</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>

<body>

    <div class="header">
        <div style="display: flex; align-items: center;">
            <img src="logo.png?v=2" alt="University Portal Logo">
            <h1>USTHB</h1>
        </div>
        <div class="header-links">
            <?php if ($is_logged_in == true) { ?>
                <a href="index.php?logout=1">Logout</a>
            <?php } else { ?>
                <a href="login.php">Login</a>
            <?php } ?>
        </div>
    </div>

    <div class="container">

        <!-- Hero Section -->
        <div class="hero">
            <img src="logo.png?v=2" alt="Logo" style="width: 80px; border-radius: 16px; margin-bottom: 18px;">
            <h2>Welcome to University Portal for USTHB</h2>
            <p>A modern, streamlined platform for managing students, teachers, modules, and grades — all in one place.
            </p>
            <div>
                <?php if ($is_logged_in == true) { ?>
                    <a href="dashboard.php" class="btn btn-large">Go to Dashboard</a>
                <?php } else { ?>
                    <a href="login.php" class="btn btn-large">Sign In</a>
                <?php } ?>
            </div>
        </div>

        <!-- Stats Cards -->
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

        <!-- Feature Cards -->
        <div class="features-grid">
            <div class="feature-card">
                <h3>Admin Panel</h3>
                <p>Manage students, teachers, modules, and grades from a central dashboard.</p>
            </div>
            <div class="feature-card">
                <h3>Grade Management</h3>
                <p>Teachers can input and update grades quickly and securely.</p>
            </div>
            <div class="feature-card">
                <h3>Transcripts</h3>
                <p>Students can view their grades and print official transcripts online.</p>
            </div>
        </div>

    </div>

    <div class="footer">
        © 2025/2026 USTHB
    </div>

</body>

</html>