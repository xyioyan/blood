<?php
// Add this function to your existing functions.php file

/**
 * Send SMS using Twilio API (you'll need to install Twilio SDK)
 * Alternatively, you can use any other SMS gateway
 * 
 * @param string $to Phone number to send to
 * @param string $message Message to send
 * @return bool True if sent successfully, false otherwise
 */
function sendSMS($to, $message) {
    // This is a placeholder function. In production, you would use:
    // 1. Twilio (https://www.twilio.com/docs/sms/quickstart/php)
    // 2. Nexmo (https://developer.nexmo.com/messaging/sms/overview)
    // 3. Or any other SMS gateway
    
    // Example with Twilio (uncomment and configure if you have Twilio):
    /*
    require_once 'vendor/autoload.php';
    
    $account_sid = 'YOUR_TWILIO_ACCOUNT_SID';
    $auth_token = 'YOUR_TWILIO_AUTH_TOKEN';
    $twilio_number = 'YOUR_TWILIO_PHONE_NUMBER';
    
    $client = new Twilio\Rest\Client($account_sid, $auth_token);
    
    try {
        $client->messages->create(
            $to,
            array(
                'from' => $twilio_number,
                'body' => $message
            )
        );
        return true;
    } catch (Exception $e) {
        error_log("SMS sending failed: " . $e->getMessage());
        return false;
    }
    */
    
    // For demo purposes, we'll just log the message
    error_log("SMS would be sent to $to: $message");
    return true;
}

/**
 * Generate a random numeric code for SMS verification
 * 
 * @param int $length Length of the code (default: 6)
 * @return string Generated code
 */
function generateVerificationCode($length = 6) {
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= rand(0, 9);
    }
    return $code;
}
?>