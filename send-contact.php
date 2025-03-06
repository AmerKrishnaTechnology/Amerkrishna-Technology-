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
    $required_fields = ['name', 'email', 'subject', 'message'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Sanitize inputs
    $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $subject = filter_var($data['subject'], FILTER_SANITIZE_STRING);
    $message = filter_var($data['message'], FILTER_SANITIZE_STRING);

    // Email configuration
    $to = "support@amerkrishna.com";
    $headers = "From: {$email}\r\n";
    $headers .= "Reply-To: {$email}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $email_content = "
        New contact form submission:
        
        Name: {$name}
        Email: {$email}
        Subject: {$subject}
        Message: {$message}
    ";

    // Send email
    if (!mail($to, "Contact Form: {$subject}", $email_content, $headers)) {
        throw new Exception("Failed to send email");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
?>