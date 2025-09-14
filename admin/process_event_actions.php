<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' || isset($_GET['action'])) {
    $action = $_POST['action'] ?? $_GET['action'];
    
    try {
        switch($action) {
            case 'add':
                // Validate and add new event
                $event_name = trim($_POST['event_name']);
                $event_date = trim($_POST['event_date']);
                $start_time = trim($_POST['start_time']);
                $end_time = trim($_POST['end_time']);
                $location = trim($_POST['location']);
                $target_donors = (int)$_POST['target_donors'];
                $target_blood_groups = isset($_POST['target_blood_groups']) ? 
                    json_encode($_POST['target_blood_groups']) : '[]';
                $target_departments = isset($_POST['target_departments']) ? 
                    json_encode($_POST['target_departments']) : '[]';
                $description = trim($_POST['description']);
                
                if(empty($event_name) || empty($event_date) || empty($start_time) || 
                   empty($end_time) || empty($location) || empty($target_donors)) {
                    $_SESSION['error'] = "All required fields must be filled";
                    header("Location: add_event.php");
                    exit();
                }
                
                if(strtotime($start_time) >= strtotime($end_time)) {
                    $_SESSION['error'] = "End time must be after start time";
                    header("Location: add_event.php");
                    exit();
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO donation_events 
                    (event_name, event_date, start_time, end_time, location, 
                     target_donors, target_blood_groups, target_departments, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $event_name,
                    $event_date,
                    $start_time,
                    $end_time,
                    $location,
                    $target_donors,
                    $target_blood_groups,
                    $target_departments,
                    $description
                ]);
                
                $_SESSION['success'] = "Event added successfully!";
                header("Location: donation_events.php");
                break;
                
            case 'update':
                // Update existing event
                $id = (int)$_POST['id'];
                $event_name = trim($_POST['event_name']);
                $event_date = trim($_POST['event_date']);
                $start_time = trim($_POST['start_time']);
                $end_time = trim($_POST['end_time']);
                $location = trim($_POST['location']);
                $target_donors = (int)$_POST['target_donors'];
                $target_blood_groups = isset($_POST['target_blood_groups']) ? 
                    json_encode($_POST['target_blood_groups']) : '[]';
                $target_departments = isset($_POST['target_departments']) ? 
                    json_encode($_POST['target_departments']) : '[]';
                $description = trim($_POST['description']);
                
                if(empty($event_name) || empty($event_date) || empty($start_time) || 
                   empty($end_time) || empty($location) || empty($target_donors)) {
                    $_SESSION['error'] = "All required fields must be filled";
                    header("Location: edit_event.php?id=$id");
                    exit();
                }
                
                if(strtotime($start_time) >= strtotime($end_time)) {
                    $_SESSION['error'] = "End time must be after start time";
                    header("Location: edit_event.php?id=$id");
                    exit();
                }
                
                $stmt = $pdo->prepare("
                    UPDATE donation_events
                    SET event_name = ?, event_date = ?, start_time = ?, end_time = ?, 
                        location = ?, target_donors = ?, target_blood_groups = ?, 
                        target_departments = ?, description = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $event_name,
                    $event_date,
                    $start_time,
                    $end_time,
                    $location,
                    $target_donors,
                    $target_blood_groups,
                    $target_departments,
                    $description,
                    $id
                ]);
                
                $_SESSION['success'] = "Event updated successfully!";
                header("Location: view_event.php?id=$id");
                break;
                
            case 'delete':
                $id = (int)$_GET['id'];
                
                $stmt = $pdo->prepare("SELECT id FROM donation_events WHERE id = ?");
                $stmt->execute([$id]);
                
                if($stmt->rowCount() === 0) {
                    $_SESSION['error'] = "Event not found";
                    header("Location: donation_events.php");
                    exit();
                }
                
                $stmt = $pdo->prepare("DELETE FROM donation_events WHERE id = ?");
                $stmt->execute([$id]);
                
                $_SESSION['success'] = "Event deleted successfully!";
                header("Location: donation_events.php");
                break;
                
            default:
                $_SESSION['error'] = "Invalid action";
                header("Location: donation_events.php");
        }
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: donation_events.php");
    }
    
} else {
    header("Location: donation_events.php");
}
?>
