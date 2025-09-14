<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

// Get all departments for target audience
$departments = $pdo->query("SELECT DISTINCT department FROM students ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Donation Event - Blood Group Management</title>
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
    min-height: 140px; /* More space for options */
    box-sizing: border-box;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
    appearance: none; /* Removes default browser styling */
    overflow-y: auto; /* Enables scrolling for many options */
}

/* On focus */
.multiselect:focus {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.4);
}



/* Scrollbar styling (for better look) */
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

.multiselect {
    width: 100%;
    min-height: 140px;
    padding: 6px;
    font-size: 14px;
    font-family: inherit;
    border: 1px solid #ccc;
    border-radius: 6px;
    background-color: #fff;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.08);
    outline: none;
    cursor: pointer;
    overflow-y: auto;
}

/* Style each option */
.multiselect option {
    padding: 8px 10px;
    margin: 2px 0;
    border-radius: 4px;
    transition: background 0.2s, color 0.2s;
}

/* Hover effect */
.multiselect option:hover {
    background-color: #f0f4ff;
}

/* Selected state */
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
                <h1>Add Donation Event</h1>
                <a href="donation_events.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Events
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
                        <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous"> -->
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="event_name">Event Name*</label>
                                <input type="text" id="event_name" name="event_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="event_date">Event Date*</label>
                                <input type="date" id="event_date" name="event_date" class="datepicker" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_time">Start Time*</label>
                                <input type="time" id="start_time" name="start_time" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_time">End Time*</label>
                                <input type="time" id="end_time" name="end_time" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">Location*</label>
                            <input type="text" id="location" name="location" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="target_donors">Target Donors*</label>
                                <input type="number" id="target_donors" name="target_donors" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="target_blood_groups">Target Blood Groups</label>
                                <select id="target_blood_groups" name="target_blood_groups[]" multiple class="multiselect">
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
                        </div>
                        
                        <div class="form-group">
                            <label for="target_departments">Target Departments</label>
                            <select id="target_departments" name="target_departments[]" multiple class="multiselect">
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Event Description</label>
                            <textarea id="description" name="description" rows="4"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Event
                            </button>
                            <button type="reset" class="btn btn-outline">
                                <i class="fas fa-undo"></i> Reset
                            </button>
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