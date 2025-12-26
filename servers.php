<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/dashboard_helper.php';

checkRole(['Cliente','Suporte ao Cliente','Suporte Técnico','Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$content = '<div class="card">
  <h2>Servidores</h2>
  <p>Página em desenvolvimento. Em breve poderá consultar planos de servidores aqui.</p>
</div>';

echo renderDashboardLayout('Servidores', 'Gestão de servidores e infraestrutura', $content, 'servers');
?>
