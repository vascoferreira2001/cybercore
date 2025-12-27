<?php
/**
 * CyberCore - Sistema único de permissões
 * Centraliza verificação de permissões por cargo/role
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/menu_config.php';

/**
 * Verifica se o utilizador (ou o atual) tem uma permissão específica
 * Gestor tem sempre permissão total
 */
function userHasPermission($permission, $user = null) {
  $user = $user ?: currentUser();
  if (!$user) return false;

  if (normalizeRoleName($user['role']) === 'Gestor') return true;

  return roleHasPermission(normalizeRoleName($user['role']), $permission);
}

function userHasAnyPermission(array $permissions, $user = null) {
  foreach ($permissions as $p) {
    if (userHasPermission($p, $user)) return true;
  }
  return false;
}

function userHasAllPermissions(array $permissions, $user = null) {
  foreach ($permissions as $p) {
    if (!userHasPermission($p, $user)) return false;
  }
  return true;
}

function requirePermission($permission, $user = null) {
  if (!userHasPermission($permission, $user)) {
    http_response_code(403);
    echo '<h1>403 - Acesso Negado</h1>';
    echo '<p>Permissão necessária: <code>' . htmlspecialchars($permission) . '</code></p>';
    exit;
  }
}

function requireAnyPermission(array $permissions, $user = null) {
  if (!userHasAnyPermission($permissions, $user)) {
    http_response_code(403);
    echo '<h1>403 - Acesso Negado</h1>';
    echo '<p>Requer pelo menos uma das permissões.</p>';
    exit;
  }
}

function requireAllPermissions(array $permissions, $user = null) {
  if (!userHasAllPermissions($permissions, $user)) {
    http_response_code(403);
    echo '<h1>403 - Acesso Negado</h1>';
    echo '<p>Requer todas as permissões especificadas.</p>';
    exit;
  }
}
?>
