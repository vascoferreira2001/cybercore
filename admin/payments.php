<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';

checkRole(['Gestor','Suporte Financeiro']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$content = '<div class="card">
  <h2>Pagamentos</h2>
  <p>Página em desenvolvimento.</p>
</div>';

echo renderDashboardLayout('Pagamentos', 'Gestão de pagamentos', $content, 'payments');
?>
