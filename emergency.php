<?php
include 'includes/config.php';

if(isset($_GET['blood_group'])) {
    $blood_group = $_GET['blood_group'];
    
    $stmt = $pdo->prepare("SELECT * FROM students 
                          WHERE blood_group = ? AND is_available = 1
                          ORDER BY last_donation_date ASC");
    $stmt->execute([$blood_group]);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Emergency Donor Search</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Emergency Donor Search</h1>
        
        <?php if(isset($donors)): ?>
            <h2>Available <?php echo $blood_group; ?> Donors</h2>
            
            <?php if(count($donors) > 0): ?>
                <table class="donor-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>Last Donation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($donors as $donor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($donor['full_name']); ?></td>
                            <td>
                                <a href="mailto:<?php echo $donor['email']; ?>">Email</a> | 
                                <a href="tel:<?php echo $donor['phone']; ?>">Call</a>
                            </td>
                            <td><?php echo htmlspecialchars($donor['department']); ?></td>
                            <td><?php echo $donor['year_of_study']; ?></td>
                            <td><?php echo $donor['last_donation_date'] ?: 'Never'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No available donors found for this blood group.</p>
            <?php endif; ?>
            
            <a href="emergency.php" class="back-link">Search Again</a>
            
        <?php else: ?>
            <form method="get" action="emergency.php">
                <div class="form-group">
                    <label>Select Blood Group Needed:</label>
                    <select name="blood_group" required>
                        <option value="">-- Select --</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <!-- Other options -->
                    </select>
                </div>
                <button type="submit">Find Donors</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>