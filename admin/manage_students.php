<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

// Pagination
$limit = 4; // Show only 4 students per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter
$search = $_GET['search'] ?? '';
$blood_group = $_GET['blood_group'] ?? '';
$department = $_GET['department'] ?? '';
$passout = isset($_GET['passout']) ? (int)$_GET['passout'] : 0;

// Build query
$query = "SELECT * FROM students WHERE 1=1";
$params = [];

if(!empty($search)) {
    $query .= " AND (full_name LIKE ? OR student_id LIKE ? OR email LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if(!empty($blood_group)) {
    $query .= " AND blood_group = ?";
    $params[] = $blood_group;
}

if(!empty($department)) {
    $query .= " AND department LIKE ?";
    $params[] = "%$department%";
}

// Filter for passout students
if ($passout) {
    $query .= " AND year_of_study > 3"; // Assuming 0 represents passout students
} else {
    $query .= " AND year_of_study < 4"; // Only current students
}

// Get total count for pagination
$count_query = str_replace('*', 'COUNT(*)', $query);
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_students = $stmt->fetchColumn();

// Add sorting and pagination
$query .= " ORDER BY full_name ASC LIMIT $limit OFFSET $offset";

// Execute main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique departments for filter
$departments = $pdo->query("SELECT DISTINCT department FROM students ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

// Function to get blood group color
// function getBloodGroupColor($bloodGroup) {
//     $colors = [
//         'A+' => '#FF5252',
//         'A-' => '#FF8A80',
//         'B+' => '#448AFF',
//         'B-' => '#82B1FF',
//         'AB+' => '#7C4DFF',
//         'AB-' => '#B388FF',
//         'O+' => '#FFC107',
//         'O-' => '#FFD740'
//     ];
    
//     return $colors[$bloodGroup] ?? '#9E9E9E';
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Blood Group Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin_login.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <style>
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-top: 15px;
        }
        
        .filter-form .form-group {
            margin-bottom: 0;
        }
        
        .filter-form input,
        .filter-form select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .passout-filter {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .passout-filter input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .students-table th,
        .students-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .students-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .blood-group-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            font-size: 12px;
            text-align: center;
            min-width: 40px;
        }
        .passout{
            color:black
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-badge.inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 8px;
            font-size: 12px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .page-link {
            padding: 8px 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            color: #007bff;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background-color: #e9ecef;
        }
        
        .page-info {
            font-weight: 500;
        }
        
        .card-header {
            display: flex;
            flex-direction: column;
        }
        
        @media (min-width: 768px) {
            .filter-form {
                flex-wrap: nowrap;
            }
            
            .card-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
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
                <h1>Manage Students</h1>
                <a href="add_student.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add Student
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2>Student Records</h2>
                    <form method="get" class="filter-form">
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <select name="blood_group">
                                <option value="">All Blood Groups</option>
                                <option value="A+" <?php echo $blood_group == 'A+' ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo $blood_group == 'A-' ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo $blood_group == 'B+' ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo $blood_group == 'B-' ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo $blood_group == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo $blood_group == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo $blood_group == 'O+' ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo $blood_group == 'O-' ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <select name="department">
                                <option value="">All Departments</option>
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department == $dept ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="passout-filter">
                            <input type="checkbox" id="passout" name="passout" value="1" <?php echo $passout ? 'checked' : ''; ?>>
                            <label for="passout"  class="passout" >Show Passout Students</label>
                        </div>
                        <button type="submit" class="btn btn-secondary">Filter</button>
                        <a href="manage_students.php" class="btn btn-outline">Reset</a>
                    </form>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Blood Group</th>
                                    <th>Department</th>
                                    <th>Year</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($students) > 0): ?>
                                    <?php foreach($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td>
                                            <span class="blood-group-badge" style="background-color: <?php echo getBloodGroupColor($student['blood_group']); ?>">
                                                <?php echo htmlspecialchars($student['blood_group']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['department']); ?></td>
                                        <td>
                                            <?php 
                                            if ($student['year_of_study'] >= 4) {
                                                echo 'Passout';
                                            } else {
                                                echo 'Year ' . htmlspecialchars($student['year_of_study']);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $student['is_available'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $student['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="process_actions.php?action=delete_student&id=<?php echo $student['id']; ?>" 
                                               class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 20px;">
                                            No students found matching your criteria.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_students > 0): ?>
                    <div class="pagination">
                        <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&blood_group=<?php echo urlencode($blood_group); ?>&department=<?php echo urlencode($department); ?>&passout=<?php echo $passout; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <span class="page-info">Page <?php echo $page; ?> of <?php echo ceil($total_students / $limit); ?></span>
                        
                        <?php if($page * $limit < $total_students): ?>
                            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&blood_group=<?php echo urlencode($blood_group); ?>&department=<?php echo urlencode($department); ?>&passout=<?php echo $passout; ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        $(document).ready(function() {
            $('.students-table').DataTable({
                paging: false,
                searching: false,
                info: false,
                ordering: true,
                responsive: true
            });
        });
    </script>
</body>
</html>