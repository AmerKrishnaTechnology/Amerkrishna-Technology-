<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required_fields = ['name', 'email', 'phone', 'date', 'time', 'service'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Sanitize inputs
    $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
    $date = filter_var($data['date'], FILTER_SANITIZE_STRING);
    $time = filter_var($data['time'], FILTER_SANITIZE_STRING);
    $service = filter_var($data['service'], FILTER_SANITIZE_STRING);
    $message = !empty($data['message']) ? filter_var($data['message'], FILTER_SANITIZE_STRING) : 'No additional message';

    // Email to admin
    $to_admin = "support@amerkrishna.com";
    $subject_admin = "New Consultation Request";
    $message_admin = "
        New consultation request received:
        
        Name: {$name}
        Email: {$email}
        Phone: {$phone}
        Date: {$date}
        Time: {$time}
        Service: {$service}
        Message: {$message}
    ";

    $headers_admin = "From: {$email}\r\n";
    $headers_admin .= "Reply-To: {$email}\r\n";
    $headers_admin .= "X-Mailer: PHP/" . phpversion();

    // Email to client
    $to_client = $email;
    $subject_client = "Your Consultation Request - AmerKrishna Technology";
    $message_client = "
        Dear {$name},

        Thank you for scheduling a consultation with AmerKrishna Technology. This email confirms your appointment:

        Date: {$date}
        Time: {$time}
        Service: {$service}

        Our team is looking forward to discussing your project requirements. If you need to reschedule or have any questions, please contact us at support@amerkrishna.com.

        Best regards,
        AmerKrishna Technology Team
    ";

    $headers_client = "From: support@amerkrishna.com\r\n";
    $headers_client .= "Reply-To: support@amerkrishna.com\r\n";
    $headers_client .= "X-Mailer: PHP/" . phpversion();

    // Send emails
    $admin_mail_sent = mail($to_admin, $subject_admin, $message_admin, $headers_admin);
    $client_mail_sent = mail($to_client, $subject_client, $message_client, $headers_client);

    if (!$admin_mail_sent || !$client_mail_sent) {
        throw new Exception("Failed to send email");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Consultation request sent successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>