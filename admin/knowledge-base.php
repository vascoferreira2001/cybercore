<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';

checkRole(['Gestor','Suporte ao Cliente','Suporte Técnica','Suporte Financeira']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$content = '<div class="card">
  <h2>Base de Conhecimento</h2>
  <p>Página em desenvolvimento.</p>
</div>';

echo renderDashboardLayout('Base de Conhecimento', 'Gestão de artigos e documentação', $content, 'knowledge-base');
?>
