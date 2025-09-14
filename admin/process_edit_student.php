<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_students.php");
    exit();
}

// Validate and sanitize input
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$student_id = trim($_POST['student_id']);
$full_name = trim($_POST['full_name']);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$phone = trim($_POST['phone']);
$blood_group = trim($_POST['blood_group']);
$department = trim($_POST['department']);
$year_of_study = trim($_POST['year_of_study']);
$is_available = isset($_POST['is_available']) ? 1 : 0;
$password = $_POST['password'] ?? '';

// Basic validation
if(!$id || !$student_id || !$full_name || !$email || !$phone || !$blood_group || !$department || !$year_of_study) {
    $_SESSION['error'] = "All required fields must be filled properly";
    header("Location: edit_student.php?id=$id");
    exit();
}

// Check if password is being changed
if(!empty($password)) {
    if(strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long";
        header("Location: edit_student.php?id=$id");
        exit();
    }
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $update_password = ", password = :password";
} else {
    $update_password = "";
}

try {
    // Check if student ID is already taken by another student
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = :student_id AND id != :id");
    $stmt->execute([':student_id' => $student_id, ':id' => $id]);
    
    if($stmt->fetch()) {
        $_SESSION['error'] = "Student ID already exists";
        header("Location: edit_student.php?id=$id");
        exit();
    }
    
    // Check if email is already taken by another student
    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = :email AND id != :id");
    $stmt->execute([':email' => $email, ':id' => $id]);
    
    if($stmt->fetch()) {
        $_SESSION['error'] = "Email already exists";
        header("Location: edit_student.php?id=$id");
        exit();
    }
    
    // Update student record
    $query = "UPDATE students SET 
              student_id = :student_id, 
              full_name = :full_name, 
              email = :email, 
              phone = :phone, 
              blood_group = :blood_group, 
              department = :department, 
              year_of_study = :year_of_study, 
              is_available = :is_available
              $update_password
              WHERE id = :id";
    
    $stmt = $pdo->prepare($query);
    
    $params = [
        ':student_id' => $student_id,
        ':full_name' => $full_name,
        ':email' => $email,
        ':phone' => $phone,
        ':blood_group' => $blood_group,
        ':department' => $department,
        ':year_of_study' => $year_of_study,
        ':is_available' => $is_available,
        ':id' => $id
    ];
    
    if(!empty($password)) {
        $params[':password'] = $hashed_password;
    }
    
    $stmt->execute($params);
    
    $_SESSION['success'] = "Student updated successfully";
    header("Location: view_student.php?id=$id");
    exit();
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: edit_student.php?id=$id");
    exit();
}