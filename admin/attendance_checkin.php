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

// Handle check-in
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = trim($_POST['student_id']);
    
    // Verify student exists
    $stmt = $pdo->prepare("SELECT id, full_name FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if(!$student) {
        $_SESSION['error'] = "Student ID not found";
        header("Location: attendance_checkin.php?id=$event_id");
        exit();
    }
    
    // Check if already checked in
    $stmt = $pdo->prepare("SELECT id FROM event_attendance WHERE event_id = ? AND student_id = ?");
    $stmt->execute([$event_id, $student['id']]);
    
    if($stmt->fetch()) {
        $_SESSION['error'] = "Student already checked in";
    } else {
        // Check if student is registered for event
        $stmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND student_id = ?");
        $stmt->execute([$event_id, $student['id']]);
        
        if(!$stmt->fetch()) {
            // Auto-register student if not registered
            $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, student_id, registered_at) VALUES (?, ?, NOW())");
            $stmt->execute([$event_id, $student['id']]);
            $_SESSION['info'] = "Student {$student['full_name']} was automatically registered for this event";
        }
        
        // Record attendance
        $stmt = $pdo->prepare("INSERT INTO event_attendance (event_id, student_id, checked_in_at) VALUES (?, ?, NOW())");
        $stmt->execute([$event_id, $student['id']]);
        $_SESSION['success'] = "Check-in successful for {$student['full_name']}!";
        
        // Record donation if not already recorded
        $stmt = $pdo->prepare("SELECT id FROM donations WHERE event_id = ? AND donor_id = ?");
        $stmt->execute([$event_id, $student['id']]);
        
        if(!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO donations (donor_id, event_name, donation_date, event_id) VALUES (?, ?, NOW(), ?)");
            $stmt->execute([$student['id'], $event['event_name'], $event_id]);
        }
    }
    
    header("Location: attendance_checkin.php?id=$event_id");
    exit();
}

// Get recent check-ins
$recent_checkins = $pdo->prepare("
    SELECT a.*, s.full_name, s.blood_group, s.student_id
    FROM event_attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.event_id = ?
    ORDER BY a.checked_in_at DESC
    LIMIT 10
");
$recent_checkins->execute([$event_id]);
$recent_checkins = $recent_checkins->fetchAll(PDO::FETCH_ASSOC);

// Get registered students for suggestions
$registered_students = $pdo->prepare("
    SELECT s.student_id, s.full_name, s.blood_group 
    FROM event_registrations r
    JOIN students s ON r.student_id = s.id
    WHERE r.event_id = ?
    ORDER BY s.full_name
");
$registered_students->execute([$event_id]);
$registered_students = $registered_students->fetchAll(PDO::FETCH_ASSOC);
$all_students = $pdo->query("SELECT student_id, full_name, blood_group FROM students ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-In Attendees - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .card{
            height: 50vh;
        }
        .card-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .search-container {
            position: relative;
        }
        
        .suggestions {
            position: absolute;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 4px 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 100;
            display: none;
        }

        .search-container {
  position: relative;
  width: 300px;
}

#suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 200px;       /* ✅ limit height */
  overflow-y: auto;        /* ✅ enable vertical scroll */
  border: 1px solid #ccc;
  background: #fff;
  z-index: 9999;
}

#suggestions div {
  padding: 8px;
  cursor: pointer;
}

#suggestions div:hover {
  background: #f0f0f0;
}

        
        .suggestion-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .suggestion-item:hover {
            background-color: #f5f5f5;
        }
        
        .suggestion-item:last-child {
            border-bottom: none;
        }
        
        .suggestion-item .blood-group-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .recent-checkins {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .checkin-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .checkin-time {
            font-size: 0.85rem;
            color: #666;
            min-width: 70px;
        }
        
        .checkin-details {
            flex-grow: 1;
            margin: 0 1rem;
        }
        
        .checkin-details h3 {
            margin: 0;
            font-size: 1rem;
        }
        
        .student-id {
            font-size: 0.8rem;
            color: #666;
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
        
        .blood-group-badge {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            color: white;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 0.75rem 1.25rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h1>Check-In Attendees</h1>
                <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
                <a href="event_registrations.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Registrations
                </a>
            </div>
            
            <?php if(isset($_SESSION['info'])): ?>
                <div class="alert-info">
                    <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
                </div>
            <?php endif; ?>
            
            <div class="card-row">
                <div class="card">
                    <div class="card-header">
                        <h2>Check-In Student</h2>
                    </div>
                    <div class="card-body">
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" id="checkinForm">
                            <div class="form-group search-container">
                                <label for="student_id">Student ID or Name</label>
                                <input type="text" id="student_id" name="student_id" required autofocus 
                                       placeholder="Start typing to search...">
                                <div class="suggestions" id="suggestions">
                                    <div id="suggestionsList"></div>
                                </div>
                                <small class="form-text">Students registered for this event will appear as suggestions</small>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> Check In
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Check-Ins (Last 10)</h2>
                    </div>
                    <div class="card-body">
                        <?php if(count($recent_checkins) > 0): ?>
                            <div class="recent-checkins">
                                <?php foreach($recent_checkins as $checkin): ?>
                                    <div class="checkin-item">
                                        <div class="checkin-time">
                                            <?php echo date('h:i A', strtotime($checkin['checked_in_at'])); ?>
                                        </div>
                                        <div class="checkin-details">
                                            <h3><?php echo htmlspecialchars($checkin['full_name']); ?></h3>
                                            <div class="student-id">ID: <?php echo htmlspecialchars($checkin['student_id']); ?></div>
                                        </div>
                                        <span class="blood-group-badge" style="background-color: <?php echo getBloodGroupColor($checkin['blood_group']); ?>">
                                            <?php echo htmlspecialchars($checkin['blood_group']); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-user-times"></i>
                                <p>No check-ins recorded yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const studentInput = document.getElementById('student_id');
        const suggestionsContainer = document.getElementById('suggestions');
        const suggestionsList = document.getElementById('suggestionsList');
        const registeredStudents = <?php echo json_encode($registered_students); ?>;
        const allStudents = <?php echo json_encode($all_students); ?>;

        // Show only registered students when clicking the input
        studentInput.addEventListener('click', function() {
            if (this.value.length === 0) {
                showRegisteredStudents();
                suggestionsContainer.style.display = 'block';
            }
        });

        // Show suggestions when typing
        studentInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            suggestionsList.innerHTML = '';

            if (searchTerm.length === 0) {
                return;
            }

            // First show registered students that match
            const filteredRegistered = registeredStudents.filter(student => 
                student.student_id.toLowerCase().includes(searchTerm) || 
                student.full_name.toLowerCase().includes(searchTerm)
            );

            // Then show other students that match
            const filteredAll = allStudents.filter(student => 
                (student.student_id.toLowerCase().includes(searchTerm) || 
                 student.full_name.toLowerCase().includes(searchTerm)) &&
                !registeredStudents.some(reg => reg.student_id === student.student_id)
            );

            if (filteredRegistered.length > 0 || filteredAll.length > 0) {
                if (filteredRegistered.length > 0) {
                    const header = document.createElement('div');
                    header.className = 'suggestion-item';
                    header.style.background = '#f8f9fa';
                    header.style.fontWeight = 'bold';
                    header.textContent = 'Registered Students';
                    suggestionsList.appendChild(header);

                    filteredRegistered.forEach(student => {
                        addSuggestionItem(student);
                    });
                }

                if (filteredAll.length > 0) {
                    const header = document.createElement('div');
                    header.className = 'suggestion-item';
                    header.style.background = '#f8f9fa';
                    header.style.fontWeight = 'bold';
                    header.textContent = 'Other Students';
                    suggestionsList.appendChild(header);

                    filteredAll.forEach(student => {
                        addSuggestionItem(student);
                    });
                }

                suggestionsContainer.style.display = 'block';
            } else {
                showNoResults();
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!studentInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });

        // Auto-submit if suggestion is selected with Enter
        studentInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && suggestionsContainer.style.display === 'block') {
                e.preventDefault();
                const firstSuggestion = suggestionsList.querySelector('.suggestion-item:not([style*="background"])');
                if (firstSuggestion) {
                    firstSuggestion.click();
                    document.getElementById('checkinForm').submit();
                }
            }
        });

        // Show only registered students
        function showRegisteredStudents() {
            suggestionsList.innerHTML = '';

            if (registeredStudents.length > 0) {
                const header = document.createElement('div');
                header.className = 'suggestion-item';
                header.style.background = '#f8f9fa';
                header.style.fontWeight = 'bold';
                header.textContent = 'Registered Students';
                suggestionsList.appendChild(header);

                registeredStudents.forEach(student => {
                    addSuggestionItem(student);
                });
            } else {
                showNoResults();
            }
        }

        // Add suggestion item to list
        function addSuggestionItem(student) {
            const suggestion = document.createElement('div');
            suggestion.className = 'suggestion-item';
            suggestion.innerHTML = `
                <div style="flex-grow: 1;">
                    <strong>${student.student_id}</strong> - ${student.full_name}
                </div>
                <span class="blood-group-badge" style="background-color: ${getBloodGroupColor(student.blood_group)}">
                    ${student.blood_group}
                </span>
            `;
            suggestion.addEventListener('click', function() {
                studentInput.value = student.student_id;
                suggestionsContainer.style.display = 'none';
                studentInput.focus();
            });
            suggestionsList.appendChild(suggestion);
        }

        // Show no results
        function showNoResults() {
            suggestionsList.innerHTML = '';
            const noResults = document.createElement('div');
            noResults.className = 'suggestion-item';
            noResults.textContent = 'No matching students found';
            suggestionsList.appendChild(noResults);
            suggestionsContainer.style.display = 'block';
        }
    });

    // Blood group badge colors
    function getBloodGroupColor(bloodGroup) {
        const colors = {
            'A+': '#FF5252',
            'A-': '#FF8A80',
            'B+': '#448AFF',
            'B-': '#82B1FF',
            'AB+': '#7C4DFF',
            'AB-': '#B388FF',
            'O+': '#FFC107',
            'O-': '#FFD740'
        };
        return colors[bloodGroup] || '#9E9E9E';
    }
</script>

</body>
</html>