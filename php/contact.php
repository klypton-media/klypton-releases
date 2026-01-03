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

// =====================================================
// PROTON MAIL SMTP CONFIGURATION
// =====================================================
// Generate an SMTP token in Proton Mail:
// Settings > All settings > IMAP/SMTP > Generate token
// =====================================================
$smtpHost = 'smtp.protonmail.ch';
$smtpPort = 587;
$smtpUsername = 'admin@klypton.com';
$smtpPassword = 'RTBXJ1ZPFBUF1364'; // SMTP token

$to = 'admin@klypton.com';
$subject = "KLYPTON Contact Form: $category";

// Build email body
$emailBody = "You have received a new message from the KLYPTON website contact form.\r\n\r\n";
$emailBody .= "Name: $name\r\n";
$emailBody .= "Email: $email\r\n";
$emailBody .= "Category: $category\r\n";
$emailBody .= "Message:\r\n$message\r\n";

// Send email using SMTP (port 587 with STARTTLS)
$mailSent = false;
$errorMessage = '';

try {
    // Connect to SMTP server on port 587
    $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);
    if (!$socket) {
        throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
    }
    
    // Read greeting
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '220') {
        throw new Exception("SMTP error: $response");
    }
    
    // Send EHLO
    fwrite($socket, "EHLO klypton.com\r\n");
    while ($line = fgets($socket, 512)) {
        if (substr($line, 3, 1) == ' ') break;
    }
    
    // Start TLS
    fwrite($socket, "STARTTLS\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '220') {
        throw new Exception("STARTTLS failed: $response");
    }
    
    // Enable crypto
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
    // Send EHLO again after STARTTLS
    fwrite($socket, "EHLO klypton.com\r\n");
    while ($line = fgets($socket, 512)) {
        if (substr($line, 3, 1) == ' ') break;
    }
    
    // Authenticate
    fwrite($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '334') {
        throw new Exception("AUTH failed: $response");
    }
    
    fwrite($socket, base64_encode($smtpUsername) . "\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '334') {
        throw new Exception("Username rejected: $response");
    }
    
    fwrite($socket, base64_encode($smtpPassword) . "\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '235') {
        throw new Exception("Authentication failed: $response");
    }
    
    // Send MAIL FROM
    fwrite($socket, "MAIL FROM:<$smtpUsername>\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '250') {
        throw new Exception("MAIL FROM rejected: $response");
    }
    
    // Send RCPT TO
    fwrite($socket, "RCPT TO:<$to>\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '250') {
        throw new Exception("RCPT TO rejected: $response");
    }
    
    // Send DATA
    fwrite($socket, "DATA\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '354') {
        throw new Exception("DATA rejected: $response");
    }
    
    // Build and send email headers and body
    $headers = "From: $smtpUsername\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "\r\n";
    
    fwrite($socket, $headers . $emailBody . "\r\n.\r\n");
    $response = fgets($socket, 512);
    if (substr($response, 0, 3) != '250') {
        throw new Exception("Message rejected: $response");
    }
    
    // Quit
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    
    $mailSent = true;
    
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    if (isset($socket) && $socket) {
        fclose($socket);
    }
}

if ($mailSent) {
    // Log the contact submission
    $logFile = __DIR__ . '/../assets/contact-log.csv';
    $logData = [date('Y-m-d'), date('H:i:s'), $name, $email, $category];
    $file = fopen($logFile, 'a');
    if ($file) {
        fputcsv($file, $logData);
        fclose($file);
    }
    
    echo json_encode(['success' => true, 'message' => 'Email sent successfully']);
} else {
    http_response_code(500);
    // Log the error for debugging (don't expose to user)
    error_log("SMTP Error: $errorMessage");
    echo json_encode(['success' => false, 'message' => 'Failed to send email']);
}
?>
