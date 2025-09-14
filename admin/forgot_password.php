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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    
    if (empty($username) || empty($phone)) {
        $error = 'Please enter both username and phone number';
    } else {
        // Check if username and phone number match in the admins table
        $stmt = $pdo->prepare("SELECT id, username, full_name FROM admins WHERE username = ? AND phone = ?");
        $stmt->execute([$username, $phone]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete existing tokens for this admin
            $stmt = $pdo->prepare("DELETE FROM admin_password_reset_tokens WHERE admin_id = ?");
            $stmt->execute([$admin['id']]);
            
            // Insert new token
            $stmt = $pdo->prepare("INSERT INTO admin_password_reset_tokens (admin_id, token, phone_number, expires_at) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$admin['id'], $token, $phone, $expires])) {
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/blood/admin/password_reset_by_link.php?token=" . $token;
                $success = "Password reset link has been generated. <a href='$resetLink'>Click here to reset your password</a>";
            } else {
                $error = 'Error generating reset token. Please try again.';
            }
        } else {
            $error = 'Username and phone number do not match our records.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Forgot Password - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="../assets/css/auth.css" />
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <div class="auth-header">
                <h2>Forgot Password</h2>
                <p>Enter your username and registered phone number to reset your password</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Enter your username" />
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required placeholder="Enter your registered phone number" />
                </div>

                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </form>

            <div class="auth-footer">
                Remember your password? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>
