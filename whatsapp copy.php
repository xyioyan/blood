<?php
session_start();

require_once './includes/config.php'; // Your existing authentication
require_once './includes/blood-requests.php';

// // Check if student is logged in
// if (!isset($_SESSION['student_id'])) {
//     header('Location: login.php');
//     exit;
// }

$bloodRequest = new BloodRequest($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'patient_name' => $_POST['patient_name'],
        'contact_name' => $_POST['contact_name'],
        'contact_phone' => $_POST['contact_phone'],
        'blood_group' => $_POST['blood_group'],
        'units_needed' => $_POST['units_needed'],
        'hospital_name' => $_POST['hospital_name'],
        'hospital_address' => $_POST['hospital_address'],
        'urgency' => $_POST['urgency'],
        'additional_info' => $_POST['additional_info']
    ];
    
    if ($bloodRequest->createRequest($data)) {
        $success = "Blood request submitted successfully! It will be reviewed by an administrator.";
    } else {
        $error = "There was an error submitting your request. Please try again.";
    }
}

include './includes/student_header.php';
?>

<div class="container">
    <h2>Request Blood Donation</h2>
    
    <?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="patient-name">Patient Name *</label>
            <input type="text" id="patient-name" name="patient_name" required>
        </div>
        
        <div class="form-group">
            <label for="contact-name">Your Name *</label>
            <input type="text" id="contact-name" name="contact_name" required>
        </div>
        
        <div class="form-group">
            <label for="contact-phone">Your Phone Number *</label>
            <input type="tel" id="contact-phone" name="contact_phone" required>
        </div>
        
        <div class="form-group">
            <label for="blood-group">Required Blood Group *</label>
            <select id="blood-group" name="blood_group" required>
                <option value="">Select Blood Group</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="units-needed">Units Needed *</label>
            <input type="number" id="units-needed" name="units_needed" min="1" max="10" required>
        </div>
        
        <div class="form-group">
            <label for="hospital">Hospital Name *</label>
            <input type="text" id="hospital" name="hospital_name" required>
        </div>
        
        <div class="form-group">
            <label for="hospital-address">Hospital Address *</label>
            <textarea id="hospital-address" name="hospital_address" required></textarea>
        </div>
        
        <div class="form-group">
            <label for="urgency">Urgency Level *</label>
            <select id="urgency" name="urgency" required>
                <option value="">Select Urgency</option>
                <option value="Critical">Critical (Within 2 hours)</option>
                <option value="High">High (Within 6 hours)</option>
                <option value="Medium">Medium (Within 24 hours)</option>
                <option value="Low">Low (Within 48 hours)</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="additional-info">Additional Information</label>
            <textarea id="additional-info" name="additional_info"></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">Submit Request</button>
    </form>
</div>

<?php include './includes/student_footer.php'; ?>