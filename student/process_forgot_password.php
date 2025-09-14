<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/mailer.php';

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: forgot_password.php");
    exit();
}

$email = trim($_POST['email']);

// Validate email
if(empty($email)) {
    $_SESSION['error'] = "Email is required";
    header("Location: forgot_password.php");
    exit();
}

try {
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id, student_id, full_name FROM students WHERE email = ?");
    $stmt->execute([$email]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$student) {
        $_SESSION['error'] = "No account found with that email";
        header("Location: forgot_password.php");
        exit();
    }
    
    // Generate unique token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
    
    // Delete any existing tokens for this user
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->execute([$email]);
    
    // Store token in database
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token, $expires]);
    
    // Send email with reset link
    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";
    
    $subject = "Password Reset Request";
    $message = "Hello {$student['full_name']},<br><br>"
             . "You requested a password reset for your Blood Group Management account.<br><br>"
             . "Please click the following link to reset your password:<br>"
             . "<a href='$reset_link'>$reset_link</a><br><br>"
             . "This link will expire in 1 hour.<br><br>"
             . "If you didn't request this, please ignore this email.<br><br>"
             . "Thanks,<br>"
             . "Blood Group Management Team";
    
    if(sendEmail($email, $subject, $message)) {
        $_SESSION['success'] = "Password reset link has been sent to your email";
        header("Location: forgot_password.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to send reset email. Please try again.";
        header("Location: forgot_password.php");
        exit();
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Error processing your request: " . $e->getMessage();
    header("Location: forgot_password.php");
    exit();
}
?>