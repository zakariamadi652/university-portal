<?php
// transcript.php - Simple printable transcript (Simplified for beginners)
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'Student') {
    die("Access denied.");
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Connect to database
try {
    $db = new PDO('mysql:host=localhost;dbname=gestion_scolarite;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

// Get the actual student ID from the users table
$get_student_id_query = $db->query("SELECT reference_id FROM users WHERE id = $user_id");
$student_actual_id = $get_student_id_query->fetchColumn();

// Get the student's personal information
$get_student_info_query = $db->query("SELECT * FROM students WHERE id = $student_actual_id");
$student_info = $get_student_info_query->fetch(PDO::FETCH_ASSOC);

$study_level = $student_info['study_level'];
$student_full_name = $student_info['last_name'] . ' ' . $student_info['first_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transcript - <?php echo htmlspecialchars($username); ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: rgb(0,0,0); margin: 40px; }
        .header { text-align: center; margin-bottom: 40px; }
        .header img { height: 60px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid rgb(0,0,0); text-align: left; }
        th { background-color: rgb(230,230,230); }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; }
    </style>
</head>
<body onload="window.print()">

<div class="header">
    <img src="logo.png?v=2" alt="University Portal">
    <h1>USTHB</h1>
    <h2>Academic Transcript</h2>
</div>

<p><strong>Student:</strong> <?php echo htmlspecialchars($student_full_name); ?></p>
<p><strong>Study Level:</strong> <?php echo htmlspecialchars($study_level); ?></p>
<p><strong>Academic Year:</strong> 2025/2026</p>

<table>
    <tr>
        <th>Module Name</th>
        <th>Coefficient</th>
        <th>Grade (/20)</th>
    </tr>
    <?php
    // Get all modules and the student's grades
    $get_grades_query = $db->prepare("
        SELECT modules.module_name, modules.coefficient, grades.grade_value 
        FROM modules 
        LEFT JOIN grades ON modules.id = grades.module_id AND grades.student_id = ?
    ");
    $get_grades_query->execute([$student_actual_id]);
    $grades_list = $get_grades_query->fetchAll(PDO::FETCH_ASSOC);
    
    $total_points = 0;
    $total_coefficients = 0;

    foreach ($grades_list as $grade_info) { 
        if ($grade_info['grade_value'] != null) {
            $points = $grade_info['grade_value'] * $grade_info['coefficient'];
            $total_points = $total_points + $points;
            $total_coefficients = $total_coefficients + $grade_info['coefficient'];
        }
    ?>
    <tr>
        <td><?php echo htmlspecialchars($grade_info['module_name']); ?></td>
        <td><?php echo $grade_info['coefficient']; ?></td>
        <td>
            <?php 
            if ($grade_info['grade_value'] != null) {
                echo $grade_info['grade_value'];
            } else {
                echo 'N/A';
            }
            ?>
        </td>
    </tr>
    <?php } ?>
</table>

<?php if ($total_coefficients > 0) { ?>
    <?php $average = $total_points / $total_coefficients; ?>
    <h3 style="text-align: right; margin-top: 20px;">Overall Average: <?php echo number_format($average, 2); ?> / 20</h3>
<?php } ?>

<div class="footer">
    Official Document - USTHB
</div>

</body>
</html>
