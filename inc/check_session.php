<?php
/**
 * Check Session Validity
 * Returns JSON response with session status
 */

session_start();
header('Content-Type: application/json');

$response = [
    'valid' => false,
    'timestamp' => time()
];

// Check if user is logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Check session timeout (30 minutes)
    $timeout = 1800; // 30 minutes in seconds
    
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        
        if ($elapsed < $timeout) {
            $response['valid'] = true;
            $_SESSION['last_activity'] = time();
        } else {
            // Session expired
            session_unset();
            session_destroy();
        }
    } else {
        // First time checking, set activity
        $_SESSION['last_activity'] = time();
        $response['valid'] = true;
    }
}

echo json_encode($response);
