<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';
require_once __DIR__ . '/../inc/permissions.php';

checkRole(['Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$pdo = getDB();
$accessLevel = getAccessLevel($pdo, $user, 'expenses');

if (!$accessLevel || $accessLevel === 'no') {
    http_response_code(403);
    echo 'Acesso negado ao recurso Despesas.';
    exit;
}

$canManageAll = $user['role'] === 'Gestor' || $accessLevel === 'all';
$canManageOwn = in_array($accessLevel, ['own', 'manage_own']);

$levelLabels = [
  'all' => 'Todas as despesas',
  'own' => 'Apenas despesas próprias',
  'manage_own' => 'Gerir despesas próprias'
];

$content = '<div class="card">
  <h2>Despesas</h2>';
  
if ($accessLevel): 
  $content .= '<div style="margin-bottom:16px;padding:12px;background:#e3f2fd;border-radius:4px">
      <strong>Seu nível de acesso:</strong> ' . htmlspecialchars($levelLabels[$accessLevel] ?? $accessLevel) . '
    </div>';
endif;
  
if ($canManageAll || $canManageOwn): 
  $content .= '<button class="btn" style="margin-bottom:12px">+ Nova Despesa</button>';
endif;
  
$content .= '<p>Gestão de Despesas — em desenvolvimento.</p>
  <p><small>O nível de acesso está a ser validado baseado nas permissões configuradas.</small></p>
</div>';

echo renderDashboardLayout('Despesas', 'Gestão de despesas', $content, 'expenses');
?>
