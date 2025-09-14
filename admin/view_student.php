<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_students.php");
    exit();
}

$student_id = $_GET['id'];

// Fetch student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student) {
    $_SESSION['error'] = "Student not found";
    header("Location: manage_students.php");
    exit();
}

// Get departments for dropdown (for edit mode)
$departments = $pdo->query("SELECT DISTINCT department FROM students ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student - Blood Group Management</title>
    <link rel="stylesheet" href="/blood/assets/css/admin.css">
    <link rel="stylesheet" href="/blood/assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h1>Student Profile</h1>
                <a href="manage_students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2><?php echo htmlspecialchars($student['full_name']); ?></h2>
                    <div class="actions">
                        <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="student-profile">
                        <div class="profile-header">
                            <div class="profile-image">
                                <div class="initials">
                                    <?php echo getInitials($student['full_name']); ?>
                                </div>
                            </div>
                            <div class="profile-info">
                                <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
                                <div class="blood-group-badge" style="background-color: <?php echo getBloodGroupColor($student['blood_group']); ?>">
                                    <?php echo htmlspecialchars($student['blood_group']); ?>
                                </div>
                                <div class="status-badge <?php echo $student['is_available'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $student['is_available'] ? 'Available for Donation' : 'Not Available'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="profile-details">
                            <div class="detail-group">
                                <h4>Basic Information</h4>
                                <div class="detail-row">
                                    <span class="detail-label">Student ID:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($student['student_id']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($student['email']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($student['phone']); ?></span>
                                </div>
                            </div>
                            
                            <div class="detail-group">
                                <h4>Academic Information</h4>
                                <div class="detail-row">
                                    <span class="detail-label">Department:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($student['department']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Year of Study:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($student['year_of_study']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

<?php
function getInitials($name) {
    $names = explode(' ', $name);
    $initials = '';
    
    foreach($names as $n) {
        $initials .= strtoupper(substr($n, 0, 1));
    }
    
    return $initials;
}

?>