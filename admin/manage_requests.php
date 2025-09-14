<?php

// session_start();
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
    }
}

// Get all requests
$requests = $bloodRequest->getRequests();

include '../includes/admin_header.php';
?>

<div class="container">
    <h2>Manage Blood Requests</h2>
    
    <table class="table table-bordered">
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
                <td><?php echo $request['id']; ?></td>
                <td><?php echo $request['patient_name']; ?></td>
                <td><?php echo $request['blood_group']; ?></td>
                <td><?php echo $request['units_needed']; ?></td>
                <td><?php echo $request['hospital_name']; ?></td>
                <td><?php echo $request['urgency']; ?></td>
                <td>
                    <span class="badge badge-<?php 
                        switch($request['status']) {
                            case 'pending': echo 'warning'; break;
                            case 'approved': echo 'success'; break;
                            case 'rejected': echo 'danger'; break;
                            case 'completed': echo 'info'; break;
                        }
                    ?>">
                        <?php echo ucfirst($request['status']); ?>
                    </span>
                </td>
                <td><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                <td>
                    <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-info">View</a>
                    <a href="whatsapp_broadcast.php?request_id=<?php echo $request['id']; ?>" class="btn btn-sm btn-success">Notify</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
