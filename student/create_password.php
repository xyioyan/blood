<?php
session_start();
require_once '../includes/config.php';

// If no temporary student ID in session, redirect to login
if (!isset($_SESSION['student_id_temp'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id_temp'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill out both password fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Update in database
            $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE student_id = ?");
            $stmt->execute([$hashed_password, $student_id]);

            unset($_SESSION['student_id_temp']);

            $_SESSION['success'] = "Password created successfully. You can now log in.";
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error updating password: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Password - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <div class="auth-header">
                <h2>Create Your Password</h2>
                <p>Set a password to secure your account</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-primary">Set Password</button>
            </form>

            <div class="auth-footer">
                Already have a password? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>
