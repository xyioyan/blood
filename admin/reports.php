<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Validate dates
if(!strtotime($start_date) || !strtotime($end_date)) {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
}

// Get blood group statistics
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$blood_group_stats = [];

foreach($blood_groups as $group) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE blood_group = ?");
    $stmt->execute([$group]);
    $blood_group_stats[$group] = $stmt->fetchColumn();
}

// Get donation trends
$donation_trends = [];
for($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE DATE(donation_date) = ?");
    $stmt->execute([$date]);
    $donation_trends[$date] = $stmt->fetchColumn();
}

// Get top donors
$top_donors = $pdo->prepare("
    SELECT s.full_name, s.blood_group, COUNT(d.id) as donation_count 
    FROM donations d
    JOIN students s ON d.donor_id = s.id
    WHERE d.donation_date BETWEEN ? AND ?
    GROUP BY d.donor_id
    ORDER BY donation_count DESC
    LIMIT 5
");
$top_donors->execute([$start_date, $end_date]);
$top_donors = $top_donors->fetchAll(PDO::FETCH_ASSOC);

// Get departments with most donors
$department_stats = $pdo->query("
    SELECT department, COUNT(*) as donor_count 
    FROM students 
    WHERE is_available = 1
    GROUP BY department
    ORDER BY donor_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .stat-icon{
            color: var(--light-color)
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h1>Reports & Analytics</h1>
                <div class="export-actions">
                    <a href="export_reports.php?type=pdf&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                       class="btn btn-danger" target="_blank">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="export_reports.php?type=excel&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                       class="btn btn-success" target="_blank">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                </div>
            </div>
            
            <!-- Date Range Filter -->
            <div class="card">
                <div class="card-header">
                    <h2>Filter Reports</h2>
                </div>
                <div class="card-body">
                    <form method="get" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" 
                                       value="<?php echo htmlspecialchars($start_date); ?>" class="datepicker">
                            </div>
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" 
                                       value="<?php echo htmlspecialchars($end_date); ?>" class="datepicker">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Apply Filter</button>
                                <a href="reports.php" class="btn btn-outline">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Blood Group Statistics -->
            <div class="card">
                <div class="card-header">
                    <h2>Blood Group Distribution</h2>
                    <span class="badge">Total Donors: <?php echo array_sum($blood_group_stats); ?></span>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 400px;">
                        <canvas id="bloodGroupChart"></canvas>
                    </div>
                    
                    <div class="stats-grid">
                        <?php foreach($blood_groups as $group): ?>
                            <div class="stat-card">
                                <div class="stat-icon" style="background-color: <?php echo getBloodGroupColor($group); ?>">
                                    <?php echo $group; ?>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $blood_group_stats[$group]; ?></h3>
                                    <p>Donors</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Donation Trends -->
            <div class="card-row">
                <div class="card">
                    <div class="card-header">
                        <h2>Donation Trends (Last 7 Days)</h2>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="donationTrendChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Top Departments</h2>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="departmentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Donors -->
            <div class="card">
                <div class="card-header">
                    <h2>Top Donors (<?php echo date('M j', strtotime($start_date)); ?> - <?php echo date('M j', strtotime($end_date)); ?>)</h2>
                </div>
                <div class="card-body">
                    <?php if(count($top_donors) > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Donor Name</th>
                                        <th>Blood Group</th>
                                        <th>Donations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($top_donors as $index => $donor): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($donor['full_name']); ?></td>
                                            <td>
                                                <span class="blood-group-badge" style="background-color: <?php echo getBloodGroupColor($donor['blood_group']); ?>">
                                                    <?php echo htmlspecialchars($donor['blood_group']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $donor['donation_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-info-circle"></i>
                            <p>No donation records found for the selected period</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    <script>
        // Initialize date pickers
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            maxDate: "today"
        });
        
        // Blood Group Distribution Chart
        const bgCtx = document.getElementById('bloodGroupChart').getContext('2d');
        const bgChart = new Chart(bgCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($blood_groups); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($blood_group_stats)); ?>,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#8AC249', '#EA3546'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Donation Trends Chart
        const dtCtx = document.getElementById('donationTrendChart').getContext('2d');
        const dtChart = new Chart(dtCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($donation_trends)); ?>,
                datasets: [{
                    label: 'Daily Donations',
                    data: <?php echo json_encode(array_values($donation_trends)); ?>,
                    backgroundColor: 'rgba(231, 76, 60, 0.2)',
                    borderColor: 'rgba(231, 76, 60, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Department Chart
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        const deptChart = new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($department_stats, 'department')); ?>,
                datasets: [{
                    label: 'Active Donors',
                    data: <?php echo json_encode(array_column($department_stats, 'donor_count')); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>