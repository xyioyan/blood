<?php
function sendEmail($to, $subject, $message) {
    // For production, use PHPMailer or similar library
    // This is a basic implementation using mail() function
    
    $headers = "From: Blood Group Management <noreply@bloodgroupmanagement.com>\r\n";
    $headers .= "Reply-To: noreply@bloodgroupmanagement.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // In a real application, you would use a proper mailer like PHPMailer
    // This is just for demonstration
    return mail($to, $subject, $message, $headers);
    
    // For actual implementation, consider using:
    // - PHPMailer (https://github.com/PHPMailer/PHPMailer)
    // - SwiftMailer
    // - Or a transactional email service like SendGrid, Mailgun, etc.
}
?>