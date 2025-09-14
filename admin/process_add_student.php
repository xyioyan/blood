<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: add_student.php");
    exit();
}

// Get form data
$student_id = trim($_POST['student_id']);
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$blood_group = trim($_POST['blood_group']);
$department = trim($_POST['department']);
$year_of_study = (int)$_POST['year_of_study'];
$password = trim($_POST['password']);
$is_available = isset($_POST['is_available']) ? 1 : 0;

// Validate inputs
if(empty($student_id) || empty($full_name) || empty($email) || empty($phone) || 
   empty($blood_group) || empty($department) || empty($year_of_study) || empty($password)) {
    $_SESSION['error'] = "All fields are required";
    header("Location: add_student.php");
    exit();
}

if(strlen($password) < 8) {
    $_SESSION['error'] = "Password must be at least 8 characters";
    header("Location: add_student.php");
    exit();
}

if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format";
    header("Location: add_student.php");
    exit();
}

try {
    // Check if student ID or email already exists
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
    $stmt->execute([$student_id, $email]);
    
    if($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Student ID or Email already exists";
        header("Location: add_student.php");
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new student
    $stmt = $pdo->prepare("INSERT INTO students 
                          (student_id, full_name, email, phone, blood_group, department, year_of_study, password, is_available) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $student_id,
        $full_name,
        $email,
        $phone,
        $blood_group,
        $department,
        $year_of_study,
        $hashed_password,
        $is_available
    ]);
    
    $_SESSION['success'] = "Student added successfully!";
    header("Location: manage_students.php");
    exit();
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Error adding student: " . $e->getMessage();
    header("Location: add_student.php");
    exit();
}
?>