<?php
require_once '../includes/admin_auth.php';
require_once '../includes/blood-requests.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$bloodRequest = new BloodRequest($pdo);
$request_id = $_GET['id'];
$request = $bloodRequest->getRequestById($request_id);

if (!$request) {
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
    header('Location: manage_requests.php');
    exit;
}

include '../includes/admin_header.php';
?>

<div class="container">
    <h2>Blood Request Details</h2>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><?php echo $request['patient_name']; ?></h5>
            <p><strong>Blood Group:</strong> <?php echo $request['blood_group']; ?></p>
            <p><strong>Units Needed:</strong> <?php echo $request['units_needed']; ?></p>
            <p><strong>Hospital:</strong> <?php echo $request['hospital_name']; ?></p>
            <p><strong>Address:</strong> <?php echo $request['hospital_address']; ?></p>
            <p><strong>Urgency:</strong> <?php echo $request['urgency']; ?></p>
            <p><strong>Contact:</strong> <?php echo $request['contact_name']; ?> (<?php echo $request['contact_phone']; ?>)</p>
            <p><strong>Additional Info:</strong> <?php echo $request['additional_info']; ?></p>
            <p><strong>Status:</strong> <?php echo ucfirst($request['status']); ?></p>
            <p><strong>Requested By:</strong> <?php echo $request['requested_by_name'] ? $request['requested_by_name'] : 'Guest'; ?></p>
            <p><strong>Requested On:</strong> <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></p>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Update Status</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="pending" <?php if($request['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="approved" <?php if($request['status'] == 'approved') echo 'selected'; ?>>Approved</option>
                        <option value="rejected" <?php if($request['status'] == 'rejected') echo 'selected'; ?>>Rejected</option>
                        <option value="completed" <?php if($request['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="admin_notes">Admin Notes</label>
                    <textarea class="form-control" id="admin_notes" name="admin_notes"><?php echo $request['admin_notes']; ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Available Donors (<?php echo count($donors); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (count($donors) > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Last Donation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donors as $donor): ?>
                    <tr>
                        <td><?php echo $donor['full_name']; ?></td>
                        <td><?php echo $donor['phone']; ?></td>
                        <td><?php echo $donor['email']; ?></td>
                        <td>
                            <?php 
                            if ($donor['last_donation_date']) {
                                echo date('M j, Y', strtotime($donor['last_donation_date']));
                                echo " (" . $donor['days_since_last_donation'] . " days ago)";
                            } else {
                                echo "Never donated";
                            }
                            ?>
                        </td>
                        <td>
                            <a href="https://wa.me/<?php echo $donor['phone']; ?>?text=<?php echo urlencode("Blood donation needed for {$request['patient_name']} ({$request['blood_group']}) at {$request['hospital_name']}. Contact: {$request['contact_name']} - {$request['contact_phone']}"); ?>" 
                               target="_blank" class="btn btn-sm btn-success">WhatsApp</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No available donors found for this blood group.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
