<?php
session_start();
require_once '../includes/config.php';

if(isset($_SESSION['student_id'])) {
    header("Location: profile.php");
    exit();
}

$token = $_GET['token'] ?? '';

if(empty($token)) {
    $_SESSION['error'] = "Invalid password reset link";
    header("Location: login.php");
    exit();
}

try {
    // Check if token exists and is valid
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$reset_request) {
        $_SESSION['error'] = "Invalid or expired password reset link";
        header("Location: login.php");
        exit();
    }
    
    // Get student email for the form
    $email = $reset_request['email'];
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Error processing your request: " . $e->getMessage();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <div class="auth-header">
                <h2>Reset Password</h2>
                <p>Enter a new password for your account</p>
            </div>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="process_reset_password.php" method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <small>Must be at least 8 characters long</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
            
            <div class="auth-footer">
                Remember your password? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
    
    <script>
    // Client-side password validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if(newPassword !== confirmPassword) {
            alert('Passwords do not match');
            e.preventDefault();
            return;
        }
        
        if(newPassword.length < 8) {
            alert('Password must be at least 8 characters long');
            e.preventDefault();
        }
    });
    </script>
</body>
</html>