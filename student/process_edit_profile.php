<?php
session_start();
require_once '../includes/config.php';

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: edit_profile.php");
    exit();
}

// Get form data
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$blood_group = trim($_POST['blood_group']);
$department = trim($_POST['department']);
$year_of_study = trim($_POST['year_of_study']);
$current_password = trim($_POST['current_password']);
$new_password = trim($_POST['new_password']);
$confirm_password = trim($_POST['confirm_password']);

// Validate required fields
if(empty($full_name) || empty($email) || empty($phone) || empty($blood_group) || 
   empty($department) || empty($year_of_study)) {
    $_SESSION['error'] = "All fields except password are required";
    header("Location: edit_profile.php");
    exit();
}

// Validate email format
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format";
    header("Location: edit_profile.php");
    exit();
}

// Check if passwords are being changed
$password_changed = false;
if(!empty($new_password) || !empty($confirm_password)) {
    if(empty($current_password)) {
        $_SESSION['error'] = "Current password is required to change password";
        header("Location: edit_profile.php");
        exit();
    }
    
    if($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match";
        header("Location: edit_profile.php");
        exit();
    }
    
    if(strlen($new_password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters";
        header("Location: edit_profile.php");
        exit();
    }
    
    $password_changed = true;
}

try {
    // Get current student data
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$student) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    // Verify current password if changing password
    if($password_changed && !password_verify($current_password, $student['password'])) {
        $_SESSION['error'] = "Current password is incorrect";
        header("Location: edit_profile.php");
        exit();
    }
    
    // Check if email is being changed to one that already exists
    if($email != $student['email']) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
        $stmt->execute([$email, $student['id']]);
        if($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Email already in use by another account";
            header("Location: edit_profile.php");
            exit();
        }
    }
    
    // Prepare update query
    $update_data = [
        'full_name' => $full_name,
        'email' => $email,
        'phone' => $phone,
        'blood_group' => $blood_group,
        'department' => $department,
        'year_of_study' => $year_of_study,
        'id' => $student['id']
    ];
    
    $update_query = "UPDATE students SET 
                    full_name = :full_name, 
                    email = :email, 
                    phone = :phone, 
                    blood_group = :blood_group, 
                    department = :department, 
                    year_of_study = :year_of_study";
    
    // Add password to update if changed
    if($password_changed) {
        $update_query .= ", password = :password";
        $update_data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
    }
    
    $update_query .= " WHERE id = :id";
    
    // Execute update
    $stmt = $pdo->prepare($update_query);
    $stmt->execute($update_data);
    
    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: profile.php");
    exit();
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
    header("Location: edit_profile.php");
    exit();
}
?>