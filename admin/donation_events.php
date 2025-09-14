<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

// Get all donation events
$events = $pdo->query("
    SELECT e.*, COUNT(d.id) as donation_count 
    FROM donation_events e
    LEFT JOIN donations d ON e.id = d.event_id
    GROUP BY e.id
    ORDER BY e.event_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Donation Events - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h1>Donation Events</h1>
                <a href="add_event.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Event
                </a>
            </div>
            
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
                    <?php if(count($events) > 0): ?>
                        <div class="events-grid">
                            <?php foreach($events as $event): 
                                $isPast = strtotime($event['event_date']) < time();
                                $eventClass = $isPast ? 'past-event' : 'upcoming-event';
                            ?>
                                <div class="event-card <?php echo $eventClass; ?>">
                                    <div class="event-date">
                                        <div class="event-day"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                                        <div class="event-month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                                    </div>
                                    
                                    <div class="event-details">
                                        <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                                        <p class="event-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('h:i A', strtotime($event['start_time'])); ?> - 
                                            <?php echo date('h:i A', strtotime($event['end_time'])); ?>
                                        </p>
                                        <p class="event-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($event['location']); ?>
                                        </p>
                                        <div class="event-stats">
                                            <span class="stat">
                                                <i class="fas fa-tint"></i> <?php echo $event['donation_count']; ?> Donations
                                            </span>
                                            <span class="stat">
                                                <i class="fas fa-user"></i> <?php echo $event['target_donors']; ?> Target
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="event-actions">
                                        <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="process_event_actions.php?action=delete&id=<?php echo $event['id']; ?>" 
                                           class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this event?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-events">
                            <i class="fas fa-calendar-times"></i>
                            <p>No donation events found</p>
                            <a href="add_event.php" class="btn btn-primary">Create Your First Event</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Event filtering
        document.getElementById('eventFilter').addEventListener('change', function() {
            const filter = this.value;
            const events = document.querySelectorAll('.event-card');
            
            events.forEach(event => {
                const isPast = event.classList.contains('past-event');
                
                if(filter === 'all' || 
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