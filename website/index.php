<?php
// index.php - Authentication and Homepage
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

// Handle Login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare('SELECT * FROM utilisateurs WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Identifiant ou mot de passe incorrect.';
    }
}

$isLogged = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>University Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header clearfix">
    <img src="logo.png" alt="University Portal Logo">
    <h1>University Portal</h1>
    <div class="header-links">
        <?php if ($isLogged): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="index.php?logout=1">Logout</a>
        <?php else: ?>
            <a href="index.php">Home</a>
            <a href="index.php?login=1">Login</a>
        <?php endif; ?>
    </div>
</div>

<div class="container clearfix">
    <img src="logo.png" alt="Logo" class="logo-large">
    
    <?php if (isset($_GET['login'])): ?>
        <!-- Login Form -->
        <h2 style="text-align: center;">Connexion</h2>
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="index.php?login=1">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
            
            <div style="text-align: center;">
                <button type="submit" class="btn">Se connecter</button>
            </div>
        </form>
    <?php else: ?>
        <!-- Homepage -->
        <h2 style="text-align: center;">Welcome to University Portal</h2>
        <p style="text-align: center;">A completely simple and flat management system.</p>
        <div style="text-align: center; margin-bottom: 40px;">
            <?php if ($isLogged): ?>
                <a href="dashboard.php" class="btn">Accéder au Dashboard</a>
            <?php else: ?>
                <a href="index.php?login=1" class="btn">Se connecter</a>
            <?php endif; ?>
        </div>

        <div class="clearfix">
            <div class="feature">
                <h3>Admin</h3>
                <p>Manage users easily.</p>
            </div>
            <div class="feature">
                <h3>Teachers</h3>
                <p>Input grades quickly.</p>
            </div>
            <div class="feature">
                <h3>Students</h3>
                <p>View transcripts online.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="footer">
    © 2025/2026 University Portal - Simple Version
</div>

</body>
</html>
