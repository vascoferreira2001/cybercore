<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';
require_once __DIR__ . '/../inc/permissions.php';

checkRole(['Cliente','Suporte ao Cliente','Suporte Técnico','Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;
requirePermission('can_view_own_services', $user);

$content = '<div class="card">
  <h2>Alojamento</h2>
  <p>Gestão de planos e alojamentos associados à sua conta. Em breve, mais detalhes aqui.</p>
</div>';

echo renderDashboardLayout('Alojamento', 'Gestão de alojamentos e planos', $content, 'hosting');
