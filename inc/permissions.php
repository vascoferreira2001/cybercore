<?php
/**
 * Sistema de Permissões - Validação e Controle de Acesso
 * 
 * Funções para verificar permissões de departamentos baseadas na configuração
 * realizada em admin/settings.php > Funções da Equipa
 */

/**
 * Obtém as permissões de um departamento
 * 
 * @param PDO $pdo Conexão à base de dados
 * @param int $deptId ID do departamento
 * @return array Array com todas as permissões do departamento
 */
function getDepartmentPermissions($pdo, $deptId) {
  try {
    $stmt = $pdo->prepare('SELECT permission_key, permission_value FROM department_permissions WHERE department_id = ?');
    $stmt->execute([$deptId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $permissions = [];
    foreach ($rows as $row) {
      $permissions[$row['permission_key']] = $row['permission_value'];
    }
    return $permissions;
  } catch (Exception $e) {
    error_log('getDepartmentPermissions error: ' . $e->getMessage());
    return [];
  }
}

/**
 * Obtém as permissões do utilizador atual baseadas no seu departamento
 * 
 * @param PDO $pdo Conexão à base de dados
 * @param array $user Array do utilizador (deve conter 'id' e role será obtido da BD)
 * @return array Array com todas as permissões do utilizador
 */
function getUserPermissions($pdo, $user) {
  if (!$user || !isset($user['id'])) {
    return [];
  }
  
  try {
    // Obtém o departamento/role do utilizador
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
      return [];
    }
    
    // Procura o departamento com esse nome
    $stmt = $pdo->prepare('SELECT id FROM departments WHERE name = ?');
    $stmt->execute([$userData['role']]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dept) {
      return [];
    }
    
    return getDepartmentPermissions($pdo, $dept['id']);
  } catch (Exception $e) {
    error_log('getUserPermissions error: ' . $e->getMessage());
    return [];
  }
}

/**
 * Verifica se um utilizador tem uma permissão específica
 * 
 * @param PDO $pdo Conexão à base de dados
 * @param array $user Array do utilizador
 * @param string $permissionKey Chave da permissão (ex: 'customers', 'tickets', 'licenses')
 * @param string $expectedValue Valor esperado (ex: 'yes', 'all', 'no', etc.) - se vazio, verifica se existe
 * @return bool true se tem permissão, false caso contrário
 */
function hasPermission($pdo, $user, $permissionKey, $expectedValue = null) {
  // Gestor tem sempre permissão total
  if ($user && $user['role'] === 'Gestor') {
    return true;
  }
  
  $permissions = getUserPermissions($pdo, $user);
  
  if (empty($permissions[$permissionKey])) {
    return false;
  }
  
  // Se não especificar valor esperado, apenas verificar se a permissão existe
  if ($expectedValue === null) {
    return true;
  }
  
  // Verificar se o valor corresponde (suporta múltiplos valores separados por |)
  if (strpos($expectedValue, '|') !== false) {
    $allowedValues = explode('|', $expectedValue);
    return in_array($permissions[$permissionKey], $allowedValues);
  }
  
  return $permissions[$permissionKey] === $expectedValue;
}

/**
 * Verifica se um utilizador NÃO tem permissão para uma ação
 * (útil para bloquear acesso)
 * 
 * @param PDO $pdo Conexão à base de dados
 * @param array $user Array do utilizador
 * @param string $permissionKey Chave da permissão
 * @return bool true se NÃO tem permissão
 */
function deniesPermission($pdo, $user, $permissionKey) {
  $permissions = getUserPermissions($pdo, $user);
  return empty($permissions[$permissionKey]) || $permissions[$permissionKey] === 'no';
}

/**
 * Verifica se um utilizador pode visualizar uma página/recurso
 * 
 * @param PDO $pdo Conexão à base de dados
 * @param array $user Array do utilizador
 * @param string $resource Nome do recurso (ex: 'customers', 'tickets', 'expenses')
 * @param string $level Nível de acesso requerido (ex: 'view', 'manage', 'all')
 * @return bool true se tem acesso, false caso contrário
 */
function canAccessResource($pdo, $user, $resource, $level = 'view') {
  // Gestor tem sempre acesso
  if ($user && $user['role'] === 'Gestor') {
    return true;
  }
  
  $permissions = getUserPermissions($pdo, $user);
  
  // Se a permissão é 'no', nega acesso
  if (empty($permissions[$resource]) || $permissions[$resource] === 'no') {
    return false;
  }
  
  // Se level é 'view', qualquer coisa que não seja 'no' é permitido
  if ($level === 'view') {
    return true;
  }
  
  // Se level é 'manage', requer opção que permita gerenciar
  if ($level === 'manage') {
    $allowedManage = ['all', 'manage_all', 'manage_own_clients', 'manage_created'];
    return in_array($permissions[$resource], $allowedManage);
  }
  
  return false;
}

/**
 * Obtém o nível de acesso para um recurso específico
 * Retorna a permissão exata para lógica mais granular
 * 
 * @param PDO $pdo Conexão à base de dados
 * @param array $user Array do utilizador
 * @param string $resource Nome do recurso
 * @return string|null Valor da permissão ou null se não existe
 */
function getAccessLevel($pdo, $user, $resource) {
  if ($user && $user['role'] === 'Gestor') {
    return 'all'; // Gestor tem acesso total
  }
  
  $permissions = getUserPermissions($pdo, $user);
  return $permissions[$resource] ?? null;
}

/**
 * Valida acesso a um recurso e termina se negado
 * Usa esta função no topo das páginas para bloquear acesso não autorizado
 * 
 * @param PDO $pdo Conexão à base de dados
 * @param array $user Array do utilizador
 * @param string $resource Nome do recurso
 * @param string $level Nível de acesso requerido ('view', 'manage', 'all')
 * @param string $message Mensagem de erro personalizada (opcional)
 */
function requirePermission($pdo, $user, $resource, $level = 'view', $message = 'Acesso negado.') {
  if (!canAccessResource($pdo, $user, $resource, $level)) {
    http_response_code(403);
    echo htmlspecialchars($message);
    exit;
  }
}

/**
 * Valida múltiplos recursos (requer pelo menos um)
 * 
 * @param PDO $pdo Conexão à base de dados
 * @param array $user Array do utilizador
 * @param array $resources Array de nomes de recursos
 * @param string $message Mensagem de erro (opcional)
 */
function requireAnyPermission($pdo, $user, $resources, $message = 'Acesso negado.') {
  foreach ($resources as $resource) {
    if (canAccessResource($pdo, $user, $resource, 'view')) {
      return true;
    }
  }
  http_response_code(403);
  echo htmlspecialchars($message);
  exit;
}

/**
 * Valida múltiplos recursos (requer todos)
 * 
 * @param PDO $pdo Conexão à base de dados
 * @param array $user Array do utilizador
 * @param array $resources Array de nomes de recursos
 * @param string $message Mensagem de erro (opcional)
 */
function requireAllPermissions($pdo, $user, $resources, $message = 'Acesso negado.') {
  foreach ($resources as $resource) {
    if (!canAccessResource($pdo, $user, $resource, 'view')) {
      http_response_code(403);
      echo htmlspecialchars($message);
      exit;
    }
  }
}

?>
