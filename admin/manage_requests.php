<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';
require_once '../includes/blood-requests.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$bloodRequest = new BloodRequest($pdo);

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $bloodRequest->updateRequestStatus(
            $_POST['request_id'],
            $_POST['status'],
            $_SESSION['admin_id'],
            $_POST['admin_notes']
        );
        $_SESSION['success'] = 'Blood request status updated successfully!';
        header('Location: manage_requests.php');
        exit;
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$urgency_filter = $_GET['urgency'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$query = "SELECT br.*, s.full_name as requested_by_name, a.full_name as approved_by_name 
          FROM blood_requests br 
          LEFT JOIN students s ON br.requested_by = s.id 
          LEFT JOIN admins a ON br.approved_by = a.id
          WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND br.status = ?";
    $params[] = $status_filter;
}

if (!empty($urgency_filter)) {
    $query .= " AND br.urgency = ?";
    $params[] = $urgency_filter;
}

if (!empty($search)) {
    $query .= " AND (br.patient_name LIKE ? OR br.hospital_name LIKE ? OR br.contact_name LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

$query .= " ORDER BY br.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for each status
$pending_count = $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'pending'")->fetchColumn();
$approved_count = $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'approved'")->fetchColumn();
$rejected_count = $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'rejected'")->fetchColumn();
$completed_count = $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE status = 'completed'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blood Requests - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .request-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card-small {
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 15px;
            text-align: center;
        }
        
        .stat-card-small .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card-small.pending .stat-value { color: #f39c12; }
        .stat-card-small.approved .stat-value { color: #2ecc71; }
        .stat-card-small.rejected .stat-value { color: #e74c3c; }
        .stat-card-small.completed .stat-value { color: #3498db; }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .filter-form input,
        .filter-form select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .requests-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .requests-table th,
        .requests-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .requests-table th {
            background-color: #f8f9fa;
            font-weight: 600;
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
            padding: 3px 6px;
            border-radius: 8px;
            font-size: 11px;
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
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 8px;
            font-size: 12px;
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
                <h1>Manage Blood Requests</h1>
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
            <div class="request-stats">
                <div class="stat-card-small pending">
                    <div class="stat-value"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card-small approved">
                    <div class="stat-value"><?php echo $approved_count; ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card-small rejected">
                    <div class="stat-value"><?php echo $rejected_count; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
                <div class="stat-card-small completed">
                    <div class="stat-value"><?php echo $completed_count; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Blood Request Records</h2>
                    <form method="get" class="filter-form">
                        <input type="text" name="search" placeholder="Search patient, hospital..." value="<?php echo htmlspecialchars($search); ?>">
                        
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                        
                        <select name="urgency">
                            <option value="">All Urgency</option>
                            <option value="Critical" <?php echo $urgency_filter == 'Critical' ? 'selected' : ''; ?>>Critical</option>
                            <option value="High" <?php echo $urgency_filter == 'High' ? 'selected' : ''; ?>>High</option>
                            <option value="Medium" <?php echo $urgency_filter == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="Low" <?php echo $urgency_filter == 'Low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                        
                        <button type="submit" class="btn btn-secondary">Filter</button>
                        <a href="manage_requests.php" class="btn btn-outline">Reset</a>
                    </form>
                </div>
                
                <div class="card-body">
                    <?php if (count($requests) > 0): ?>
                        <div class="table-responsive">
                            <table class="requests-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Patient</th>
                                        <th>Blood Group</th>
                                        <th>Units</th>
                                        <th>Hospital</th>
                                        <th>Urgency</th>
                                        <th>Status</th>
                                        <th>Requested On</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td>#<?php echo $request['id']; ?></td>
                                        <td><?php echo htmlspecialchars($request['patient_name']); ?></td>
                                        <td>
                                            <span class="blood-group-badge" style="background-color: #e74c3c; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                                <?php echo htmlspecialchars($request['blood_group']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $request['units_needed']; ?></td>
                                        <td><?php echo htmlspecialchars($request['hospital_name']); ?></td>
                                        <td>
                                            <span class="urgency-badge <?php echo $request['urgency']; ?>">
                                                <?php echo htmlspecialchars($request['urgency']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $request['status']; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                                        <td>
                                            <div class="actions">
                                                <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($request['status'] == 'approved'): ?>
                                                    <a href="whatsapp_broadcast.php?request_id=<?php echo $request['id']; ?>" class="btn btn-sm btn-success" title="Send WhatsApp Broadcast">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                            <h3 style="color: #999;">No Blood Requests Found</h3>
                            <p style="color: #666;">No blood requests match your current filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
