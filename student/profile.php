<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/sync_last_donation.php';

// Redirect to login if not logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

try {
    // Fetch student data
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // ✅ Check if password is missing
    if (empty($student['password'])) {
        $_SESSION['student_id_temp'] = $student['student_id'];
        header("Location: create_password.php");
        exit();
    }

    // ✅ Calculate eligibility and update database if needed
    $eligible = true;
    $remaining_days = 0;

    if (!empty($student['last_donation_date'])) {
        $lastDonation = new DateTime($student['last_donation_date']);
        $today = new DateTime();
        $interval = $today->diff($lastDonation);
        $daysPassed = (int)$interval->format('%r%a');

        if ($daysPassed < 90) {
            $eligible = false;
            $remaining_days = 90 - $daysPassed;
            
            // If not eligible but marked as available in DB, update DB
            if ($student['is_available']) {
                $updateStmt = $pdo->prepare("UPDATE students SET is_available = 0 WHERE id = ?");
                $updateStmt->execute([$student['id']]);
                $student['is_available'] = 0;
            }
        } else {
            // If eligible but marked as unavailable in DB, update DB
            if (!$student['is_available']) {
                $updateStmt = $pdo->prepare("UPDATE students SET is_available = 1 WHERE id = ?");
                $updateStmt->execute([$student['id']]);
                $student['is_available'] = 1;
            }
        }
    }

    // Fetch donation history
    $donation_stmt = $pdo->prepare("SELECT * FROM donations WHERE donor_id = ? ORDER BY donation_date DESC");
    $donation_stmt->execute([$student['id']]);
    $donations = $donation_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch events registered by this student
    $registeredEventsStmt = $pdo->prepare("
        SELECT de.* 
        FROM donation_events de
        INNER JOIN event_registrations er ON de.id = er.event_id
        WHERE er.student_id = ?
        ORDER BY de.event_date DESC
    ");
    $registeredEventsStmt->execute([$student['id']]);
    $registeredEvents = $registeredEventsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>My Profile - Blood Group Management</title>
        <!-- Font Awesome for icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <link rel="stylesheet" href="/blood/assets/css/style.css">
        <link rel="stylesheet" href="/blood/assets/css/profile.css">
        <style>
            .status-available {
                background-color: #d4edda;
                color: #155724;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
            }
            
            .status-not-available {
                background-color: #f8d7da;
                color: #721c24;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
            }
            
            .status-available i, .status-not-available i {
                margin-right: 10px;
                font-size: 20px;
            }
            
            .btn:disabled {
                background-color: #6c757d;
                cursor: not-allowed;
            }
            
            .donation-info {
                display: flex;
                justify-content: space-around;
                margin: 15px 0;
            }
            
            .info-item {
                text-align: center;
            }
            
            .count {
                display: block;
                font-size: 24px;
                font-weight: bold;
            }
            
            .availability-status {
                color: <?php echo $student['is_available'] ? '#28a745' : '#dc3545'; ?>;
            }
            
            .eligibility-info {
                margin-top: 10px;
                padding: 10px;
                border-radius: 5px;
                font-size: 14px;
            }
            
            .eligibility-eligible {
                background-color: #d4edda;
                color: #155724;
            }
            
            .eligibility-not-eligible {
                background-color: #fff3cd;
                color: #856404;
            }
        </style>
    </head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>Blood Group Management</h1>
                </div>
                <nav class="nav-menu">
                    <ul>
                        <li><a href="/blood/student/profile.php" class="active">My Profile</a></li>
                        <li><a href="/blood/index.php">Join WhatsApp Group</a></li>
                        <li><a href="/blood/student/request-blood.php">Request Blood</a></li>
                        <!-- <li><a href="/blood/emergency.php">Emergency</a></li> -->
                        <li><a href="/blood/student/logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="profile-header">
            <h2>Welcome, <?php echo htmlspecialchars($student['full_name']); ?></h2>
            <div class="blood-group-badge">
                Blood Group: <span><?php echo htmlspecialchars($student['blood_group']); ?></span>
            </div>
        </div>
        
        <div class="profile-grid">
            <!-- Personal Information Card -->
            <div class="profile-card">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <div class="info-group">
                    <label>Student ID:</label>
                    <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                </div>
                <div class="info-group">
                    <label>Email:</label>
                    <p><?php echo htmlspecialchars($student['email']); ?></p>
                </div>
                <div class="info-group">
                    <label>Phone:</label>
                    <p><?php echo htmlspecialchars($student['phone']); ?></p>
                </div>
                <div class="info-group">
                    <label>Department:</label>
                    <p><?php echo htmlspecialchars($student['department']); ?></p>
                </div>
                <div class="info-group">
                    <label>Year of Study:</label>
                    <p><?php echo htmlspecialchars($student['year_of_study']); ?></p>
                </div>
                <a href="edit_profile.php" class="btn btn-edit">Edit Profile</a>
            </div>
            
            <!-- Donation Status Card -->
            <div class="profile-card">
                <h3><i class="fas fa-heartbeat"></i> Donation Status</h3>
                <div class="donation-status">
                    <?php if($eligible): ?>
                        <div class="status-available">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <p>You're eligible to donate blood</p>
                                <?php if($student['last_donation_date']): ?>
                                    <small>Last donated on <?php echo date('d M Y', strtotime($student['last_donation_date'])); ?></small>
                                <?php else: ?>
                                    <small>No donation record found</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="status-not-available">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>
                                <p>You're not eligible to donate blood yet</p>
                                <?php if($student['last_donation_date']): ?>
                                    <small>Last donated on <?php echo date('d M Y', strtotime($student['last_donation_date'])); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="donation-info">
                        <div class="info-item">
                            <span class="count"><?php echo count($donations); ?></span>
                            <span class="label">Total Donations</span>
                        </div>
                        <div class="info-item">
                            <span class="count availability-status">
                                <?php echo $student['is_available'] ? 'Yes' : 'No'; ?>
                            </span>
                            <span class="label">Currently Available</span>
                        </div>
                    </div>
                    
                    <div class="eligibility-info <?php echo $eligible ? 'eligibility-eligible' : 'eligibility-not-eligible'; ?>">
                        <i class="fas fa-info-circle"></i>
                        <?php if($eligible): ?>
                            You can donate blood. The 90-day waiting period has passed.
                        <?php else: ?>
                            You can donate again after <?php echo $remaining_days; ?> days (90-day waiting period).
                        <?php endif; ?>
                    </div>
                </div>

                <button class="btn btn-primary toggle-availability"
                    data-available="<?php echo $student['is_available'] ? '1' : '0'; ?>"
                    <?php echo !$eligible ? 'disabled' : ''; ?>>
                    <?php echo $student['is_available'] ? 'Mark as Unavailable' : 'Mark as Available'; ?>
                </button>

                <?php if(!$eligible): ?>
                    <p style="color:#dc3545; margin-top:10px; font-size: 14px;">
                        <i class="fas fa-lock"></i> You can only change availability after the 90-day waiting period.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Donation History Section -->
        <div class="donation-history">
            <h3><i class="fas fa-history"></i> Donation History</h3>
            
            <?php if(count($donations) > 0): ?>
                <div class="table-responsive">
                    <table class="donation-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Event</th>
                                <th>Recipient Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($donations as $donation): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($donation['donation_date'])); ?></td>
                                <td><?php echo htmlspecialchars($donation['event_name'] ?? 'Regular Donation'); ?></td>
                                <td>
                                    <?php 
                                    $eventName = !empty($donation['event_name']) ? htmlspecialchars($donation['event_name']) : '';
                                    $recipient = !empty($donation['recipient_details']) ? htmlspecialchars($donation['recipient_details']) : '';
                                    
                                    if ($eventName && $recipient) {
                                        echo $eventName . " - " . $recipient;
                                    } elseif ($eventName) {
                                        echo $eventName;
                                    } elseif ($recipient) {
                                        echo $recipient;
                                    } else {
                                        echo "N/A";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="no-donations">
                        <?php if(count($donations) > 0): ?>
                        <a href="/blood/student/student_events.php" class="btn btn-primary" style='color:white'>Find Donation Events</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-donations">
                    <p>You haven't donated blood yet.</p>
                    <a href="/blood/student/student_events.php" class="btn btn-primary" style='color:white'>Find Donation Events</a>
                </div>
            <?php endif; ?>
        </div>
            
        <!-- Registered Events Card -->
        <div class="profile-card">
            <h3><i class="fas fa-calendar-check"></i> Registered Events</h3>

            <?php if (count($registeredEvents) > 0): ?>
                <ul class="registered-events-list" style="padding-left: 0; list-style: none;">
                    <?php foreach ($registeredEvents as $event): ?>
                        <li style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?php echo htmlspecialchars($event['event_name']); ?></strong><br>
                                <small>
                                    <?php echo date('d M Y', strtotime($event['event_date'])); ?> at <?php echo htmlspecialchars($event['location']); ?>
                                </small>
                            </div>
                            <a href="/blood/student/register_event.php?event_id=<?php echo $event['id']; ?>" class="btn btn-danger btn-sm" style="white-space: nowrap;">
                                Cancel
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>You have not registered for any events yet.</p>
                <a href="/blood/student/student_events.php" class="btn btn-primary">Find Events</a>
            <?php endif; ?>
        </div>

    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Blood Group Management System</p>
        </div>
    </footer>
    
    <!-- JavaScript for toggle availability -->
    <script>
    document.querySelector('.toggle-availability').addEventListener('click', function() {
        const button = this;
        if (button.disabled) {
            alert('You cannot change availability until 90 days have passed since your last donation.');
            return;
        }
        
        const isAvailable = button.dataset.available === '1' ? 0 : 1;
        
        fetch('toggle_availability.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'available=' + isAvailable
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                button.dataset.available = isAvailable;
                button.textContent = isAvailable ? 'Mark as Unavailable' : 'Mark as Available';
                
                const availableDisplay = document.querySelector('.availability-status');
                availableDisplay.textContent = isAvailable ? 'Yes' : 'No';
                availableDisplay.style.color = isAvailable ? '#28a745' : '#dc3545';
                
                alert('Availability status updated successfully!');
                
                // Reload the page to reflect changes
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                alert('Error updating availability: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating availability');
        });
    });
    </script>
</body>
</html>