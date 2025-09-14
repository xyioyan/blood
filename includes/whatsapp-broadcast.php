<?php
require_once 'config.php';

class WhatsAppBroadcast {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function createBroadcast($request_id, $message, $admin_id, $recipient_count = 0) {
        $sql = "INSERT INTO whatsapp_broadcasts (request_id, message, sent_by, recipient_count) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$request_id, $message, $admin_id, $recipient_count]);
    }
    
    public function getBroadcasts($request_id = null) {
        $sql = "SELECT wb.*, a.full_name as admin_name, br.patient_name 
                FROM whatsapp_broadcasts wb 
                JOIN admins a ON wb.sent_by = a.id 
                JOIN blood_requests br ON wb.request_id = br.id";
        
        if ($request_id) {
            $sql .= " WHERE wb.request_id = ? ORDER BY wb.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$request_id]);
        } else {
            $sql .= " ORDER BY wb.created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function generateMessage($request) {
        $urgency_emoji = [
            'Critical' => '🔴',
            'High' => '🟠',
            'Medium' => '🟡',
            'Low' => '🟢'
        ];
        
        $message = "{$urgency_emoji[$request['urgency']]} URGENT BLOOD REQUEST {$urgency_emoji[$request['urgency']]}\n\n";
        $message .= "Patient: {$request['patient_name']}\n";
        $message .= "Blood Group: {$request['blood_group']}\n";
        $message .= "Units Needed: {$request['units_needed']}\n";
        $message .= "Hospital: {$request['hospital_name']}\n";
        $message .= "Location: {$request['hospital_address']}\n";
        $message .= "Contact: {$request['contact_name']} - {$request['contact_phone']}\n";
        $message .= "Urgency: {$request['urgency']}\n\n";
        
        if (!empty($request['additional_info'])) {
            $message .= "Additional Info: {$request['additional_info']}\n\n";
        }
        
        $message .= "Please help if you can! Share with potential donors. 🩸";
        
        return $message;
    }
}
?>