<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';
require_once __DIR__ . '/../inc/permissions.php';

checkRole(['Gestor','Suporte ao Cliente','Suporte Técnica','Suporte Financeira']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$pdo = getDB();
$accessLevel = getAccessLevel($pdo, $user, 'tickets');

if (!$accessLevel || $accessLevel === 'no') {
    http_response_code(403);
    echo 'Acesso negado ao recurso Tickets.';
    exit;
}

$canManage = $user['role'] === 'Gestor' || in_array($accessLevel, ['manage_all', 'manage_created']);
$viewAssignedOnly = $accessLevel === 'assigned';
$viewCategoriesOnly = $accessLevel === 'categories';

$levelLabels = [
  'all' => 'Todos os tickets',
  'assigned' => 'Apenas tickets atribuídos',
  'categories' => 'Categorias específicas',
  'manage_all' => 'Gerir todos',
  'manage_created' => 'Gerir criados por você'
];

$content = '<div class="card">
  <h2>Tickets do Sistema de Suporte</h2>';
  
if ($accessLevel): 
  $content .= '<div style="margin-bottom:16px;padding:12px;background:#e3f2fd;border-radius:4px">
      <strong>Seu nível de acesso:</strong> ' . htmlspecialchars($levelLabels[$accessLevel] ?? $accessLevel) . '
    </div>';
endif;
  
if ($canManage): 
  $content .= '<button class="btn" style="margin-bottom:12px">+ Novo Ticket</button>';
endif;
  
$content .= '<p>Sistema de Tickets — em desenvolvimento.</p>
  <p><small>O nível de acesso está a ser validado baseado nas permissões configuradas.</small></p>
</div>';

echo renderDashboardLayout('Tickets', 'Gestão de tickets de suporte', $content, 'tickets');
?>

