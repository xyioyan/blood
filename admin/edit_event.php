<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: donation_events.php");
    exit();
}

$event_id = $_GET['id'];

// Fetch event details
$stmt = $pdo->prepare("SELECT * FROM donation_events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$event) {
    $_SESSION['error'] = "Event not found";
    header("Location: donation_events.php");
    exit();
}

// Get target blood groups and departments
$target_blood_groups = json_decode($event['target_blood_groups'], true) ?: [];
$target_departments = json_decode($event['target_departments'], true) ?: [];

// Get all departments for dropdown
$departments = $pdo->query("SELECT DISTINCT department FROM students ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Multi-select dropdown styling */
        .multiselect {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            background-color: #fff;
            font-size: 14px;
            font-family: inherit;
            min-height: 140px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            appearance: none;
            overflow-y: auto;
        }

        .multiselect:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.4);
        }

        .multiselect::-webkit-scrollbar {
            width: 6px;
        }
        
        .multiselect::-webkit-scrollbar-thumb {
            background-color: #007bff;
            border-radius: 3px;
        }
        
        .multiselect::-webkit-scrollbar-track {
            background-color: #f1f1f1;
        }

        .multiselect option {
            padding: 8px 10px;
            margin: 2px 0;
            border-radius: 4px;
            transition: background 0.2s, color 0.2s;
        }

        .multiselect option:hover {
            background-color: #f0f4ff;
        }

        .multiselect option:checked {
            background-color: var(--warning-color);
            color: #fff;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h1>Edit Donation Event</h1>
                <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Event
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Event Details</h2>
                </div>
                
                <div class="card-body">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="process_event_actions.php" method="post" id="eventForm">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="event_name">Event Name*</label>
                                <input type="text" id="event_name" name="event_name" value="<?php echo htmlspecialchars($event['event_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="event_date">Event Date*</label>
                                <input type="date" id="event_date" name="event_date" class="datepicker" 
                                       value="<?php echo htmlspecialchars($event['event_date']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_time">Start Time*</label>
                                <input type="time" id="start_time" name="start_time" 
                                       value="<?php echo htmlspecialchars($event['start_time']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_time">End Time*</label>
                                <input type="time" id="end_time" name="end_time" 
                                       value="<?php echo htmlspecialchars($event['end_time']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location*</label>
                            <input type="text" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($event['location']); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="target_donors">Target Donors*</label>
                                <input type="number" id="target_donors" name="target_donors" min="1" 
                                       value="<?php echo htmlspecialchars($event['target_donors']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="target_blood_groups">Target Blood Groups</label>
                                <select id="target_blood_groups" name="target_blood_groups[]" multiple class="multiselect">
                                    <option value="A+" <?php echo in_array('A+', $target_blood_groups) ? 'selected' : ''; ?>>A+</option>
                                    <option value="A-" <?php echo in_array('A-', $target_blood_groups) ? 'selected' : ''; ?>>A-</option>
                                    <option value="B+" <?php echo in_array('B+', $target_blood_groups) ? 'selected' : ''; ?>>B+</option>
                                    <option value="B-" <?php echo in_array('B-', $target_blood_groups) ? 'selected' : ''; ?>>B-</option>
                                    <option value="AB+" <?php echo in_array('AB+', $target_blood_groups) ? 'selected' : ''; ?>>AB+</option>
                                    <option value="AB-" <?php echo in_array('AB-', $target_blood_groups) ? 'selected' : ''; ?>>AB-</option>
                                    <option value="O+" <?php echo in_array('O+', $target_blood_groups) ? 'selected' : ''; ?>>O+</option>
                                    <option value="O-" <?php echo in_array('O-', $target_blood_groups) ? 'selected' : ''; ?>>O-</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="target_departments">Target Departments</label>
                            <select id="target_departments" name="target_departments[]" multiple class="multiselect">
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" 
                                        <?php echo in_array($dept, $target_departments) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Event Description</label>
                            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($event['description']); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Event
                            </button>
                            <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date picker
        flatpickr(".datepicker", {
            minDate: "today",
            dateFormat: "Y-m-d"
        });
        
        // Time validation
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if(startTime >= endTime) {
                e.preventDefault();
                alert('End time must be after start time');
                return false;
            }
            
            return true;
        });
    </script>
    <script>
        document.querySelectorAll('.multiselect').forEach(select => {
            select.addEventListener('mousedown', function (e) {
                e.preventDefault();

                const option = e.target;
                option.selected = !option.selected;

                // Trigger change event if needed
                option.parentNode.dispatchEvent(new Event('change'));
            });
        });
    </script>
</body>
</html>