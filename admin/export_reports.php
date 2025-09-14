<?php
require_once '../includes/admin_auth.php';
require_once '../includes/config.php';

// Load TCPDF config before library
require_once __DIR__ . '/../assets/TCPDF-main/tcpdf_autoconfig.php';
require_once __DIR__ . '/../assets/TCPDF-main/tcpdf.php';



// Get export type and date range
$type = $_GET['type'] ?? 'pdf';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Validate export type
if(!in_array($type, ['pdf', 'excel'])) {
    die("Invalid export type");
}

// Validate dates
if(!strtotime($start_date) || !strtotime($end_date)) {
    die("Invalid date range");
}

// Get report data
$blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$blood_group_stats = [];

foreach($blood_groups as $group) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE blood_group = ?");
    $stmt->execute([$group]);
    $blood_group_stats[$group] = $stmt->fetchColumn();
}

// Get top donors
$top_donors = $pdo->prepare("
    SELECT s.full_name, s.blood_group, COUNT(d.id) as donation_count 
    FROM donations d
    JOIN students s ON d.donor_id = s.id
    WHERE d.donation_date BETWEEN ? AND ?
    GROUP BY d.donor_id
    ORDER BY donation_count DESC
    LIMIT 10
");
$top_donors->execute([$start_date, $end_date]);
$top_donors = $top_donors->fetchAll(PDO::FETCH_ASSOC);

// Get departments with most donors
$department_stats = $pdo->query("
    SELECT department, COUNT(*) as donor_count 
    FROM students 
    WHERE is_available = 1
    GROUP BY department
    ORDER BY donor_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Generate report based on type
if($type === 'pdf') {
    // require_once '../libs/tcpdf/tcpdf.php';
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Blood Group Management System');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Blood Donation Report');
    $pdf->SetSubject('Blood Donation Statistics');
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 16);
    
    // Title
    $pdf->Cell(0, 10, 'Blood Donation Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Date Range: ' . date('M j, Y', strtotime($start_date)) . ' - ' . date('M j, Y', strtotime($end_date)), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Blood Group Distribution
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Blood Group Distribution', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    
    foreach($blood_group_stats as $group => $count) {
        $pdf->Cell(30, 10, $group, 0, 0);
        $pdf->Cell(0, 10, $count . ' donors', 0, 1);
    }
    $pdf->Ln(10);
    
    // Top Donors
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Top Donors', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    
    if(count($top_donors) > 0) {
        foreach($top_donors as $index => $donor) {
            $pdf->Cell(10, 10, $index + 1, 0, 0);
            $pdf->Cell(60, 10, $donor['full_name'], 0, 0);
            $pdf->Cell(20, 10, $donor['blood_group'], 0, 0);
            $pdf->Cell(0, 10, $donor['donation_count'] . ' donations', 0, 1);
        }
    } else {
        $pdf->Cell(0, 10, 'No donation records found for this period', 0, 1);
    }
    $pdf->Ln(10);
    
    // Department Stats
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Top Departments by Donors', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    
    foreach($department_stats as $dept) {
        $pdf->Cell(80, 10, $dept['department'], 0, 0);
        $pdf->Cell(0, 10, $dept['donor_count'] . ' donors', 0, 1);
    }
    
    // Output PDF
    $pdf->Output('blood_donation_report.pdf', 'D');
    
} elseif($type === 'excel') {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="blood_donation_report.xls"');
    
    // Create HTML table for Excel
    echo '<table border="1">
        <tr>
            <th colspan="4" style="text-align:center;font-size:16px;background-color:#e74c3c;color:white;">
                Blood Donation Report - ' . date('M j, Y', strtotime($start_date)) . ' to ' . date('M j, Y', strtotime($end_date)) . '
            </th>
        </tr>
        <tr>
            <th colspan="4" style="background-color:#f2f2f2;">Blood Group Distribution</th>
        </tr>';
    
    foreach($blood_group_stats as $group => $count) {
        echo '<tr>
            <td>' . $group . '</td>
            <td colspan="3">' . $count . ' donors</td>
        </tr>';
    }
    
    echo '<tr>
        <th colspan="4" style="background-color:#f2f2f2;">Top Donors</th>
    </tr>
    <tr>
        <th>#</th>
        <th>Donor Name</th>
        <th>Blood Group</th>
        <th>Donations</th>
    </tr>';
    
    if(count($top_donors)) {
        foreach($top_donors as $index => $donor) {
            echo '<tr>
                <td>' . ($index + 1) . '</td>
                <td>' . htmlspecialchars($donor['full_name']) . '</td>
                <td>' . $donor['blood_group'] . '</td>
                <td>' . $donor['donation_count'] . '</td>
            </tr>';
        }
    } else {
        echo '<tr><td colspan="4">No donation records found for this period</td></tr>';
    }
    
    echo '<tr>
        <th colspan="4" style="background-color:#f2f2f2;">Top Departments by Donors</th>
    </tr>
    <tr>
        <th>Department</th>
        <th colspan="3">Active Donors</th>
    </tr>';
    
    foreach($department_stats as $dept) {
        echo '<tr>
            <td>' . htmlspecialchars($dept['department']) . '</td>
            <td colspan="3">' . $dept['donor_count'] . '</td>
        </tr>';
    }
    
    echo '</table>';
}
?>