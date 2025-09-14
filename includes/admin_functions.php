<?php
function getBloodGroupColor($group) {
    $colors = [
        'A+' => '#FF6384',
        'A-' => '#36A2EB',
        'B+' => '#FFCE56',
        'B-' => '#4BC0C0',
        'AB+' => '#9966FF',
        'AB-' => '#FF9F40',
        'O+' => '#8AC249',
        'O-' => '#EA3546'
    ];
    
    return $colors[$group] ?? '#777';
}

function getDashboardStats($pdo) {
    $stats = [];
    
    // Total students
    $stats['total_students'] = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    
    // Active donors
    $stats['active_donors'] = $pdo->query("SELECT COUNT(*) FROM students WHERE is_available = 1")->fetchColumn();
    
    // Recent donations
    $stats['recent_donations'] = $pdo->query("SELECT COUNT(*) FROM donations WHERE donation_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    
    // Blood group distribution
    $groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    $stats['blood_groups'] = [];
    
    foreach($groups as $group) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE blood_group = ?");
        $stmt->execute([$group]);
        $stats['blood_groups'][$group] = $stmt->fetchColumn();
    }
    
    return $stats;
}
?>