<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';

checkRole(['Gestor','Suporte ao Cliente','Suporte Técnico','Suporte Financeiro']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$content = '<div class="card">
  <h2>Tarefas</h2>
  <p>Gestão de tarefas — em desenvolvimento.</p>
</div>';

echo renderDashboardLayout('Tarefas', 'Gestão de tarefas e atividades', $content, 'tasks');
?>
