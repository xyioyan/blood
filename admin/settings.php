<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

// Only superadmin can access this page
if ($_SESSION['admin_role'] !== 'superadmin') {
    $_SESSION['error'] = "You don't have permission to access this page";
    header("Location: dashboard.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update system settings
    if (isset($_POST['update_settings'])) {
        $site_name = $_POST['site_name'];
        $admin_email = $_POST['admin_email'];
        $items_per_page = (int)$_POST['items_per_page'];
        $enable_registration = isset($_POST['enable_registration']) ? 1 : 0;
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $blood_group_colors = $_POST['blood_group_colors'];
        
        // Update settings in database
        $stmt = $pdo->prepare("UPDATE system_settings SET 
                              site_name = ?, 
                              admin_email = ?, 
                              items_per_page = ?, 
                              enable_registration = ?, 
                              maintenance_mode = ?,
                              blood_group_colors = ?
                              WHERE id = 1");
        $stmt->execute([$site_name, $admin_email, $items_per_page, $enable_registration, $maintenance_mode, json_encode($blood_group_colors)]);
        
        $_SESSION['success'] = "System settings updated successfully!";
    }
    
    // Change password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        if (!password_verify($current_password, $admin['password'])) {
            $_SESSION['error'] = "Current password is incorrect";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New passwords do not match";
        } elseif (strlen($new_password) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters";
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['admin_id']]);
            
            $_SESSION['success'] = "Password changed successfully!";
        }
    }
    
    // Backup database
    if (isset($_POST['backup_database'])) {
        $backup_file = '../backups/backup-' . date("Y-m-d-H-i-s") . '.sql';
        
        // Get all tables
        $tables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $output = '';
        foreach ($tables as $table) {
            // Table structure
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $output .= $row[1] . ";\n\n";
            
            // Table data
            $stmt = $pdo->query("SELECT * FROM `$table`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $output .= "INSERT INTO `$table` VALUES(";
                $values = [];
                foreach ($row as $value) {
                    $values[] = "'" . addslashes($value) . "'";
                }
                $output .= implode(',', $values) . ");\n";
            }
            $output .= "\n";
        }
        
        // Save to file
        if (!is_dir('../backups')) {
            mkdir('../backups', 0755, true);
        }
        
        if (file_put_contents($backup_file, $output)) {
            $_SESSION['success'] = "Database backup created successfully!";
        } else {
            $_SESSION['error'] = "Failed to create database backup";
        }
    }
    
    // Restore database
    if (isset($_POST['restore_database']) && !empty($_FILES['backup_file']['name'])) {
        $backup_file = $_FILES['backup_file']['tmp_name'];
        $file_content = file_get_contents($backup_file);
        
        try {
            $pdo->beginTransaction();
            $pdo->exec($file_content);
            $pdo->commit();
            $_SESSION['success'] = "Database restored successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Database restore failed: " . $e->getMessage();
        }
    }
    
    header("Location: settings.php");
    exit();
}

// Get current settings
$stmt = $pdo->query("SELECT * FROM system_settings WHERE id = 1");
$settings = $stmt->fetch();

// Decode blood group colors
$blood_group_colors = json_decode($settings['blood_group_colors'] ?? '{}', true);
$default_colors = [
    'A+' => '#FF5252',
    'A-' => '#FF8A80',
    'B+' => '#448AFF',
    'B-' => '#82B1FF',
    'AB+' => '#7C4DFF',
    'AB-' => '#B388FF',
    'O+' => '#FFC107',
    'O-' => '#FFD740'
];
$blood_group_colors = array_merge($default_colors, $blood_group_colors);

// Get available backups
$backups = [];
if (is_dir('../backups')) {
    $files = scandir('../backups', SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if (preg_match('/^backup-.*\.sql$/', $file)) {
            $backups[] = [
                'name' => $file,
                'size' => filesize('../backups/' . $file),
                'date' => filemtime('../backups/' . $file)
            ];
        }
    }
}

// Get all admins
$admins = $pdo->query("SELECT * FROM admins ORDER BY role, full_name")->fetchAll(PDO::FETCH_ASSOC);

// Get system statistics
$stats = [
    'students' => $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'donations' => $pdo->query("SELECT COUNT(*) FROM donations")->fetchColumn(),
    'events' => $pdo->query("SELECT COUNT(*) FROM donation_events")->fetchColumn(),
    'admins' => $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"> -->
    <style>
        .settings-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .settings-section {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .settings-section h2 {
            margin-top: 0;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .checkbox-label input {
            margin: 0;
        }
        
        .backup-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .alert {
            padding: 15px;
            margin: 0 30px 20px;
            border-radius: 5px;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        
        .backup-item:last-child {
            border-bottom: none;
        }
        
        .backup-info {
            flex-grow: 1;
        }
        
        .backup-name {
            font-weight: 500;
        }
        
        .backup-meta {
            font-size: 0.85rem;
            color: #666;
        }
        
        .backup-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .color-picker {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .color-preview {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-content">
            <div class="content-header">
                <h1>System Settings</h1>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['students']; ?></div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['donations']; ?></div>
                    <div class="stat-label">Donations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['events']; ?></div>
                    <div class="stat-label">Events</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['admins']; ?></div>
                    <div class="stat-label">Admins</div>
                </div>
            </div>
            
            <div class="settings-container">
                <div>
                    
                    <!-- Password Change -->
                    <div class="settings-section">
                        <h2><i class="fas fa-lock"></i> Change Password</h2>
                        <form method="POST">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                     <!-- System Information -->
                     <div class="settings-section">
                        <h2><i class="fas fa-info-circle"></i> System Information</h2>
                        <div class="form-group">
                            <label>PHP Version</label>
                            <div class="form-control" style="background: #f8f9fa;"><?php echo phpversion(); ?></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Database Version</label>
                            <div class="form-control" style="background: #f8f9fa;">
                                <?php 
                                $stmt = $pdo->query("SELECT VERSION()");
                                echo $stmt->fetchColumn(); 
                                ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Server Software</label>
                            <div class="form-control" style="background: #f8f9fa;"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <!-- Database Backup -->
                    <div class="settings-section">
                        <h2><i class="fas fa-database"></i> Database Backups</h2>
                        <form method="POST">
                            <p>Create a complete backup of your database:</p>
                            <button type="submit" name="backup_database" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Backup Now
                            </button>
                        </form>
                        
                        <h3 style="margin-top: 1.5rem;">Restore Database</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="backup_file">Select Backup File</label>
                                <input type="file" id="backup_file" name="backup_file" class="form-control" accept=".sql">
                            </div>
                            <button type="submit" name="restore_database" class="btn btn-warning"
                                    onclick="return confirm('WARNING: This will overwrite your current database. Continue?')">
                                <i class="fas fa-undo"></i> Restore Database
                            </button>
                        </form>
                        
                        <?php if (!empty($backups)): ?>
                            <h3 style="margin-top: 1.5rem;">Available Backups</h3>
                            <ul class="backup-list">
                                <?php foreach ($backups as $backup): ?>
                                    <li class="backup-item">
                                        <div class="backup-info">
                                            <div class="backup-name"><?php echo htmlspecialchars($backup['name']); ?></div>
                                            <div class="backup-meta">
                                                <?php echo date('Y-m-d H:i:s', $backup['date']); ?> | 
                                                <?php echo round($backup['size'] / 1024, 2); ?> KB
                                            </div>
                                        </div>
                                        <div class="backup-actions">
                                            <a href="../backups/<?php echo htmlspecialchars($backup['name']); ?>" 
                                               class="btn btn-sm btn-secondary" download>
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="delete_backup.php?file=<?php echo urlencode($backup['name']); ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Delete this backup?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="margin-top: 1rem;">No backups available yet.</p>
                        <?php endif; ?>
                    </div>
                    
                   
                </div>
            </div>
        </main>
    </div>

    <script>
        // Update color preview when color picker changes
        document.querySelectorAll('input[type="color"]').forEach(picker => {
            const preview = picker.nextElementSibling;
            picker.addEventListener('input', function() {
                preview.style.backgroundColor = this.value;
            });
        });
    </script>
</body>
</html>