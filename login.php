<?php
// login.php - Login page with email support (Simplified for beginners)
session_start();

// If the user is already logged in, send them to the dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$login_error_message = '';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Connect to the database
        $db = new PDO('mysql:host=localhost;dbname=gestion_scolarite;charset=utf8mb4', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get the inputted username and password
        $login_username = "";
        if (isset($_POST['username'])) {
            $login_username = trim($_POST['username']);
        }

        $login_password = "";
        if (isset($_POST['password'])) {
            $login_password = $_POST['password'];
        }

        $found_user = false;

        // 1. Try to find the user by their username
        $find_by_username_query = $db->prepare('SELECT * FROM users WHERE username = ?');
        $find_by_username_query->execute([$login_username]);
        $found_user = $find_by_username_query->fetch(PDO::FETCH_ASSOC);

        // 2. If not found by username, try to find a student by their email
        if ($found_user == false) {
            $find_student_query = $db->prepare('
                SELECT users.* FROM users 
                JOIN students ON users.reference_id = students.id AND users.role = "Student"
                WHERE students.email = ?
            ');
            $find_student_query->execute([$login_username]);
            $found_user = $find_student_query->fetch(PDO::FETCH_ASSOC);
        }

        // 3. If still not found, try to find a teacher by their email
        if ($found_user == false) {
            $find_teacher_query = $db->prepare('
                SELECT users.* FROM users 
                JOIN teachers ON users.reference_id = teachers.id AND users.role = "Teacher"
                WHERE teachers.email = ?
            ');
            $find_teacher_query->execute([$login_username]);
            $found_user = $find_teacher_query->fetch(PDO::FETCH_ASSOC);
        }

        // 4. Verify the password if a user was found
        if ($found_user != false) {
            
            $is_password_correct = password_verify($login_password, $found_user['password']);
            
            if ($is_password_correct == true) {
                // Password is correct, log them in
                $_SESSION['user_id'] = $found_user['id'];
                $_SESSION['user_role'] = $found_user['role'];
                $_SESSION['username'] = $found_user['username'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $login_error_message = 'Incorrect password.';
            }

        } else {
            $login_error_message = 'Username or email not found.';
        }

    } catch (PDOException $e) {
        $login_error_message = "Database error. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to University Portal for USTHB">
    <title>Login — University Portal</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>

<body style="background: #e0e7ff; min-height: 100vh;">
    <div class="login-box">
        <img src="logo.png?v=2" alt="UP" style="display: block; margin: 0 auto 16px auto; height: 56px; border-radius: 12px;">
        <h2>Welcome Back</h2>
        <p class="login-subtitle">Sign in with your username or email</p>
        
        <?php if ($login_error_message != '') { ?>
            <div class="alert error"><?php echo htmlspecialchars($login_error_message); ?></div>
        <?php } ?>

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
            <a href="index.php" class="back-link">Back to Home</a>
        </div>
    </div>
</body>

</html>