<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

// Get departments for dropdown
$departments = $pdo->query("SELECT DISTINCT department FROM students ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - Blood Group Management</title>
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
                <h1>Add New Student</h1>
                <a href="manage_students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Student Information</h2>
                </div>
                
                <div class="card-body">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="process_add_student.php" method="post" id="studentForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="student_id">Student ID*</label>
                                <div class="student-id-container">
                                    <select id="student_id_prefix" class="student-id-prefix" required>
                                        <option value="">Select Prefix</option>
                                        <option value="C3S37401">C3S37401</option>
                                        <option value="C3S37501">C3S37501</option>
                                    </select>
                                    <input type="text" id="student_id_suffix" class="student-id-suffix" maxlength="2" pattern="[0-9]{2}" placeholder="##" required>
                                    <input type="hidden" id="student_id" name="student_id" required>
                                </div>
                                <small class="form-text">Choose prefix and enter last 2 digits (00-99)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name">Full Name*</label>
                                <input type="text" id="full_name" name="full_name" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email*</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number*</label>
                                <input type="tel" maxlength="10" id="phone" name="phone" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="blood_group">Blood Group*</label>
                                <select id="blood_group" name="blood_group" required>
                                    <option value="">Select Blood Group</option>
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
                            
                            <div class="form-group">
                                <label for="department">Department*</label>
                                <select id="department" name="department" required>
                                    <option value="">Select Department</option>
                                    <option value="BCA">BCA (Bachelor of Computer Applications)</option>
                                    <option value="B.Sc. CS">B.Sc. CS (Bachelor of Science in Computer Science)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="year_of_study">Year of Study*</label>
                                <select id="year_of_study" name="year_of_study" required>
                                    <option value="">Select Year</option>
                                    <option value="1">First Year</option>
                                    <option value="2">Second Year</option>
                                    <option value="3">Third Year</option>
                                    <option value="4">Fourth Year</option>
                                    <option value="5">Fifth Year</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Password*</label>
                                <div class="password-input">
                                    <input type="password" id="password" name="password" required>
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
                            <input type="checkbox" name="is_available" checked>
                            <label>
                                Available for donations
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Student
                            </button>
                            <button type="reset" class="btn btn-outline">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <a href="/blood/admin/upload_student.php" class="btn btn-primary">
                                <i class="fas fa-upload"></i>
                                Upload CSV
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
            
            if(password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return;
            }
            
            // Additional validations can be added here
        });
    </script>
</body>
</html>