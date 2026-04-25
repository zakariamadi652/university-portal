<?php
// home.php - Modern Homepage
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
    header('Location: home.php');
    exit;
}

$isLogged = isset($_SESSION['user_id']);

// Stats
$stats = ['etudiants' => 0, 'enseignants' => 0, 'modules' => 0];
try {
    $stats['etudiants'] = $db->query("SELECT COUNT(*) FROM etudiants")->fetchColumn() ?: 0;
    $stats['enseignants'] = $db->query("SELECT COUNT(*) FROM enseignants")->fetchColumn() ?: 0;
    $stats['modules'] = $db->query("SELECT COUNT(*) FROM modules")->fetchColumn() ?: 0;
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="fr">

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
            <?php if ($isLogged): ?>

                <a href="home.php?logout=1">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
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
                <?php if ($isLogged): ?>
                    <a href="dashboard.php" class="btn btn-large">Go to Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-large">Sign In</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Cards -->
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