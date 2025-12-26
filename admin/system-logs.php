<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';

checkRole(['Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$content = '<div class="card">
  <h2>Logs do Sistema</h2>
  <p>Página em desenvolvimento.</p>
</div>';

echo renderDashboardLayout('Logs do Sistema', 'Visualização de logs do sistema', $content, 'system-logs');
?>
