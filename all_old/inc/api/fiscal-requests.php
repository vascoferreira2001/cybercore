<?php
/**
 * API Endpoint: Fiscal Change Requests Handler
 * POST /inc/api/fiscal-requests.php
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../fiscal_requests.php';

// Validação básica
requireLogin();
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$user = currentUser();
$pdo = getDB();

$response = ['success' => false, 'message' => 'Ação inválida'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Método não permitido.';
} else {
    csrf_validate();
    
    switch ($action) {
        // Cliente submete solicitação
        case 'submit':
            if ($user['role'] !== 'Cliente') {
                http_response_code(403);
                $response['message'] = 'Apenas clientes podem solicitar alterações fiscais.';
                break;
            }
            
            $result = submitFiscalChangeRequest(
                $pdo,
                $user['id'],
                $_POST['newNIF'] ?? '',
                $_POST['newEntityType'] ?? '',
                $_POST['newCompanyName'] ?? '',
                $_POST['reason'] ?? ''
            );
            
            $response = $result;
            if ($response['success']) {
                http_response_code(201);
            } else {
                http_response_code(400);
            }
            break;
        
        // Gestor/Suporte Financeiro aprova
        case 'approve':
            if (!in_array($user['role'], ['Gestor', 'Suporte Financeiro'])) {
                http_response_code(403);
                $response['message'] = 'Sem permissão.';
                break;
            }
            
            $result = approveFiscalChangeRequest(
                $pdo,
                intval($_POST['requestId'] ?? 0),
                $user['id']
            );
            
            $response = $result;
            if (!$response['success']) {
                http_response_code(400);
            }
            break;
        
        // Gestor/Suporte Financeiro rejeita
        case 'reject':
            if (!in_array($user['role'], ['Gestor', 'Suporte Financeiro'])) {
                http_response_code(403);
                $response['message'] = 'Sem permissão.';
                break;
            }
            
            $result = rejectFiscalChangeRequest(
                $pdo,
                intval($_POST['requestId'] ?? 0),
                $user['id'],
                $_POST['reason'] ?? ''
            );
            
            $response = $result;
            if (!$response['success']) {
                http_response_code(400);
            }
            break;
        
        // Cliente vê seu histórico
        case 'getHistory':
            $requests = getUserFiscalRequests($pdo, $user['id']);
            $response = [
                'success' => true,
                'requests' => $requests
            ];
            break;
        
        // Gestor/Suporte vê pendentes
        case 'getPending':
            if (!in_array($user['role'], ['Gestor', 'Suporte Financeiro'])) {
                http_response_code(403);
                $response['message'] = 'Sem permissão.';
                break;
            }
            
            $requests = getPendingFiscalRequests($pdo);
            $response = [
                'success' => true,
                'requests' => $requests
            ];
            break;
        
        default:
            http_response_code(400);
            $response['message'] = 'Ação desconhecida.';
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
