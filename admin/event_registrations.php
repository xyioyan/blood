<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

$event_id = $_GET['id'] ?? 0;

// Get event details
$stmt = $pdo->prepare("SELECT * FROM donation_events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$event) {
    $_SESSION['error'] = "Event not found";
    header("Location: donation_events.php");
    exit();
}

// Get registrations
$registrationsStmt = $pdo->prepare("
    SELECT r.*, s.full_name, s.blood_group, s.department, s.phone, s.email, s.id AS student_id
    FROM event_registrations r
    JOIN students s ON r.student_id = s.id
    WHERE r.event_id = ?
    ORDER BY r.registered_at DESC
");
$registrationsStmt->execute([$event_id]);
$registrations = $registrationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get attendance
$attendanceStmt = $pdo->prepare("
    SELECT a.student_id, a.checked_in_at, s.full_name
    FROM event_attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.event_id = ?
");
$attendanceStmt->execute([$event_id]);
$attendance = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

// --- FIX: Calculate Blood Group Distribution ---
$blood_group_stats = [];
foreach ($registrations as $reg) {
    $bg = $reg['blood_group'] ?? 'Unknown';
    if (!isset($blood_group_stats[$bg])) {
        $blood_group_stats[$bg] = 0;
    }
    $blood_group_stats[$bg]++;
}

$groups = array_keys($blood_group_stats);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registrations - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include '../includes/admin_header.php'; ?>

<div class="admin-container">
    <?php include '../includes/admin_sidebar.php'; ?>
    <main class="admin-content">
        <div class="content-header">
            <h1>Registrations for <?php echo htmlspecialchars($event['event_name']); ?></h1>
            <div class="header-actions">
                <a href="donation_events.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Events
                </a>
                <a href="send_reminders.php?id=<?php echo $event_id; ?>" class="btn btn-info">
                    <i class="fas fa-envelope"></i> Send Reminders
                </a>
                <a href="attendance_checkin.php?id=<?php echo $event_id; ?>" class="btn btn-success">
                    <i class="fas fa-clipboard-check"></i> Check-In
                </a>
            </div>
        </div>

        <div class="card-row">
            <div class="card">
                <div class="card-header">
                    <h2>Registration Stats</h2>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-info">
                                <h3><?php echo count($registrations); ?></h3>
                                <p>Total Registrations</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                            <div class="stat-info">
                                <h3><?php echo count($attendance); ?></h3>
                                <p>Attended</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                            <div class="stat-info">
                                <h3>
                                    <?php echo count($registrations) ? round((count($attendance) / count($registrations)) * 100) : 0; ?>%
                                </h3>
                                <p>Attendance Rate</p>
                            </div>
                        </div>
                    </div>
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
                <h2>Registered Students</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Blood Group</th>
                                <th>Department</th>
                                <th>Contact</th>
                                <th>Registered At</th>
                                <th>Attended</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($registrations as $reg):
                            $attended = in_array($reg['student_id'], array_column($attendance, 'student_id'));
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reg['full_name']); ?></td>
                                <td>
                                    <span class="blood-group-badge" style="background-color: <?php echo getBloodGroupColor($reg['blood_group']); ?>">
                                        <?php echo htmlspecialchars($reg['blood_group']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($reg['department']); ?></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($reg['email']); ?>"><i class="fas fa-envelope"></i></a>
                                    <a href="tel:<?php echo htmlspecialchars($reg['phone']); ?>"><i class="fas fa-phone"></i></a>
                                </td>
                                <td><?php echo date('M j, Y h:i A', strtotime($reg['registered_at'])); ?></td>
                                <td>
                                    <?php if($attended): ?>
                                        <span class="badge badge-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
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
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>
</body>
</html>
