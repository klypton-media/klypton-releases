<?php
// Set headers for JSON response and CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (empty($data['name']) || empty($data['email']) || empty($data['category']) || empty($data['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate email format
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

// Length validation
if (strlen($data['message']) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message too long (max 5000 characters)']);
    exit();
}

if (strlen($data['name']) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name too long (max 100 characters)']);
    exit();
}

// Security validation - check for dangerous content
$dangerousPatterns = '/<script|<\/script|javascript:|on\w+\s*=|<iframe|<object|<embed/i';
if (preg_match($dangerousPatterns, $data['name']) || 
    preg_match($dangerousPatterns, $data['message']) || 
    preg_match($dangerousPatterns, $data['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid characters detected']);
    exit();
}

// Sanitize inputs
$name = htmlspecialchars(strip_tags($data['name']));
$email = htmlspecialchars(strip_tags($data['email']));
$category = htmlspecialchars(strip_tags($data['category']));
$message = htmlspecialchars(strip_tags($data['message']));

// Email configuration
$to = 'admin@klypton.com';
$subject = "KLYPTON Contact Form: $category";

// Build email body
$emailBody = "You have received a new message from the KLYPTON website contact form.\n\n";
$emailBody .= "Name: $name\n";
$emailBody .= "Email: $email\n";
$emailBody .= "Category: $category\n";
$emailBody .= "Message:\n$message\n";


// Email headers
$headers = "From: admin@klypton.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send email
$mailSent = mail($to, $subject, $emailBody, $headers);

if ($mailSent) {
    // Log the contact submission (optional)
    $logFile = __DIR__ . '/assets/contact-log.csv';
    $logData = [date('Y-m-d'), date('H:i:s'), $name, $email, $category];
    $file = fopen($logFile, 'a');
    if ($file) {
        fputcsv($file, $logData);
        fclose($file);
    }
    
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send email']);
}
?>
