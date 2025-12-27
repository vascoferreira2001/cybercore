<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';
require_once __DIR__ . '/../inc/permissions.php';

checkRole(['Gestor', 'Suporte Financeiro']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$pdo = getDB();
requirePermission('can_manage_expenses', $user);

$canManageExpenses = userHasPermission('can_manage_expenses', $user);

$content = '<div class="card">
  <h2>Despesas</h2>';

if ($canManageExpenses):
  $content .= '<button class="btn" style="margin-bottom:12px">+ Nova Despesa</button>';
endif;

$content .= '<p>Gestão de Despesas — em desenvolvimento.</p>
  <p><small>Permissão usada: can_manage_expenses.</small></p>
</div>';

echo renderDashboardLayout('Despesas', 'Gestão de despesas', $content, 'expenses');
?>
