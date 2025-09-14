<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = trim($_POST['student_id']); // can be student_id, phone, or email
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']) ? true : false;

    if (empty($login_id) || empty($password)) {
        $_SESSION['error'] = "Login ID and password are required";
        header("Location: login.php");
        exit();
    }

    try {
        // Find student by ID, phone, or email
        $stmt = $pdo->prepare("
            SELECT * FROM students 
            WHERE student_id = ? OR phone = ? OR email = ?
            LIMIT 1
        ");
        $stmt->execute([$login_id, $login_id, $login_id]);

        if ($stmt->rowCount() === 1) {
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            $passwordOk = false;

            // If password exists in DB, verify normally
            if (!empty($student['password'])) {
                if (password_verify($password, $student['password'])) {
                    $passwordOk = true;
                }
            } else {
                // No password in DB: allow login if entered password matches email or phone
                if ($password === $student['phone'] || $password === $student['email']) {
                    $passwordOk = true;
                }
            }

            if ($passwordOk) {
                // Set session
                $_SESSION['student_id'] = $student['student_id'];
                $_SESSION['student_name'] = $student['full_name'];
                $_SESSION['blood_group'] = $student['blood_group'];

                // Remember me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);

                    $stmt = $pdo->prepare("UPDATE students SET remember_token = ?, token_expiry = ? WHERE id = ?");
                    $stmt->execute([$token, $expiry, $student['id']]);

                    setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/');
                }

                header("Location: profile.php");
                exit();
            } else {
                $_SESSION['error'] = "Invalid password";
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Student not found";
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Login failed: " . $e->getMessage();
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
