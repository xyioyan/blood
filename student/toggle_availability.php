<?php
session_start();
require_once '../includes/config.php';

// Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Get input
$available = isset($_POST['available']) ? intval($_POST['available']) : null;

if (!in_array($available, [0, 1])) {
    echo json_encode(['success' => false, 'message' => 'Invalid availability value']);
    exit();
}

try {
    // Fetch student data
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }

    // Check eligibility based on last donation date
    $eligible = true;

    if (!empty($student['last_donation_date'])) {
        $lastDonation = new DateTime($student['last_donation_date']);
        $today = new DateTime();
        $interval = $today->diff($lastDonation);
        $daysPassed = (int)$interval->format('%r%a');

        if ($daysPassed < 90) {
            $eligible = false;
        }
    }

    if (!$eligible) {
        echo json_encode(['success' => false, 'message' => 'You are not eligible to change availability yet']);
        exit();
    }

    // Update availability only if eligible
    $updateStmt = $pdo->prepare("UPDATE students SET is_available = ? WHERE id = ?");
    $updateStmt->execute([$available, $student['id']]);

    echo json_encode(['success' => true, 'message' => 'Availability updated successfully']);
    exit();

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}
?>
