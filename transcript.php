<?php
// transcript.php - Simple printable transcript
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Etudiant') {
    die("Access denied.");
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// DB connection
try {
    $db = new PDO('mysql:host=localhost;dbname=gestion_scolarite;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed.");
}

$etu_id = $db->query("SELECT id_ref FROM utilisateurs WHERE id = $user_id")->fetchColumn();
$student_info = $db->query("SELECT * FROM etudiants WHERE id = $etu_id")->fetch(PDO::FETCH_ASSOC);

$niveau = $student_info['niveau'];
$student_name = $student_info['nom'] . ' ' . $student_info['prenom'];
?>
<!DOCTYPE html>
<html lang="fr">
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
    <h2>Relevé de Notes</h2>
</div>

<p><strong>Étudiant:</strong> <?php echo htmlspecialchars($student_name); ?></p>
<p><strong>Niveau:</strong> <?php echo htmlspecialchars($niveau); ?></p>
<p><strong>Année:</strong> 2025/2026</p>

<table>
    <tr>
        <th>Module</th>
        <th>Coefficient</th>
        <th>Note (/20)</th>
    </tr>
    <?php
    $stmt = $db->prepare("
        SELECT m.intitule as nom_module, m.coefficient, n.note 
        FROM modules m
        LEFT JOIN notes n ON m.id = n.id_module AND n.id_etudiant = ?
    ");
    $stmt->execute([$etu_id]);
    
    $total_points = 0;
    $total_coeffs = 0;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r): 
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
    <?php $avg = $total_points / $total_coeffs; ?>
    <h3 style="text-align: right; margin-top: 20px;">Moyenne Générale: <?php echo number_format($avg, 2); ?> / 20</h3>
<?php endif; ?>

<div class="footer">
    Document officiel - USTHB
</div>

</body>
</html>
