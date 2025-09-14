<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';
require_once '../includes/mailer.php';

$event_id = $_GET['id'] ?? 0;

// Get event details
$stmt = $pdo->prepare("SELECT * FROM donation_events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$event) {
    $_SESSION['error'] = "Event not found";
    header("Location: donation_events.php");
    exit();
}

// Get registrations who haven't checked in yet
$registrations = $pdo->prepare("
    SELECT s.* 
    FROM event_registrations r
    JOIN students s ON r.student_id = s.id
    LEFT JOIN event_attendance a ON a.event_id = r.event_id AND a.student_id = r.student_id
    WHERE r.event_id = ? AND a.id IS NULL
");
$registrations->execute([$event_id]);
$registrations = $registrations->fetchAll(PDO::FETCH_ASSOC);

// Send reminders
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sent = 0;
    $errors = 0;
    
    foreach($registrations as $student) {
        $subject = "Reminder: {$event['event_name']} - Tomorrow";
        $message = "Dear {$student['full_name']},<br><br>"
                 . "This is a reminder about the blood donation event tomorrow:<br><br>"
                 . "<strong>{$event['event_name']}</strong><br>"
                 . "Date: " . date('F j, Y', strtotime($event['event_date'])) . "<br>"
                 . "Time: " . date('h:i A', strtotime($event['start_time'])) . " - " . date('h:i A', strtotime($event['end_time'])) . "<br>"
                 . "Location: {$event['location']}<br><br>"
                 . "Please remember to bring your student ID.<br><br>"
                 . "Thank you for your participation!<br><br>"
                 . "Blood Donation Team";
        
        if(sendEmail($student['email'], $subject, $message)) {
            $sent++;
        } else {
            $errors++;
        }
    }
    
    $_SESSION['success'] = "Sent $sent reminders. $errors failed.";
    header("Location: event_registrations.php?id=$event_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Reminders - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h1>Send Reminders</h1>
                <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
                <a href="event_registrations.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Registrations
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Confirm Reminder Send</h2>
                </div>
                <div class="card-body">
                    <p>You are about to send reminders to <?php echo count($registrations); ?> registered students who haven't checked in yet.</p>
                    <p>The following message will be sent:</p>
                    
                    <div class="email-preview">
                        <h3>Subject: Reminder: <?php echo htmlspecialchars($event['event_name']); ?> - Tomorrow</h3>
                        <div class="email-content">
                            <p>Dear [Student Name],</p>
                            <p>This is a reminder about the blood donation event tomorrow:</p>
                            <p><strong><?php echo htmlspecialchars($event['event_name']); ?></strong><br>
                            Date: <?php echo date('F j, Y', strtotime($event['event_date'])); ?><br>
                            Time: <?php echo date('h:i A', strtotime($event['start_time'])); ?> - <?php echo date('h:i A', strtotime($event['end_time'])); ?><br>
                            Location: <?php echo htmlspecialchars($event['location']); ?></p>
                            <p>Please remember to bring your student ID.</p>
                            <p>Thank you for your participation!</p>
                            <p>Blood Donation Team</p>
                        </div>
                    </div>
                    
                    <form method="post">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Reminders Now
                        </button>
                        <a href="event_registrations.php?id=<?php echo $event_id; ?>" class="btn btn-outline">
                            Cancel
                        </a>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>