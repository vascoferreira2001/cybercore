<?php
/**
 * Fiscal Data Update Handler
 * Only Manager and Financial Support roles can update fiscal data directly
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

header('Content-Type: application/json');

$out = ['success' => false, 'errors' => []];

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode($out);
    exit;
}

// CSRF validation
csrf_validate();

// Authentication check
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode($out);
    exit;
}

// Get current user
$currentUser = currentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode($out);
    exit;
}

// Permission check: Only Manager and Financial Support can edit fiscal data
$allowedRoles = ['Gestor', 'Suporte Financeiro'];
if (!in_array($currentUser['role'], $allowedRoles)) {
    http_response_code(403);
    $out['errors']['permission'] = 'Apenas Gestor e Suporte Financeiro podem editar dados fiscais.';
    echo json_encode($out);
    exit;
}

try {
    $userId = (int)($_POST['userId'] ?? $_SESSION['user_id']);
    $entityType = trim($_POST['entityType'] ?? '');
    $companyName = trim($_POST['companyName'] ?? '');
    $nif = trim($_POST['taxId'] ?? '');

    // Validation
    if (!in_array($entityType, ['Singular', 'Coletiva'])) {
        $out['errors']['entityType'] = 'Tipo de entidade inválido.';
    }

    if (strlen($nif) !== 9 || !ctype_digit($nif)) {
        $out['errors']['taxId'] = 'NIF deve ter 9 dígitos.';
    }

    if ($entityType === 'Coletiva' && strlen($companyName) < 3) {
        $out['errors']['companyName'] = 'Nome da empresa é obrigatório para Pessoa Coletiva.';
    }

    if (!empty($out['errors'])) {
        http_response_code(400);
        echo json_encode($out);
        exit;
    }

    $pdo = getDB();

    // Check if NIF is already in use by another user
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE nif = ? AND id != ?');
    $stmt->execute([$nif, $userId]);
    if ((int)$stmt->fetchColumn() > 0) {
        $out['errors']['taxId'] = 'Este NIF já está em uso por outro utilizador.';
        http_response_code(400);
        echo json_encode($out);
        exit;
    }

    // Update fiscal data
    $stmt = $pdo->prepare('UPDATE users SET entity_type = ?, company_name = ?, nif = ? WHERE id = ?');
    $stmt->execute([$entityType, $companyName, $nif, $userId]);

    // Log activity
    try {
        $log = $pdo->prepare('INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)');
        $log->execute([
            $currentUser['id'], 
            'fiscal_data_update', 
            'Manager/Financial Support updated fiscal data for user ID: ' . $userId
        ]);
    } catch (Throwable $e) {
        error_log('Failed to log fiscal update: ' . $e->getMessage());
    }

    $out['success'] = true;
    $out['message'] = 'Dados fiscais atualizados com sucesso.';
    echo json_encode($out);

} catch (Throwable $e) {
    error_log('fiscal_update error: ' . $e->getMessage());
    http_response_code(500);
    $out['errors']['server'] = 'Erro ao atualizar dados fiscais.';
    echo json_encode($out);
}
