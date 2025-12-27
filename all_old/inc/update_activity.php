<?php
/**
 * Update User Activity Timestamp
 * Updates last activity time for session management
 */

session_start();
header('Content-Type: application/json');

$response = [
    'success' => false,
    'timestamp' => time()
];

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
    $response['success'] = true;
}

echo json_encode($response);
