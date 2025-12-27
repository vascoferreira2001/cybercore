<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

header('Content-Type: application/json');

$out = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($out);
    exit;
}

csrf_validate();

if (empty($_SESSION['user_id'])) {
    echo json_encode($out);
    exit;
}

try {
    $pdo = getDB();
    $userId = (int)$_SESSION['user_id'];
    $reason = trim($_POST['reason'] ?? '');

    // Fetch current fiscal snapshot
    $stmt = $pdo->prepare('SELECT first_name, last_name, email, entity_type, company_name, nif FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    if (!$u) { echo json_encode($out); exit; }

    $subject = 'Pedido de alteração de dados fiscais';
    $message = "Utilizador: " . ($u['first_name'].' '.$u['last_name']) . "\n".
               "Email: " . $u['email'] . "\n".
               "Tipo de entidade: " . $u['entity_type'] . "\n".
               "Nome da empresa: " . ($u['company_name'] ?: '-') . "\n".
               "NIF: " . $u['nif'] . "\n\n".
               "Motivo: " . ($reason ?: '—');

    // Create support ticket
    $ins = $pdo->prepare('INSERT INTO tickets (user_id, subject, message, status) VALUES (?, ?, ?, ?)');
    $ins->execute([$userId, $subject, $message, 'open']);

    // Log activity
    try {
        $log = $pdo->prepare('INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)');
        $log->execute([$userId, 'fiscal_change_request', 'User requested fiscal data change']);
    } catch (Throwable $e) {}

    $out['success'] = true;
    echo json_encode($out);
} catch (Throwable $e) {
    error_log('request_fiscal_change error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode($out);
}
