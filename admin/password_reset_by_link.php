<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

if(isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';
$validToken = false;
$token = '';
$adminName = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Validate token: exists, not expired, not used
    $stmt = $pdo->prepare("SELECT aprt.*, a.username, a.full_name 
                           FROM admin_password_reset_tokens aprt 
                           JOIN admins a ON aprt.admin_id = a.id 
                           WHERE aprt.token = ? AND aprt.used = 0 AND aprt.expires_at > NOW()");
    $stmt->execute([$token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tokenData) {
        $validToken = true;
        $adminName = $tokenData['full_name'];
        $adminId = $tokenData['admin_id'];
    } else {
        $error = 'Invalid or expired reset token.';
    }
} else {
    $error = 'No reset token provided.';
}

// Handle form submission
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
        
        // Update the admin password
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashedPassword, $adminId])) {
            // Mark token as used
            $stmt = $pdo->prepare("UPDATE admin_password_reset_tokens SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            $success = 'Password has been reset successfully. You can now <a href="login.php">login</a> with your new password.';
            $validToken = false; // prevent form from displaying again
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
    <title>Reset Password - Admin Panel</title>
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
                <p class="text-muted">Hello <strong><?php echo htmlspecialchars($adminName); ?></strong>, please enter your new password below.</p>
                
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
