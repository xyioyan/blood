<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';
require_once '../includes/blood-requests.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$bloodRequest = new BloodRequest($pdo);
$request_id = $_GET['id'] ?? 0;

// Get request by ID
$stmt = $pdo->prepare("SELECT br.*, s.full_name as requested_by_name, a.full_name as approved_by_name 
                      FROM blood_requests br 
                      LEFT JOIN students s ON br.requested_by = s.id 
                      LEFT JOIN admins a ON br.approved_by = a.id 
                      WHERE br.id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    $_SESSION['error'] = 'Blood request not found.';
    header('Location: manage_requests.php');
    exit;
}

// Get available donors for this blood group
$donors = $bloodRequest->getAvailableDonors($request['blood_group']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bloodRequest->updateRequestStatus(
        $request_id,
        $_POST['status'],
        $_SESSION['admin_id'],
        $_POST['admin_notes']
    );
    $_SESSION['success'] = 'Blood request status updated successfully!';
    header('Location: manage_requests.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Blood Request - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .request-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .detail-item {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
            min-width: 120px;
            margin-right: 15px;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-badge.approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-badge.rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-badge.completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .urgency-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .urgency-badge.Critical {
            background-color: #e74c3c;
            color: white;
        }
        
        .urgency-badge.High {
            background-color: #f39c12;
            color: white;
        }
        
        .urgency-badge.Medium {
            background-color: #3498db;
            color: white;
        }
        
        .urgency-badge.Low {
            background-color: #2ecc71;
            color: white;
        }
        
        .blood-group-badge {
            background-color: #e74c3c;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .donors-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .donors-table th,
        .donors-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .donors-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2);
        }
        
        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-sm {
            padding: 5px 8px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h1>Blood Request Details</h1>
                <a href="manage_requests.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Requests
                </a>
            </div>
            
            <div class="request-details-grid">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-user-injured"></i> Patient Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="detail-item">
                            <div class="detail-label">Patient Name:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['patient_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Blood Group:</div>
                            <div class="detail-value">
                                <span class="blood-group-badge"><?php echo htmlspecialchars($request['blood_group']); ?></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Units Needed:</div>
                            <div class="detail-value"><?php echo $request['units_needed']; ?> unit(s)</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Urgency:</div>
                            <div class="detail-value">
                                <span class="urgency-badge <?php echo $request['urgency']; ?>">
                                    <?php echo htmlspecialchars($request['urgency']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value">
                                <span class="status-badge <?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-hospital"></i> Hospital Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="detail-item">
                            <div class="detail-label">Hospital:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['hospital_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Address:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['hospital_address']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Contact Person:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['contact_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Contact Phone:</div>
                            <div class="detail-value">
                                <a href="tel:<?php echo $request['contact_phone']; ?>"><?php echo htmlspecialchars($request['contact_phone']); ?></a>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Additional Info:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['additional_info'] ?: 'None'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="request-details-grid">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-info-circle"></i> Request Information</h2>
                    </div>
                    <div class="card-body">
                        <div class="detail-item">
                            <div class="detail-label">Request ID:</div>
                            <div class="detail-value">#<?php echo $request['id']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Requested By:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['requested_by_name'] ?: 'Guest User'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Requested On:</div>
                            <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Last Updated:</div>
                            <div class="detail-value"><?php echo date('M j, Y g:i A', strtotime($request['updated_at'])); ?></div>
                        </div>
                        <?php if ($request['approved_by_name']): ?>
                        <div class="detail-item">
                            <div class="detail-label">Handled By:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($request['approved_by_name']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2><i class="fas fa-edit"></i> Update Status</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="pending" <?php echo $request['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $request['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $request['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="completed" <?php echo $request['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="admin_notes">Admin Notes</label>
                                <textarea class="form-control" id="admin_notes" name="admin_notes" placeholder="Add your notes here..."><?php echo htmlspecialchars($request['admin_notes'] ?? ''); ?></textarea>
                            </div>
                            <div class="action-buttons">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Status
                                </button>
                                <?php if ($request['status'] == 'approved'): ?>
                                    <a href="whatsapp_broadcast.php?request_id=<?php echo $request['id']; ?>" class="btn btn-success">
                                        <i class="fab fa-whatsapp"></i> Send WhatsApp Broadcast
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> Available Donors (<?php echo count($donors); ?>)</h2>
                    <span class="badge" style="background-color: #e74c3c; color: white; padding: 5px 10px; border-radius: 12px;">
                        <?php echo htmlspecialchars($request['blood_group']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (count($donors) > 0): ?>
                        <div class="table-responsive">
                            <table class="donors-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Last Donation</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($donors as $donor): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($donor['full_name']); ?></td>
                                        <td>
                                            <a href="tel:<?php echo $donor['phone']; ?>"><?php echo htmlspecialchars($donor['phone']); ?></a>
                                        </td>
                                        <td>
                                            <a href="mailto:<?php echo $donor['email']; ?>"><?php echo htmlspecialchars($donor['email']); ?></a>
                                        </td>
                                        <td><?php echo htmlspecialchars($donor['department']); ?></td>
                                        <td>
                                            <?php 
                                            if ($donor['last_donation_date']) {
                                                echo date('M j, Y', strtotime($donor['last_donation_date']));
                                                echo " <small>(" . $donor['days_since_last_donation'] . " days ago)</small>";
                                            } else {
                                                echo '<span style="color: #28a745; font-weight: 500;">Never donated</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="https://wa.me/<?php echo $donor['phone']; ?>?text=<?php echo urlencode("🆘 URGENT BLOOD NEEDED\n\nPatient: {$request['patient_name']}\nBlood Group: {$request['blood_group']}\nHospital: {$request['hospital_name']}\nContact: {$request['contact_name']} - {$request['contact_phone']}\n\nPlease help if you can donate. Thank you!"); ?>" 
                                               target="_blank" class="btn btn-sm btn-success" title="Contact via WhatsApp">
                                                <i class="fab fa-whatsapp"></i> WhatsApp
                                            </a>
                                            <a href="view_student.php?id=<?php echo $donor['id']; ?>" class="btn btn-sm btn-info" title="View Profile">
                                                <i class="fas fa-user"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 40px;">
                            <i class="fas fa-user-times" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                            <h3 style="color: #999;">No Available Donors</h3>
                            <p style="color: #666;">No eligible donors found for <?php echo htmlspecialchars($request['blood_group']); ?> blood group at this time.</p>
                            <p style="color: #666; font-size: 14px;">Donors must wait at least 90 days between donations.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
