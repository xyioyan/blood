<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$event_id = $_GET['event_id'] ?? 0;

// Get event details
$stmt = $pdo->prepare("SELECT * FROM donation_events WHERE id = ? AND event_date >= CURDATE()");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    $_SESSION['error'] = "Event not found or has already passed";
    header("Location: ./student_events.php");
    exit();
}

// Get numeric student ID from database
$stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]); // If session stores STU001
$studentRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$studentRow) {
    $_SESSION['error'] = "Student not found in database";
    header("Location: ./student_events.php");
    exit();
}

$numericId = $studentRow['id'];

// Check if already registered
$stmt = $pdo->prepare("SELECT 1 FROM event_registrations WHERE event_id = ? AND student_id = ?");
$stmt->execute([$event_id, $numericId]);
$is_registered = $stmt->fetchColumn();

// Handle registration form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($is_registered) {
        // Cancel registration
        $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ? AND student_id = ?");
        $stmt->execute([$event_id, $numericId]);
        $_SESSION['success'] = "Registration canceled successfully";
    } else {
        // Register for event
        $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, student_id, registered_at) VALUES (?, ?, NOW())");
        $stmt->execute([$event_id, $numericId]);
        $_SESSION['success'] = "Registered for event successfully!";
    }
    header("Location: register_event.php?event_id=$event_id");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/student_header.php'; ?>
    
    <div class="container">
        <div class="event-registration">
            <div class="event-header">
                <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
                <p class="event-date">
                    <i class="fas fa-calendar-day"></i> 
                    <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                </p>
                <p class="event-time">
                    <i class="fas fa-clock"></i> 
                    <?php echo date('h:i A', strtotime($event['start_time'])); ?> - 
                    <?php echo date('h:i A', strtotime($event['end_time'])); ?>
                </p>
                <p class="event-location">
                    <i class="fas fa-map-marker-alt"></i> 
                    <?php echo htmlspecialchars($event['location']); ?>
                </p>
            </div>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <div class="event-details">
                <?php if(!empty($event['description'])): ?>
                    <h3>Event Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                <?php endif; ?>
                
                <h3>Registration Status</h3>
                <p>You are currently <?php echo $is_registered ? 'registered' : 'not registered'; ?> for this event.</p>
                
                <form method="post">
                    <button type="submit" class="btn <?php echo $is_registered ? 'btn-danger' : 'btn-primary'; ?>">
                        <?php echo $is_registered ? 'Cancel Registration' : 'Register for Event'; ?>
                    </button>
                </form>
            </div>
            
            <div class="event-reminder">
                <h3>Reminders</h3>
                <p>You will receive an email reminder 24 hours before the event.</p>
                <p>Please bring your student ID and ensure you meet all donation requirements.</p>
            </div>
        </div>
    </div>
    
    <?php include '../includes/student_footer.php'; ?>
</body>
</html>
