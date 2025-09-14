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

// Get departments for dropdown
$departments = $pdo->query("SELECT DISTINCT department FROM students ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Blood Group Management</title>
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
                <h1>Edit Student</h1>
                <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Edit Student Information</h2>
                </div>
                
                <div class="card-body">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="process_edit_student.php" method="post" id="studentForm">
                        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="student_id">Student ID*</label>
                                <div class="student-id-container">
                                    <?php 
                                    $current_id = $student['student_id'];
                                    $prefix = '';
                                    $suffix = '';
                                    if (strlen($current_id) >= 2) {
                                        $prefix = substr($current_id, 0, -2);
                                        $suffix = substr($current_id, -2);
                                    }
                                    ?>
                                    <select id="student_id_prefix" class="student-id-prefix" required>
                                        <option value="">Select Prefix</option>
                                        <option value="C3S37401" <?php echo $prefix == 'C3S37401' ? 'selected' : ''; ?>>C3S37401</option>
                                        <option value="C3S37501" <?php echo $prefix == 'C3S37501' ? 'selected' : ''; ?>>C3S37501</option>
                                    </select>
                                    <input type="text" id="student_id_suffix" class="student-id-suffix" maxlength="2" pattern="[0-9]{2}" placeholder="##" value="<?php echo htmlspecialchars($suffix); ?>" required>
                                    <input type="hidden" id="student_id" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>" required>
                                </div>
                                <small class="form-text">Choose prefix and enter last 2 digits (00-99)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name">Full Name*</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email*</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number*</label>
                                <input type="tel" maxlength="10" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="blood_group">Blood Group*</label>
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
                            
                            <div class="form-group">
                                <label for="department">Department*</label>
                                <select id="department" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="BCA" <?php echo $student['department'] == 'BCA' ? 'selected' : ''; ?>>BCA (Bachelor of Computer Applications)</option>
                                    <option value="B.Sc. CS" <?php echo $student['department'] == 'B.Sc. CS' ? 'selected' : ''; ?>>B.Sc. CS (Bachelor of Science in Computer Science)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="year_of_study">Year of Study*</label>
                                <select id="year_of_study" name="year_of_study" required>
                                    <option value="">Select Year</option>
                                    <option value="1" <?php echo $student['year_of_study'] == '1' ? 'selected' : ''; ?>>First Year</option>
                                    <option value="2" <?php echo $student['year_of_study'] == '2' ? 'selected' : ''; ?>>Second Year</option>
                                    <option value="3" <?php echo $student['year_of_study'] == '3' ? 'selected' : ''; ?>>Third Year</option>
                                    <option value="4" <?php echo $student['year_of_study'] == '4' ? 'selected' : ''; ?>>Fourth Year</option>
                                    <option value="5" <?php echo $student['year_of_study'] == '5' ? 'selected' : ''; ?>>Fifth Year</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password (Leave blank to keep current)</label>
                                <div class="password-input">
                                    <input type="password" id="password" name="password">
                                    <button type="button" class="toggle-password" aria-label="Show password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-text">Minimum 8 characters</small>
                                <div class="password-strength">
                                    <div class="strength-bar"></div>
                                    <span class="strength-text"></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="remember-me">
                            <input type="checkbox" name="is_available" id="is_available" <?php echo $student['is_available'] ? 'checked' : ''; ?>>
                            <label for="is_available">
                                Available for donations
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Student
                            </button>
                            <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <style>
        .student-id-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .student-id-prefix {
            flex: 2;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .student-id-suffix {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            text-align: center;
            font-weight: bold;
        }
        
        .student-id-preview {
            font-weight: bold;
            color: #2ecc71;
            font-size: 14px;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .student-id-container {
                flex-direction: column;
                gap: 5px;
            }
            
            .student-id-prefix,
            .student-id-suffix {
                flex: 1;
                width: 100%;
            }
        }
    </style>
    
    <script>
        // Student ID functionality
        function updateStudentId() {
            const prefix = document.getElementById('student_id_prefix').value;
            const suffix = document.getElementById('student_id_suffix').value;
            const studentIdField = document.getElementById('student_id');
            
            if (prefix && suffix && suffix.length === 2) {
                const fullId = prefix + suffix;
                studentIdField.value = fullId;
                
                // Show preview
                let preview = document.querySelector('.student-id-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.className = 'student-id-preview';
                    document.querySelector('.student-id-container').appendChild(preview);
                }
                preview.textContent = 'Student ID: ' + fullId;
            } else {
                studentIdField.value = '';
                const preview = document.querySelector('.student-id-preview');
                if (preview) preview.remove();
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateStudentId(); // Show initial preview
        });
        
        document.getElementById('student_id_prefix').addEventListener('change', updateStudentId);
        document.getElementById('student_id_suffix').addEventListener('input', function() {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            // Pad with zero if single digit
            if (this.value.length === 1) {
                this.value = '0' + this.value;
            }
            updateStudentId();
        });
        
        // Toggle password visibility
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if(passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
        
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.querySelector('.strength-bar');
            const strengthText = document.querySelector('.strength-text');
            
            // Reset
            strengthBar.style.width = '0%';
            strengthBar.style.backgroundColor = 'transparent';
            strengthText.textContent = '';
            
            if(password.length === 0) return;
            
            // Calculate strength
            let strength = 0;
            
            // Length
            if(password.length >= 8) strength += 1;
            if(password.length >= 12) strength += 1;
            
            // Complexity
            if(/[A-Z]/.test(password)) strength += 1;
            if(/[0-9]/.test(password)) strength += 1;
            if(/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Update UI
            let width = 0;
            let color = '';
            let text = '';
            
            if(strength <= 2) {
                width = 33;
                color = '#e74c3c';
                text = 'Weak';
            } else if(strength <= 4) {
                width = 66;
                color = '#f39c12';
                text = 'Moderate';
            } else {
                width = 100;
                color = '#2ecc71';
                text = 'Strong';
            }
            
            strengthBar.style.width = width + '%';
            strengthBar.style.backgroundColor = color;
            strengthBar.style.transition = 'width 0.3s, background-color 0.3s';
            strengthText.textContent = text;
            strengthText.style.color = color;
        });
        
        // Form validation
        document.getElementById('studentForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            
            if(password.length > 0 && password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return;
            }
        });
    </script>
</body>
</html>