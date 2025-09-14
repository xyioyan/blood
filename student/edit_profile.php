<?php
session_start();
require_once '../includes/config.php';

// Redirect to login if not logged in
if(!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

// Get current student data
try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$student) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: profile.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Blood Group Management</title>
     <!-- Font Awesome for icons -->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
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
                        <li><a href="/blood/profile.php">My Profile</a></li>
                        <!-- <li><a href="/blood/index.php">Join WhatsApp Group</a></li> -->
                        <!-- <li><a href="../emergency.php">Emergency</a></li> -->
                        <li><a href="/blood/student/logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="container">
        <div class="edit-profile-container">
            <h2><i class="fas fa-user-edit"></i> Edit Profile</h2>
            
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
            
            <form action="process_edit_profile.php" method="post" class="edit-profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group" required>
                            <option value="">Select Blood Group</option>
                            <option value="A+" <?php echo $student['blood_group'] == 'A+' ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo $student['blood_group'] == 'A-' ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo $student['blood_group'] == 'B+' ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo $student['blood_group'] == 'B-' ? 'selected' : ''; ?>>B-</option>
                            <option value="AB+" <?php echo $student['blood_group'] == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo $student['blood_group'] == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                            <option value="O+" <?php echo $student['blood_group'] == 'O+' ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo $student['blood_group'] == 'O-' ? 'selected' : ''; ?>>O-</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($student['department']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="year_of_study">Year of Study</label>
                        <input type="number" id="year_of_study" name="year_of_study" min="1" max="5" value="<?php echo htmlspecialchars($student['year_of_study']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="current_password">Current Password (required for changes)</label>
                    <input type="password" id="current_password" name="current_password">
                    <small>Leave blank if you don't want to change password</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="profile.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Blood Group Management System</p>
        </div>
    </footer>
    
    <!-- Password validation script -->
    <script>
    document.querySelector('.edit-profile-form').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const currentPassword = document.getElementById('current_password').value;
        
        // If new password is entered but current password is empty
        if((newPassword || confirmPassword) && !currentPassword) {
            alert('Please enter your current password to make password changes');
            e.preventDefault();
            return;
        }
        
        // If new passwords don't match
        if(newPassword !== confirmPassword) {
            alert('New passwords do not match');
            e.preventDefault();
            return;
        }
        
        // If new password is too short
        if(newPassword && newPassword.length < 8) {
            alert('Password must be at least 8 characters long');
            e.preventDefault();
        }
    });
    </script>
</body>
</html>