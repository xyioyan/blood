<?php
session_start();
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

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

// Handle photo upload
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['photos'])) {
    $uploadDir = '../uploads/event_photos/';
    
    // Create directory if not exists
    if(!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $uploaded = 0;
    $errors = 0;
    
    foreach($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
        if($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
            $filename = uniqid() . '_' . basename($_FILES['photos']['name'][$key]);
            $target = $uploadDir . $filename;
            
            if(move_uploaded_file($tmp_name, $target)) {
                // Save to database
                $stmt = $pdo->prepare("INSERT INTO event_photos (event_id, filename, uploaded_by, uploaded_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$event_id, $filename, $_SESSION['admin_id']]);
                $uploaded++;
            } else {
                $errors++;
            }
        } else {
            $errors++;
        }
    }
    
    $_SESSION['success'] = "Uploaded $uploaded photos. $errors failed.";
    header("Location: event_gallery.php?id=$event_id");
    exit();
}

// Get event photos
$photos = $pdo->prepare("
    SELECT p.*, a.full_name as uploaded_by_name
    FROM event_photos p
    JOIN admins a ON p.uploaded_by = a.id
    WHERE p.event_id = ?
    ORDER BY p.uploaded_at DESC
");
$photos->execute([$event_id]);
$photos = $photos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Gallery - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h1>Event Gallery</h1>
                <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
                <a href="view_event.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Event
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Upload Photos</h2>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="photos">Select Photos (Multiple allowed)</label>
                            <input type="file" id="photos" name="photos[]" multiple accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload Photos
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Event Photos</h2>
                </div>
                <div class="card-body">
                    <?php if(count($photos) > 0): ?>
                        <div class="photo-gallery">
                            <?php foreach($photos as $photo): ?>
                                <div class="photo-item">
                                    <a href="../uploads/event_photos/<?php echo htmlspecialchars($photo['filename']); ?>" data-lightbox="event-gallery" data-title="Uploaded by <?php echo htmlspecialchars($photo['uploaded_by_name']); ?> on <?php echo date('M j, Y h:i A', strtotime($photo['uploaded_at'])); ?>">
                                        <img src="../uploads/event_photos/<?php echo htmlspecialchars($photo['filename']); ?>" alt="Event photo">
                                    </a>
                                    <div class="photo-info">
                                        <p>Uploaded by <?php echo htmlspecialchars($photo['uploaded_by_name']); ?></p>
                                        <small><?php echo date('M j, Y h:i A', strtotime($photo['uploaded_at'])); ?></small>
                                        <a href="process_event_actions.php?action=delete_photo&id=<?php echo $photo['id']; ?>&event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this photo?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-camera"></i>
                            <p>No photos uploaded yet for this event</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
</body>
</html>