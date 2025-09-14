<?php
session_start();
require_once '../includes/config.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$username = trim($_POST['username']);
$password = trim($_POST['password']);
$remember = isset($_POST['remember']);

// Basic validation
if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = "Username and password are required";
    header("Location: login.php");
    exit();
}

try {
    // Fetch admin by username
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Validate admin + password
    if (!$admin || !password_verify($password, $admin['password'])) {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip_address, successful) VALUES (?, ?, 0)");
        $stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);

        $_SESSION['login_error'] = "Invalid username or password";
        header("Location: login.php");
        exit();
    }

    // Check active status
    if (!$admin['is_active']) {
        $_SESSION['login_error'] = "Your account has been deactivated";
        header("Location: login.php");
        exit();
    }

    // Successful login attempt
    $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip_address, successful) VALUES (?, ?, 1)");
    $stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);

    // Set session variables
    $_SESSION['admin_id']   = $admin['id'];
    $_SESSION['admin_name'] = $admin['full_name'];
    $_SESSION['admin_role'] = $admin['role'];

    // Remember Me
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + 60 * 60 * 24 * 30; // 30 days

        // Store hashed token in DB for safety
        $hashedToken = hash('sha256', $token);
        $stmt = $pdo->prepare("UPDATE admins SET remember_token = ?, token_expiry = ? WHERE id = ?");
        $stmt->execute([$hashedToken, date('Y-m-d H:i:s', $expiry), $admin['id']]);

        // Store raw token in cookie
        setcookie('admin_remember_token', $token, [
            'expires' => $expiry,
            'path' => '/', // available across whole site
            'secure' => true,  // only HTTPS
            'httponly' => true, // not accessible in JS
            'samesite' => 'Strict'
        ]);
    }

    // Update last login time
    $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$admin['id']]);

    // Redirect to dashboard
    header("Location: dashboard.php");
    exit();

} catch (PDOException $e) {
    error_log("Admin login error: " . $e->getMessage());
    $_SESSION['login_error'] = "An error occurred. Please try again later.";
    header("Location: login.php");
    exit();
}
