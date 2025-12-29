<?php
// Set headers to allow AJAX requests from the same domain
header('Content-Type: application/json');

// Path to the CSV file
$csvFile = __DIR__ . '/assets/download-counter.csv';


// Get current date and time
$date = date('Y-m-d');
$time = date('H:i:s');

// Append to CSV file
$file = fopen($csvFile, 'a');
if ($file) {
    fputcsv($file, [$date, $time]);
    fclose($file);
    echo json_encode(['success' => true, 'message' => 'Download logged']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not write to file']);
}
?>
