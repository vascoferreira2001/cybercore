<?php
/**
 * Fiscal Change Request Management
 * Handles submission, approval, and rejection of fiscal data changes
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

/**
 * Valida NIF português
 * @param string $nif
 * @return bool
 */
function isValidNIF($nif) {
    $nif = preg_replace('/\D/', '', $nif);
    if (strlen($nif) !== 9) return false;
    
    $digits = str_split($nif);
    $weights = [9, 8, 7, 6, 5, 4, 3, 2];
    $sum = 0;
    
    for ($i = 0; $i < 8; $i++) {
        $sum += $digits[$i] * $weights[$i];
    }
    
    $check = 11 - ($sum % 11);
    $checkDigit = ($check >= 10) ? 0 : $check;
    
    return $checkDigit === (int)$digits[8];
}

/**
 * Submete solicitação de alteração de dados fiscais
 * @param PDO $pdo
 * @param int $userId
 * @param string $newNIF
 * @param string $newEntityType
 * @param string|null $newCompanyName
 * @param string $reason
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function submitFiscalChangeRequest($pdo, $userId, $newNIF, $newEntityType, $newCompanyName, $reason) {
    // Validar entrada
    if (!isValidNIF($newNIF)) {
        return ['success' => false, 'message' => 'NIF inválido.'];
    }
    
    if (!in_array($newEntityType, ['Singular', 'Coletiva'])) {
        return ['success' => false, 'message' => 'Tipo de entidade inválido.'];
    }
    
    if (empty($reason) || strlen($reason) < 10) {
        return ['success' => false, 'message' => 'Motivo da alteração obrigatório (mínimo 10 caracteres).'];
    }
    
    // Carregar dados fiscais atuais do utilizador
    $stmt = $pdo->prepare('SELECT nif, entity_type, company_name FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current) {
        return ['success' => false, 'message' => 'Utilizador não encontrado.'];
    }
    
    // Verifica se já existe solicitação pendente
    $stmt = $pdo->prepare('SELECT id FROM fiscal_change_requests WHERE user_id = ? AND status = "pending"');
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Já existe uma solicitação pendente. Aguarde a revisão.'];
    }
    
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO fiscal_change_requests 
            (user_id, old_nif, new_nif, old_entity_type, new_entity_type, old_company_name, new_company_name, reason, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending")'
        );
        
        $stmt->execute([
            $userId,
            $current['nif'],
            $newNIF,
            $current['entity_type'],
            $newEntityType,
            $current['company_name'],
            $newCompanyName,
            $reason
        ]);
        
        $requestId = $pdo->lastInsertId();
        
        // Log no sistema
        $pdo->prepare('INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)')
            ->execute([$userId, 'fiscal_change_request', "Solicitação #$requestId: NIF $newNIF, Tipo: $newEntityType"]);
        
        return ['success' => true, 'message' => 'Solicitação enviada com sucesso. Um gestor analisará em breve.', 'id' => $requestId];
    } catch (PDOException $e) {
        error_log('Fiscal change request error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao guardar solicitação.'];
    }
}

/**
 * Aprova solicitação de alteração de dados fiscais
 * @param PDO $pdo
 * @param int $requestId
 * @param int $reviewedBy (ID do gestor/suporte que aprova)
 * @return array ['success' => bool, 'message' => string]
 */
function approveFiscalChangeRequest($pdo, $requestId, $reviewedBy) {
    $reviewer = currentUser();
    
    // Verificar permissões
    if (!in_array($reviewer['role'], ['Gestor', 'Suporte Financeiro'])) {
        return ['success' => false, 'message' => 'Sem permissão para aprovar alterações fiscais.'];
    }
    
    // Carregar solicitação
    $stmt = $pdo->prepare('SELECT * FROM fiscal_change_requests WHERE id = ?');
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        return ['success' => false, 'message' => 'Solicitação não encontrada.'];
    }
    
    if ($request['status'] !== 'pending') {
        return ['success' => false, 'message' => 'Esta solicitação já foi revogada.'];
    }
    
    try {
        // Atualizar dados do utilizador
        $stmt = $pdo->prepare(
            'UPDATE users SET nif = ?, entity_type = ?, company_name = ? WHERE id = ?'
        );
        $stmt->execute([
            $request['new_nif'],
            $request['new_entity_type'],
            $request['new_company_name'],
            $request['user_id']
        ]);
        
        // Atualizar status da solicitação
        $stmt = $pdo->prepare(
            'UPDATE fiscal_change_requests SET status = "approved", reviewed_by = ?, reviewed_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$reviewedBy, $requestId]);
        
        // Log no sistema
        $pdo->prepare('INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)')
            ->execute([$request['user_id'], 'fiscal_change_approved', "Solicitação #$requestId aprovada por {$reviewer['email']}"]);
        
        return ['success' => true, 'message' => 'Alteração fiscal aprovada e aplicada com sucesso.'];
    } catch (PDOException $e) {
        error_log('Fiscal approval error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao aprovar solicitação.'];
    }
}

/**
 * Rejeita solicitação de alteração de dados fiscais
 * @param PDO $pdo
 * @param int $requestId
 * @param int $reviewedBy (ID do gestor/suporte que rejeita)
 * @param string $reason (motivo da rejeição)
 * @return array ['success' => bool, 'message' => string]
 */
function rejectFiscalChangeRequest($pdo, $requestId, $reviewedBy, $reason = '') {
    $reviewer = currentUser();
    
    // Verificar permissões
    if (!in_array($reviewer['role'], ['Gestor', 'Suporte Financeiro'])) {
        return ['success' => false, 'message' => 'Sem permissão para rejeitar alterações fiscais.'];
    }
    
    // Carregar solicitação
    $stmt = $pdo->prepare('SELECT * FROM fiscal_change_requests WHERE id = ?');
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        return ['success' => false, 'message' => 'Solicitação não encontrada.'];
    }
    
    if ($request['status'] !== 'pending') {
        return ['success' => false, 'message' => 'Esta solicitação já foi revogada.'];
    }
    
    try {
        // Atualizar status da solicitação
        $stmt = $pdo->prepare(
            'UPDATE fiscal_change_requests SET status = "rejected", reviewed_by = ?, reviewed_at = NOW(), decision_reason = ? WHERE id = ?'
        );
        $stmt->execute([$reviewedBy, $reason, $requestId]);
        
        // Log no sistema
        $pdo->prepare('INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)')
            ->execute([$request['user_id'], 'fiscal_change_rejected', "Solicitação #$requestId rejeitada por {$reviewer['email']}: $reason"]);
        
        return ['success' => true, 'message' => 'Alteração fiscal rejeitada.'];
    } catch (PDOException $e) {
        error_log('Fiscal rejection error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao rejeitar solicitação.'];
    }
}

/**
 * Retorna histórico de solicitações do utilizador
 * @param PDO $pdo
 * @param int $userId
 * @return array
 */
function getUserFiscalRequests($pdo, $userId) {
    $stmt = $pdo->prepare(
        'SELECT * FROM fiscal_change_requests 
         WHERE user_id = ? 
         ORDER BY requested_at DESC LIMIT 10'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retorna todas as solicitações pendentes (apenas para Gestor/Suporte Financeiro)
 * @param PDO $pdo
 * @return array
 */
function getPendingFiscalRequests($pdo) {
    $stmt = $pdo->prepare(
        'SELECT fcr.*, u.first_name, u.last_name, u.email 
         FROM fiscal_change_requests fcr
         JOIN users u ON fcr.user_id = u.id
         WHERE fcr.status = "pending"
         ORDER BY fcr.requested_at ASC'
    );
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
