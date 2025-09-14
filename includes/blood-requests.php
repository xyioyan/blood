<?php
// Database connection and core functions for blood requests
require_once 'config.php'; // Your existing database connection

class BloodRequest {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function createRequest($data) {
        $sql = "INSERT INTO blood_requests (patient_name, contact_name, contact_phone, blood_group, 
                units_needed, hospital_name, hospital_address, urgency, additional_info, 
                requested_by, requested_ip) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $requested_by = isset($_SESSION['student_id']) ? $_SESSION['student_id'] : null;
        $requested_ip = $_SERVER['REMOTE_ADDR'];
        
        return $stmt->execute([
            $data['patient_name'],
            $data['contact_name'],
            $data['contact_phone'],
            $data['blood_group'],
            $data['units_needed'],
            $data['hospital_name'],
            $data['hospital_address'],
            $data['urgency'],
            $data['additional_info'],
            $requested_by,
            $requested_ip
        ]);
    }
    
    public function getRequests($status = null) {
        $sql = "SELECT br.*, s.full_name as requested_by_name, a.full_name as approved_by_name 
                FROM blood_requests br 
                LEFT JOIN students s ON br.requested_by = s.id 
                LEFT JOIN admins a ON br.approved_by = a.id";
        
        if ($status) {
            $sql .= " WHERE br.status = ? ORDER BY br.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$status]);
        } else {
            $sql .= " ORDER BY br.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
     // ✅ Add this method here
     public function getRequestById($request_id) {
        $sql = "SELECT br.*, s.full_name as requested_by_name, a.full_name as approved_by_name 
                FROM blood_requests br 
                LEFT JOIN students s ON br.requested_by = s.id 
                LEFT JOIN admins a ON br.approved_by = a.id 
                WHERE br.id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$request_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updateRequestStatus($request_id, $status, $admin_id, $notes = null) {
        $sql = "UPDATE blood_requests SET status = ?, approved_by = ?, admin_notes = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $admin_id, $notes, $request_id]);
    }
    
    public function getAvailableDonors($blood_group) {
        $sql = "SELECT s.*, DATEDIFF(CURDATE(), COALESCE(s.last_donation_date, '2000-01-01')) as days_since_last_donation
                FROM students s
                WHERE s.blood_group = ? AND s.is_available = 1
                AND (s.last_donation_date IS NULL OR DATEDIFF(CURDATE(), s.last_donation_date) >= 90)
                ORDER BY s.full_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$blood_group]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>