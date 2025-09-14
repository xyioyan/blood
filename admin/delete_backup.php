<?php
session_start();
require_once '../includes/admin_auth.php';

// Only superadmin can delete backups
if ($_SESSION['admin_role'] !== 'superadmin') {
    $_SESSION['error'] = "You don't have permission to delete backups.";
    header("Location: settings.php");
    exit();
}

if (isset($_GET['file'])) {
    $file = basename($_GET['file']); // prevent directory traversal
    $path = "../backups/" . $file;

    if (file_exists($path)) {
        if (unlink($path)) {
            $_SESSION['success'] = "Backup deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete backup.";
        }
    } else {
        $_SESSION['error'] = "Backup file not found.";
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: settings.php");
exit();
