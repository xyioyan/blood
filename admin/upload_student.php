<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

$message = '';

if (isset($_POST['upload'])) {
    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $row = 0;

        // Skip header row
        fgetcsv($file);

        $stmt = $pdo->prepare("
            INSERT INTO students (student_id, full_name, email, phone, blood_group, department, year_of_study, is_available)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            $row++;
            $student_id    = trim($data[0]);
            $full_name     = trim($data[1]);
            $email         = trim($data[2]);
            $phone         = trim($data[3]);
            $blood_group   = trim($data[4]);
            $department    = trim($data[5]);
            $year_of_study = trim($data[6]);
            $is_available  = strtolower(trim($data[7])) === 'available' ? 1 : 0;

            try {
                $stmt->execute([
                    $student_id, $full_name, $email, $phone,
                    $blood_group, $department, $year_of_study, $is_available
                ]);
            } catch (PDOException $e) {
                $message .= "Error on row {$row}: " . $e->getMessage() . "<br>";
            }
        }
        fclose($file);

        if (!$message) {
            $message = "✅ CSV upload successful!";
        }
    } else {
        $message = "❌ Please choose a CSV file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Students - Blood Group Management</title>
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
                <h1>Bulk Upload Students</h1>
                <a href="/blood/admin/manage_students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>Upload CSV File</h2>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <i class="fas <?php echo strpos($message, '✅') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="csv_file">Select CSV File*</label>
                            <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="upload" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                            <a href="/blood/admin/add_student.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>

                    <div class="form-info">
                        <p><strong>CSV Format:</strong></p>
                        <pre>
student_id,full_name,email,phone,blood_group,department,year_of_study,is_available
STU001,John Doe,john@example.com,9876543210,A+,Computer Science,2,Available
STU002,Jane Smith,jane@example.com,9876543211,B-,Mechanical,3,Unavailable
                        </pre>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
