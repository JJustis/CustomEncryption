<?php
// Explicitly set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include main script with all necessary classes
require_once 'indexk.php';

// Validate token input
$downloadToken = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (empty($downloadToken)) {
    http_response_code(400);
    die("No download token provided");
}

try {
    $downloadHandler = new CreditCardDownloadHandler();
    $downloadHandler->processFileDownload($downloadToken);
} catch (Exception $e) {
    // Log the error
    error_log("Download Error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}