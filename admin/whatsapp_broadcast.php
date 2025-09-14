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

$request_id = $_GET['request_id'] ?? 0;

// Get request details
$stmt = $pdo->prepare("SELECT br.*, s.full_name as requested_by_name 
                      FROM blood_requests br 
                      LEFT JOIN students s ON br.requested_by = s.id 
                      WHERE br.id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    $_SESSION['error'] = 'Blood request not found.';
    header('Location: manage_requests.php');
    exit;
}

// Get system settings for WhatsApp group
$settings_stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
$settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);

// Generate message
$message = $whatsapp->generateMessage($request);

// Get available donors
$donors = $bloodRequest->getAvailableDonors($request['blood_group']);

// Get previous broadcasts for this request
$previous_broadcasts = $whatsapp->getBroadcasts($request_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_broadcast'])) {
        if ($whatsapp->createBroadcast($request_id, $message, $_SESSION['admin_id'], count($donors))) {
            $_SESSION['success'] = "Broadcast record saved successfully!";
        } else {
            $_SESSION['error'] = "Failed to save broadcast record.";
        }
    }
    header('Location: whatsapp_broadcast.php?request_id=' . $request_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Broadcast - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .broadcast-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card-broadcast {
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 15px;
            text-align: center;
        }
        
        .stat-card-broadcast .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card-broadcast.donors .stat-value { color: #2ecc71; }
        .stat-card-broadcast.broadcasts .stat-value { color: #3498db; }
        .stat-card-broadcast.urgency .stat-value { color: #e74c3c; }
        
        .message-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            white-space: pre-wrap;
            line-height: 1.4;
            margin-bottom: 15px;
        }
        
        .donor-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .donor-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .donor-item:last-child {
            border-bottom: none;
        }
        
        .donor-info {
            flex: 1;
        }
        
        .donor-name {
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .donor-details {
            font-size: 12px;
            color: #666;
        }
        
        .broadcast-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .action-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        .group-broadcast-card {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            color: white;
        }
        
        .group-broadcast-card h3 {
            color: white;
        }
        
        .whatsapp-button {
            background: #25D366;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .whatsapp-button:hover {
            background: #128C7E;
            color: white;
            text-decoration: none;
        }
        
        .broadcast-history {
            margin-top: 20px;
        }
        
        .history-item {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .history-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .bulk-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h1><i class="fab fa-whatsapp"></i> WhatsApp Broadcast</h1>
                <a href="manage_requests.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Requests
                </a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="broadcast-stats">
                <div class="stat-card-broadcast donors">
                    <div class="stat-value"><?php echo count($donors); ?></div>
                    <div class="stat-label">Available Donors</div>
                </div>
                <div class="stat-card-broadcast broadcasts">
                    <div class="stat-value"><?php echo count($previous_broadcasts); ?></div>
                    <div class="stat-label">Previous Broadcasts</div>
                </div>
                <div class="stat-card-broadcast urgency">
                    <div class="stat-value"><?php echo htmlspecialchars($request['urgency']); ?></div>
                    <div class="stat-label">Urgency Level</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-info-circle"></i> Request Details</h2>
                </div>
                <div class="card-body">
                    <div class="request-details-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div>
                            <strong>Patient:</strong> <?php echo htmlspecialchars($request['patient_name']); ?><br>
                            <strong>Blood Group:</strong> 
                            <span class="blood-group-badge" style="background-color: #e74c3c; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                <?php echo htmlspecialchars($request['blood_group']); ?>
                            </span><br>
                            <strong>Units Needed:</strong> <?php echo $request['units_needed']; ?>
                        </div>
                        <div>
                            <strong>Hospital:</strong> <?php echo htmlspecialchars($request['hospital_name']); ?><br>
                            <strong>Contact:</strong> <?php echo htmlspecialchars($request['contact_name']); ?><br>
                            <strong>Phone:</strong> <a href="tel:<?php echo $request['contact_phone']; ?>"><?php echo htmlspecialchars($request['contact_phone']); ?></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-comment-alt"></i> Message Preview</h2>
                </div>
                <div class="card-body">
                    <div class="message-preview"><?php echo htmlspecialchars($message); ?></div>
                    <div class="bulk-actions">
                        <button onclick="copyMessage()" class="btn btn-secondary">
                            <i class="fas fa-copy"></i> Copy Message
                        </button>
                        <button onclick="shareMessage()" class="btn btn-info">
                            <i class="fas fa-share"></i> Share Message
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Broadcast Actions -->
            <div class="broadcast-actions">
                <?php if ($settings && !empty($settings['whatsapp_group_link'])): ?>
                <div class="action-card group-broadcast-card">
                    <h3><i class="fab fa-whatsapp"></i> Group Broadcast</h3>
                    <p>Send to WhatsApp Group: <strong><?php echo htmlspecialchars($settings['whatsapp_group_name'] ?: 'Blood Donors Community'); ?></strong></p>
                    <a href="<?php echo htmlspecialchars($settings['whatsapp_group_link']); ?>?text=<?php echo urlencode($message); ?>" 
                       target="_blank" class="whatsapp-button">
                        <i class="fab fa-whatsapp"></i> Send to Group
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="action-card">
                    <h3><i class="fas fa-users"></i> Individual Broadcast</h3>
                    <p>Send to <?php echo count($donors); ?> available donors individually</p>
                    <div class="bulk-actions">
                        <button onclick="openAllWhatsApp()" class="btn btn-success">
                            <i class="fab fa-whatsapp"></i> Send to All Donors
                        </button>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="save_broadcast" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Broadcast Record
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> Available Donors (<?php echo count($donors); ?>)</h2>
                </div>
                <div class="card-body">
                    <?php if (count($donors) > 0): ?>
                        <div class="donor-list">
                            <?php foreach ($donors as $donor): ?>
                            <div class="donor-item">
                                <div class="donor-info">
                                    <div class="donor-name"><?php echo htmlspecialchars($donor['full_name']); ?></div>
                                    <div class="donor-details">
                                        <?php echo htmlspecialchars($donor['department']); ?> | 
                                        <?php echo htmlspecialchars($donor['phone']); ?> | 
                                        Last donation: <?php 
                                        if ($donor['last_donation_date']) {
                                            echo date('M j, Y', strtotime($donor['last_donation_date'])) . ' (' . $donor['days_since_last_donation'] . ' days ago)';
                                        } else {
                                            echo 'Never';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="donor-actions">
                                    <a href="https://wa.me/<?php echo $donor['phone']; ?>?text=<?php echo urlencode($message); ?>" 
                                       target="_blank" class="btn btn-sm btn-success" title="Send WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                    <a href="tel:<?php echo $donor['phone']; ?>" class="btn btn-sm btn-info" title="Call">
                                        <i class="fas fa-phone"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 40px;">
                            <i class="fas fa-user-times" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                            <h3 style="color: #999;">No Available Donors</h3>
                            <p style="color: #666;">No eligible donors found for <?php echo htmlspecialchars($request['blood_group']); ?> blood group.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (count($previous_broadcasts) > 0): ?>
            <div class="card broadcast-history">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Broadcast History</h2>
                </div>
                <div class="card-body">
                    <?php foreach ($previous_broadcasts as $broadcast): ?>
                    <div class="history-item">
                        <div class="history-meta">
                            <span><strong>Sent by:</strong> <?php echo htmlspecialchars($broadcast['admin_name']); ?></span>
                            <span><strong>Recipients:</strong> <?php echo $broadcast['recipient_count']; ?></span>
                            <span><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($broadcast['created_at'])); ?></span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            <?php echo nl2br(htmlspecialchars(substr($broadcast['message'], 0, 200))); ?><?php echo strlen($broadcast['message']) > 200 ? '...' : ''; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function copyMessage() {
            const message = `<?php echo addslashes($message); ?>`;
            navigator.clipboard.writeText(message).then(function() {
                alert('Message copied to clipboard!');
            }).catch(function(err) {
                console.error('Failed to copy message: ', err);
            });
        }
        
        function shareMessage() {
            if (navigator.share) {
                navigator.share({
                    title: 'Urgent Blood Request',
                    text: `<?php echo addslashes($message); ?>`
                });
            } else {
                copyMessage();
            }
        }
        
        function openAllWhatsApp() {
            const donors = <?php echo json_encode($donors); ?>;
            const message = `<?php echo addslashes($message); ?>`;
            
            if (confirm(`This will open ${donors.length} WhatsApp chat windows. Continue?`)) {
                donors.forEach((donor, index) => {
                    setTimeout(() => {
                        window.open(`https://wa.me/${donor.phone}?text=${encodeURIComponent(message)}`, '_blank');
                    }, index * 500); // Delay each window by 500ms
                });
            }
        }
    </script>
</body>
</html>
