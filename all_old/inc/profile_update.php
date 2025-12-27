<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

header('Content-Type: application/json');

$out = ['success' => false, 'errors' => []];

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($out);
    exit;
}

// CSRF validation (expects form-urlencoded)
csrf_validate();

if (empty($_SESSION['user_id'])) {
    echo json_encode($out);
    exit;
}

// Helpers
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPostalCodePT($pc) {
    return preg_match('/^\d{4}-\d{3}$/', $pc) === 1;
}

try {
    $userId = (int)$_SESSION['user_id'];
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postalCode = trim($_POST['postalCode'] ?? '');
    $country = trim($_POST['country'] ?? '');

    // Validation
    if (strlen($fullName) < 3) { $out['errors']['fullName'] = 'Nome demasiado curto.'; }
    if (!isValidEmail($email)) { $out['errors']['email'] = 'Email inválido.'; }
    if (!isValidPostalCodePT($postalCode)) { $out['errors']['postalCode'] = 'Formato inválido (NNNN-NNN).'; }
    if (!empty($address) && strlen($address) < 6) { $out['errors']['address'] = 'Morada demasiado curta.'; }
    if (!empty($city) && strlen($city) < 2) { $out['errors']['city'] = 'Cidade inválida.'; }

    if (!empty($out['errors'])) { echo json_encode($out); exit; }

    $pdo = getDB();

    // Check email uniqueness (exclude current user)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
    $stmt->execute([$email, $userId]);
    if ((int)$stmt->fetchColumn() > 0) {
        $out['errors']['email'] = 'Este email já está em uso.';
        echo json_encode($out);
        exit;
    }

    // Split full name into first_name and last_name (simple strategy)
    $parts = preg_split('/\s+/', $fullName);
    $first = $parts[0] ?? '';
    $last = '';
    if (count($parts) > 1) {
        array_shift($parts);
        $last = trim(implode(' ', $parts));
    }

    // Update allowed fields only
    $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, postal_code = ?, country = ? WHERE id = ?');
    $stmt->execute([$first, $last, $email, $phone, $address, $city, $postalCode, $country, $userId]);

    // Log activity
    try {
        $log = $pdo->prepare('INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)');
        $log->execute([$userId, 'profile_update', 'User updated personal profile information']);
    } catch (Throwable $e) {}

    $out['success'] = true;
    echo json_encode($out);
} catch (Throwable $e) {
    error_log('profile_update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode($out);
}
