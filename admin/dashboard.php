<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

// Get statistics
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$active_donors = $pdo->query("SELECT COUNT(*) FROM students WHERE is_available = 1")->fetchColumn();
$recent_donations = $pdo->query("SELECT COUNT(*) FROM donations WHERE donation_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <h1>Dashboard Overview</h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Students</h3>
                        <p><?php echo $total_students; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active Donors</h3>
                        <p><?php echo $active_donors; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tint"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Recent Donations</h3>
                        <p><?php echo $recent_donations; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="chart-row">
                <div class="chart-container">
                    <h2>Blood Group Distribution</h2>
                    <canvas id="bloodGroupChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h2>Recent Donations</h2>
                    <canvas id="donationChart"></canvas>
                </div>
            </div>
            
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="add_student.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add Student
                    </a>
                    <a href="donation_events.php" class="btn btn-secondary">
                        <i class="fas fa-calendar-alt"></i> Manage Events
                    </a>
                    <a href="reports.php" class="btn btn-success">
                        <i class="fas fa-file-alt"></i> Generate Report
                    </a>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

    <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script> -->
    <script>
        // Blood Group Distribution Chart
        const bgCtx = document.getElementById('bloodGroupChart').getContext('2d');
        const bgChart = new Chart(bgCtx, {
            type: 'pie',
            data: {
                labels: ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
                datasets: [{
                    data: [
                        <?php 
                        $groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                        foreach($groups as $group) {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE blood_group = ?");
                            $stmt->execute([$group]);
                            echo $stmt->fetchColumn() . ',';
                        }
                        ?>
                    ],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#8AC249', '#EA3546'
                    ]
                }]
            }
        });
        
        // Recent Donations Chart
        const donCtx = document.getElementById('donationChart').getContext('2d');
        const donChart = new Chart(donCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Donations This Week',
                    data: [
                        <?php
                        for($i = 6; $i >= 0; $i--) {
                            $day = date('Y-m-d', strtotime("-$i days"));
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE DATE(donation_date) = ?");
                            $stmt->execute([$day]);
                            echo $stmt->fetchColumn() . ',';
                        }
                        ?>
                    ],
                    backgroundColor: '#e74c3c'
                }]
            }
        });
    </script>
</body>
</html>