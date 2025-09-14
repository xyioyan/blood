<?php
session_start();
require_once '../includes/config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $student_id = trim($_POST['student_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $blood_group = trim($_POST['blood_group']);
    $department = trim($_POST['department']);
    $year_of_study = trim($_POST['year_of_study']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate inputs
    if(empty($student_id) || empty($full_name) || empty($email) || empty($phone) || 
       empty($blood_group) || empty($department) || empty($year_of_study) || 
       empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: register.php");
        exit();
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: register.php");
        exit();
    }

    if($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: register.php");
        exit();
    }

    if(strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters";
        header("Location: register.php");
        exit();
    }

    // Check if student ID or email already exists
    try {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
        $stmt->execute([$student_id, $email]);
        
        if($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Student ID or Email already exists";
            header("Location: register.php");
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: register.php");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new student
    try {
        $stmt = $pdo->prepare("INSERT INTO students (student_id, full_name, email, phone, blood_group, department, year_of_study, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $full_name, $email, $phone, $blood_group, $department, $year_of_study, $hashed_password]);
        
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: login.php");
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        header("Location: register.php");
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}
?>