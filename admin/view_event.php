<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

if(!isset($_GET['id'])) {
    header("Location: donation_events.php");
    exit();
}

$event_id = (int)$_GET['id'];

// Get event details
$stmt = $pdo->prepare("
    SELECT e.*, COUNT(d.id) as donation_count 
    FROM donation_events e
    LEFT JOIN donations d ON e.id = d.event_id
    WHERE e.id = ?
    GROUP BY e.id
");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$event) {
    $_SESSION['error'] = "Event not found";
    header("Location: donation_events.php");
    exit();
}

// Get donors for this event
$donors = $pdo->prepare("
    SELECT s.full_name, s.blood_group, s.department, d.donation_date
    FROM donations d
    JOIN students s ON d.donor_id = s.id
    WHERE d.event_id = ?
    ORDER BY d.donation_date DESC
");
$donors->execute([$event_id]);
$donors = $donors->fetchAll(PDO::FETCH_ASSOC);

// Get blood group stats for this event
$blood_group_stats = [];
$groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

foreach($groups as $group) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM donations d
        JOIN students s ON d.donor_id = s.id
        WHERE d.event_id = ? AND s.blood_group = ?
    ");
    $stmt->execute([$event_id, $group]);
    $blood_group_stats[$group] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['event_name']); ?> - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --blood-a-plus: #FF5252;
            --blood-a-minus: #FF8A80;
            --blood-b-plus: #448AFF;
            --blood-b-minus: #82B1FF;
            --blood-ab-plus: #7C4DFF;
            --blood-ab-minus: #B388FF;
            --blood-o-plus: #FFC107;
            --blood-o-minus: #FFD740;
        }
        
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .event-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .event-status.upcoming {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .event-status.past {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }
        
        .card-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .event-detail {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .event-detail:last-child {
            border-bottom: none;
        }
        
        .event-detail i {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-top: 0.25rem;
        }
        
        .event-detail h3 {
            margin: 0 0 0.25rem 0;
            font-size: 1rem;
            color: #555;
        }
        
        .event-detail p {
            margin: 0;
            color: #333;
        }
        
        .progress-container {
            margin-top: 0.5rem;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
            font-size: 0.85rem;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress {
            height: 100%;
            border-radius: 4px;
            transition: width 0.6s ease;
        }
        
        .progress-success {
            background-color: #28a745;
        }
        
        .progress-warning {
            background-color: #ffc107;
        }
        
        .progress-danger {
            background-color: #dc3545;
        }
        
        .blood-groups {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .blood-group-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            color: white;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.25rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .stat-card .value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .stat-card .label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .no-data {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .no-data p {
            margin: 0;
            font-size: 1rem;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            color: #495057;
        }
        
        .data-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.9rem;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .register-btn{
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <div class="event-header">
                    <h1><?php echo htmlspecialchars($event['event_name']); ?></h1>
                    <span class="event-status <?php echo strtotime($event['event_date']) < time() ? 'past' : 'upcoming'; ?>">
                        <i class="fas fa-<?php echo strtotime($event['event_date']) < time() ? 'check-circle' : 'calendar-alt'; ?>"></i>
                        <?php echo strtotime($event['event_date']) < time() ? 'Completed' : 'Upcoming'; ?>
                    </span>
                </div>
                <div class="event-actions">
                    <a href="donation_events.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Events
                    </a>
                    <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit Event
                    </a>
                </div>
            </div>
            
            <div class="card-row">
                <div class="card">
                    <div class="card-header">
                        <h2>Event Details</h2>
                    </div>
                    <div class="card-body">
                        <div class="event-detail">
                            <i class="fas fa-calendar-day"></i>
                            <div>
                                <h3>Date</h3>
                                <p><?php echo date('F j, Y', strtotime($event['event_date'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="event-detail">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h3>Time</h3>
                                <p><?php echo date('h:i A', strtotime($event['start_time'])); ?> - 
                                   <?php echo date('h:i A', strtotime($event['end_time'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="event-detail">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h3>Location</h3>
                                <p><?php echo htmlspecialchars($event['location']); ?></p>
                            </div>
                        </div>
                        
                        <div class="event-detail">
                            <i class="fas fa-bullseye"></i>
                            <div>
                                <h3>Target</h3>
                                <p><?php echo $event['donation_count']; ?> / <?php echo $event['target_donors']; ?> donors</p>
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo min(100, ($event['donation_count'] / $event['target_donors']) * 100); ?>%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if(!empty($event['target_blood_groups'])): ?>
                        <div class="event-detail">
                            <i class="fas fa-tint"></i>
                            <div>
                                <h3>Target Blood Groups</h3>
                                <div class="blood-groups">
                                    <?php 
                                    $targetGroups = explode(',', $event['target_blood_groups']);
                                    foreach($targetGroups as $group): 
                                    ?>
                                        <span class="blood-group-badge" style="background-color: <?php echo getBloodGroupColor($group); ?>">
                                            <?php echo $group; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($event['description'])): ?>
                        <div class="event-detail">
                            <i class="fas fa-align-left"></i>
                            <div>
                                <h3>Description</h3>
                                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                                <a style=" margin-top:5px; " href="/blood/admin/event_registrations.php?id=<?php echo $event['id']; ?>"  class="btn btn-primary">
                                    <i class="fas fa-hand-holding-heart" style="color: white;" ></i> Registered Students
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Blood Group Distribution</h2>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="bloodGroupChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Donors (<?php echo count($donors); ?>)</h2>
                </div>
                <div class="card-body">
                    <?php if(count($donors) > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Donor Name</th>
                                        <th>Blood Group</th>
                                        <th>Department</th>
                                        <th>Donation Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($donors as $donor): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($donor['full_name']); ?></td>
                                            <td>
                                                <span class="blood-group-badge" style="background-color: <?php echo getBloodGroupColor($donor['blood_group']); ?>">
                                                    <?php echo htmlspecialchars($donor['blood_group']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($donor['department']); ?></td>
                                            <td><?php echo date('h:i A', strtotime($donor['donation_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-user-times"></i>
                            <p>No donors have participated in this event yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Blood Group Distribution Chart
        const ctx = document.getElementById('bloodGroupChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($groups); ?>,
                datasets: [{
                    label: 'Donations by Blood Group',
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
    </script>
    <!-- REMOVE this: -->

<!-- KEEP this: -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

</body>
</html>