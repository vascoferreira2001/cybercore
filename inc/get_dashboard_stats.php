<?php
/**
 * Get Dashboard Stats
 * Returns real-time statistics for dashboard
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'stats' => [
        'domains' => 0,
        'services' => 0,
        'tickets' => 0,
        'invoices' => 0
    ]
];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo json_encode($response);
        exit;
    }

    $pdo = getDB();
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'] ?? 'Cliente';

    if ($userRole === 'Gestor') {
        // Manager sees all stats
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM domains");
        $response['stats']['domains'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM services");
        $response['stats']['services'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tickets WHERE status != 'Fechado'");
        $response['stats']['tickets'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM invoices WHERE status = 'Pendente'");
        $response['stats']['invoices'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    } else {
        // Client sees only their stats
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM domains WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $response['stats']['domains'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM services WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $response['stats']['services'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE user_id = :user_id AND status != 'Fechado'");
        $stmt->execute([':user_id' => $userId]);
        $response['stats']['tickets'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM invoices WHERE user_id = :user_id AND status = 'Pendente'");
        $stmt->execute([':user_id' => $userId]);
        $response['stats']['invoices'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    $response['success'] = true;

} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

echo json_encode($response);
