<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Path to the CSV file
$csvFile = __DIR__ . '/../assets/download-counter.csv';

// Check if file exists
if (!file_exists($csvFile)) {
    echo json_encode(['success' => false, 'count' => 0, 'message' => 'File not found']);
    exit;
}

// Count lines (excluding header)
$lineCount = 0;
$file = fopen($csvFile, 'r');
if ($file) {
    // Skip header row
    fgetcsv($file);
    
    // Count remaining rows
    while (($row = fgetcsv($file)) !== false) {
        if (!empty($row[0])) { // Only count non-empty rows
            $lineCount++;
        }
    }
    fclose($file);
    
    echo json_encode(['success' => true, 'count' => $lineCount]);
} else {
    echo json_encode(['success' => false, 'count' => 0, 'message' => 'Could not read file']);
}
?>
