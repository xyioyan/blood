<?php
session_start();
require_once '../includes/config.php';

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: login.php");
    exit();
}

$token = trim($_POST['token']);
$email = trim($_POST['email']);
$new_password = trim($_POST['new_password']);
$confirm_password = trim($_POST['confirm_password']);

// Validate inputs
if(empty($token) || empty($email) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['error'] = "All fields are required";
    header("Location: reset_password.php?token=$token");
    exit();
}

if($new_password !== $confirm_password) {
    $_SESSION['error'] = "Passwords do not match";
    header("Location: reset_password.php?token=$token");
    exit();
}

if(strlen($new_password) < 8) {
    $_SESSION['error'] = "Password must be at least 8 characters";
    header("Location: reset_password.php?token=$token");
    exit();
}

try {
    // Verify token is valid
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND email = ? AND expires_at > NOW()");
    $stmt->execute([$token, $email]);
    $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$reset_request) {
        $_SESSION['error'] = "Invalid or expired password reset link";
        header("Location: login.php");
        exit();
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE email = ?");
    $stmt->execute([$hashed_password, $email]);
    
    // Delete the used token
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    
    $_SESSION['success'] = "Password updated successfully. You can now login with your new password.";
    header("Location: login.php");
    exit();
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Error resetting password: " . $e->getMessage();
    header("Location: reset_password.php?token=$token");
    exit();
}
?>