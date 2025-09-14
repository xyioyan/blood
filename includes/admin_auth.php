<?php
session_start();

// Redirect to login if not authenticated
if(!isset($_SESSION['admin_id'])) {
    header("Location: /blood/admin/login.php");
    exit();
}

// Database connection
require_once 'config.php';

// Check if admin still exists in database
try {
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    
    if(!$admin) {
        session_destroy();
        header("Location: /blood/admin/login.php");
        exit();
    }
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Admin functions
require_once 'admin_functions.php';
?>