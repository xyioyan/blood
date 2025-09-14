<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

if(isset($_SESSION['student_id'])) {
    header("Location: profile.php");
    exit();
}

$error = '';
$success = '';
$validToken = false;
$token = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token is valid
    $stmt = $pdo->prepare("SELECT prt.*, s.student_id, s.full_name 
                         FROM password_reset_tokens prt 
                         JOIN students s ON prt.student_id = s.id 
                         WHERE prt.token = ? AND prt.used = 0 AND prt.expires_at > NOW()");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tokenData) {
        $validToken = true;
        $studentName = $tokenData['full_name'];
        $studentId = $tokenData['student_id'];
    } else {
        $error = 'Invalid or expired reset token.';
    }
} else {
    $error = 'No reset token provided.';
}

if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update student password
        $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashedPassword, $tokenData['student_id']])) {
            // Mark token as used
            $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            $success = 'Password has been reset successfully. You can now <a href="login.php">login</a> with your new password.';
            $validToken = false; // Prevent form from showing again
        } else {
            $error = 'Error resetting password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reset Password - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/auth.css" />
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <div class="auth-header">
                <h2>Reset Password</h2>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <p class="text-muted">Hello <strong><?php echo htmlspecialchars($studentName); ?></strong>, please enter your new password below.</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" required placeholder="Enter new password (min. 6 characters)" />
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm new password" />
                    </div>

                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            <?php elseif (empty($success)): ?>
                <div class="text-center">
                    <p>Please request a new password reset link.</p>
                    <a href="forgot-password.php" class="btn btn-primary">Request Reset Link</a>
                </div>
            <?php endif; ?>

            <div class="auth-footer">
                Remember your password? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>
