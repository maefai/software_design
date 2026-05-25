<?php
// company/simple_clock_test.php - ULTRA SIMPLE TEST
require_once '../includes/config.php';
require_once '../includes/auth_functions.php';

requireCompany();

$user_id = $_SESSION['user_id'];
$company = getCompanyData($conn, $user_id);

$message = '';
$error = '';

echo "\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2 style='color:green'>FORM WAS SUBMITTED!</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
}

$intern = null;
try {
    // Translated TOP 1 to LIMIT 1 for MySQL
    $sql = "SELECT s.id as student_id, s.fullname, i.id as internship_id, i.title as internship_title
            FROM applications a
            JOIN students s ON a.student_id = s.id
            JOIN internships i ON a.internship_id = i.id
            WHERE i.company_id = ? AND a.status = 'accepted'
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$company['id']]);
    $intern = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Simple Clock Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f0f0; }
        .box { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        button { padding: 10px 20px; font-size: 16px; background: green; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: darkgreen; }
        .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Simple Clock Test</h1>
        <p>Company: <strong><?php echo htmlspecialchars($company['company_name']); ?></strong></p>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!$intern): ?>
            <div class="error">
                <strong>No interns found!</strong><br>
                Please make sure:
                <ul>
                    <li>You have accepted applications</li>
                    <li>The applications table has status = 'accepted'</li>
                </ul>
            </div>
        <?php else: ?>
            <h2>Test Intern: <?php echo htmlspecialchars($intern['fullname']); ?></h2>
            <p>Internship: <?php echo htmlspecialchars($intern['internship_title']); ?></p>
            
            <!-- SIMPLE FORM - NO JAVASCRIPT -->
            <form method="POST" action="">
                <input type="hidden" name="student_id" value="<?php echo $intern['student_id']; ?>">
                <input type="hidden" name="internship_id" value="<?php echo $intern['internship_id']; ?>">
                <button type="submit" name="test_clock">TEST CLOCK IN</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>