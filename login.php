<?php
// login.php - Login page with email support
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new PDO('mysql:host=localhost;dbname=gestion_scolarite;charset=utf8mb4', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $login_input = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Try username first
        $stmt = $db->prepare('SELECT * FROM utilisateurs WHERE username = ?');
        $stmt->execute([$login_input]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // If not found by username, try to find by email (for students)
        if (!$user) {
            $stmt = $db->prepare('
                SELECT u.* FROM utilisateurs u
                JOIN etudiants e ON u.id_ref = e.id AND u.role = "Etudiant"
                WHERE e.email = ?
            ');
            $stmt->execute([$login_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // If still not found, try teacher email
        if (!$user) {
            $stmt = $db->prepare('
                SELECT u.* FROM utilisateurs u
                JOIN enseignants e ON u.id_ref = e.id AND u.role = "Enseignant"
                WHERE e.email = ?
            ');
            $stmt->execute([$login_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Incorrect username/email or password.';
        }
    } catch (PDOException $e) {
        $error = "Database error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to University Portal">
    <title>Login — University Portal</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>

<body style="background: #e0e7ff; min-height: 100vh;">
    <div class="login-box">
        <img src="logo.png" alt="UP" style="display: block; margin: 0 auto 16px auto; height: 56px; border-radius: 12px;">
        <h2>Welcome Back</h2>
        <p class="login-subtitle">Sign in with your username or email</p>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div>
                <input name="username" type="text" placeholder="Username or Email" required>
            </div>
            <div>
                <input name="password" type="password" placeholder="Password" required>
            </div>
            <div>
                <button type="submit" class="btn">Sign In</button>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="home.php" class="back-link">← Back to Home</a>
        </div>
    </div>
</body>

</html>