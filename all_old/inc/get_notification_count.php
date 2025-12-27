<?php
/**
 * Get Notification Count
 * Returns unread notification count for current user
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$response = [
    'count' => 0,
    'success' => false
];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo json_encode($response);
        exit;
    }

    $pdo = getDB();
    $userId = $_SESSION['user_id'];

    // Count unread notifications
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count
        FROM notifications
        WHERE user_id = :user_id AND is_read = 0
    ");
    $stmt->execute([':user_id' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $response['count'] = (int)$result['unread_count'];
    $response['success'] = true;

} catch (Exception $e) {
    error_log("Notification count error: " . $e->getMessage());
}

echo json_encode($response);
