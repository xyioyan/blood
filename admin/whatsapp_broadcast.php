<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';
require_once '../includes/blood-requests.php';
require_once '../includes/whatsapp-broadcast.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$bloodRequest = new BloodRequest($pdo);
$whatsapp = new WhatsAppBroadcast($pdo);

$request_id = $_GET['request_id'];
$request = $bloodRequest->getRequestById($request_id); // Add this method to BloodRequest class

if (!$request) {
    header('Location: manage_requests.php');
    exit;
}

// Generate message
$message = $whatsapp->generateMessage($request);

// Get available donors
$donors = $bloodRequest->getAvailableDonors($request['blood_group']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($whatsapp->createBroadcast($request_id, $message, $_SESSION['admin_id'])) {
        $success = "Broadcast created successfully!";
        
        // Update recipient count
        $sql = "UPDATE whatsapp_broadcasts SET recipient_count = ? WHERE request_id = ? ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([count($donors), $request_id]);
    } else {
        $error = "Failed to create broadcast.";
    }
}

include '../includes/admin_header.php';
?>

<div class="container">
    <h2>Send WhatsApp Broadcast</h2>
    
    <?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Request Details</h5>
        </div>
        <div class="card-body">
            <p><strong>Patient:</strong> <?php echo $request['patient_name']; ?></p>
            <p><strong>Blood Group:</strong> <?php echo $request['blood_group']; ?></p>
            <p><strong>Hospital:</strong> <?php echo $request['hospital_name']; ?></p>
            <p><strong>Contact:</strong> <?php echo $request['contact_name']; ?> (<?php echo $request['contact_phone']; ?>)</p>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Message Preview</h5>
        </div>
        <div class="card-body">
            <pre style="white-space: pre-wrap;"><?php echo $message; ?></pre>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Recipients (<?php echo count($donors); ?> available donors)</h5>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <?php foreach ($donors as $donor): ?>
                <li class="list-group-item">
                    <?php echo $donor['full_name']; ?> - <?php echo $donor['phone']; ?>
                    <a href="https://wa.me/<?php echo $donor['phone']; ?>?text=<?php echo urlencode($message); ?>" 
                       target="_blank" class="btn btn-sm btn-success float-right">Send WhatsApp</a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <form method="POST">
        <button type="submit" class="btn btn-primary">Save Broadcast Record</button>
        <a href="manage_requests.php" class="btn btn-secondary">Back to Requests</a>
    </form>
</div>
