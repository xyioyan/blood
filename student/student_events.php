<?php
session_start();
require_once '../includes/auth.php'; // Student authentication
require_once '../includes/config.php';

// Fetch active events (future or ongoing)
$current_date = date('Y-m-d');
$events_stmt = $pdo->prepare("
    SELECT * FROM donation_events 
    WHERE event_date >= ? 
    ORDER BY event_date ASC
");

function getBloodGroupColor($group) {
    $colors = [
        'A+' => '#e74c3c',
        'A-' => '#c0392b',
        'B+' => '#3498db',
        'B-' => '#2980b9',
        'AB+' => '#9b59b6',
        'AB-' => '#8e44ad',
        'O+' => '#27ae60',
        'O-' => '#16a085'
    ];
    return $colors[$group] ?? '#7f8c8d'; // default gray
}

$events_stmt->execute([$current_date]);
$events = $events_stmt->fetchAll(PDO::FETCH_ASSOC);

// // Get numeric student ID from database
$stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]); // If session stores STU001
$studentRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$studentRow) {
    $_SESSION['error'] = "Student not found in database";
    header("Location: ./student_events.php");
    exit();
}

$numericId = $studentRow['id'];

// // Check if already registered
// $stmt = $pdo->prepare("SELECT 1 FROM event_registrations WHERE event_id = ? AND student_id = ?");
// $stmt->execute([$event_id, $numericId]);
// $is_registered = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donation Events - Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        a:hover{
            color: white !important
        }
    </style>
</head>
<body>
    <?php include '../includes/student_header.php'; ?>

    <main class="admin-content">
        <div class="content-header">
            <h1>Upcoming Blood Donation Events</h1>
        </div>

        <?php if (!empty($events)): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Upcoming & Past Events</h2>
                    <div class="event-filter">
                        <select id="eventFilter" class="form-control">
                            <option value="all">All Events</option>
                            <option value="upcoming">Upcoming Events</option>
                            <option value="past">Past Events</option>
                        </select>
                    </div>
                </div>

                <div class="card-body">
                    <div class="events-grid">
                    <?php foreach ($events as $event): 
    $isPast = strtotime($event['event_date']) < time();
    $eventClass = $isPast ? 'past-event' : 'upcoming-event';

    // Check if student is registered for this event
    $stmt = $pdo->prepare("SELECT 1 FROM event_registrations WHERE event_id = ? AND student_id = ?");
    $stmt->execute([$event['id'], $numericId]);
    $is_registered = $stmt->fetchColumn();
?>
    <div class="event-card <?php echo $eventClass; ?>">
        <!-- Event Date Box -->
        <div class="event-date">
            <div class="event-day"><?php echo date('d', strtotime($event['event_date'])); ?></div>
            <div class="event-month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
        </div>

        <!-- Event Details -->
        <div class="event-details">
            <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
            <p class="event-time">
                <i class="fas fa-clock"></i>
                <?php echo date('h:i A', strtotime($event['start_time'])); ?> - 
                <?php echo date('h:i A', strtotime($event['end_time'])); ?>
            </p>
            <div class="event-location">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo htmlspecialchars($event['location']); ?>
            </div>
            <div class="event-stats">
                <span class="stat">
                    <i class="fas fa-user"></i> <?php echo $event['target_donors']; ?> Target
                </span>
            </div>

            <?php if (!empty($event['target_blood_groups'])): ?>
                <div class="event-blood-groups">
                    <i class="fas fa-tint"></i>
                    <?php 
                    $groups = explode(',', $event['target_blood_groups']);
                    foreach ($groups as $group): ?>
                        <span class="blood-group-badge" style="background-color: <?php echo getBloodGroupColor($group); ?>">
                            <?php echo htmlspecialchars($group); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($event['description'])): ?>
                <p class="event-description">
                    <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                </p>
            <?php endif; ?>

            <!-- Actions -->
            <div class="event-actions">
                <?php if ($is_registered): ?>
                    <a href="/blood/student/register_event.php?event_id=<?php echo $event['id']; ?>" class="btn btn-danger">
                        <i class="fas fa-times-circle"></i> Cancel Registration
                    </a>
                <?php else: ?>
                    <a href="/blood/student/register_event.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-hand-holding-heart"></i> Register Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>

                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="no-events">
                <i class="fas fa-calendar-times"></i>
                <p>No upcoming events found. Check back later!</p>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Event filtering
        document.getElementById('eventFilter').addEventListener('change', function() {
            const filter = this.value;
            const events = document.querySelectorAll('.event-card');
            
            events.forEach(event => {
                const isPast = event.classList.contains('past-event');
                if (filter === 'all' || 
                    (filter === 'upcoming' && !isPast) || 
                    (filter === 'past' && isPast)) {
                    event.style.display = 'flex';
                } else {
                    event.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>