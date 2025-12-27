<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/dashboard_helper.php';
require_once __DIR__ . '/inc/permissions.php';

checkRole(['Cliente','Suporte ao Cliente','Suporte Financeiro','Suporte Técnico','Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

// Gate via unified permission system
requirePermission('can_view_own_services', $user);

$content = '<div class="card">
  <h2>Serviços Contratados</h2>
  <p>Página em desenvolvimento. Em breve poderá gerir todos os seus serviços aqui.</p>
</div>';

echo renderDashboardLayout('Serviços', 'Gestão de serviços e suporte', $content, 'services');
?>
