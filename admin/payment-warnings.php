<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';
require_once __DIR__ . '/../inc/permissions.php';

checkRole(['Gestor','Suporte Financeiro']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$pdo = getDB();
$accessLevel = getAccessLevel($pdo, $user, 'payment_warnings');

if (!$accessLevel || $accessLevel === 'no') {
    http_response_code(403);
    echo 'Acesso negado ao recurso Avisos de Pagamento.';
    exit;
}

$canManage = in_array($accessLevel, ['manage_all', 'manage_own_clients', 'manage_own_clients_no_delete', 'manage_created', 'manage_created_no_delete']);
$canDelete = in_array($accessLevel, ['manage_all', 'manage_own_clients', 'manage_created']);

$levelLabels = [
  'manage_all' => 'Gerir todos',
  'view_all' => 'Ver todos (apenas leitura)',
  'manage_own_clients' => 'Gerir dos clientes próprios',
  'manage_own_clients_no_delete' => 'Gerir clientes próprios (sem eliminar)',
  'view_own_clients' => 'Ver clientes próprios (apenas leitura)',
  'manage_created' => 'Gerir criados por você',
  'manage_created_no_delete' => 'Gerir criados (sem eliminar)'
];

$content = '<div class="card">
  <h2>Avisos de Pagamento</h2>';
  
if ($accessLevel): 
  $content .= '<div style="margin-bottom:16px;padding:12px;background:#e3f2fd;border-radius:4px">
      <strong>Seu nível de acesso:</strong> ' . htmlspecialchars($levelLabels[$accessLevel] ?? $accessLevel) . '
    </div>';
endif;
  
if ($canManage): 
  $content .= '<button class="btn" style="margin-bottom:12px">+ Novo Aviso</button>';
endif;
  
$content .= '<p>Gestão de Avisos de Pagamento — em desenvolvimento.</p>
  <p><small>O nível de acesso está a ser validado baseado nas permissões configuradas.</small></p>
</div>';

echo renderDashboardLayout('Avisos de Pagamento', 'Gestão de avisos de pagamento', $content, 'payment-warnings');
?>

