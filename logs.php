<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/dashboard_helper.php';

checkRole(['Cliente','Suporte Financeira','Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;
$pdo = getDB();

if (in_array($user['role'], ['Suporte Financeira','Gestor'])) {
  $stmt = $pdo->query('SELECT l.*, u.email AS owner_email FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC');
  $logs = $stmt->fetchAll();
} else {
  $stmt = $pdo->prepare('SELECT * FROM logs WHERE user_id = ? ORDER BY created_at DESC');
  $stmt->execute([$user['id']]);
  $logs = $stmt->fetchAll();
}

$content = '<div class="card">
  <h2>Logs</h2>';
if(empty($logs)): 
  $content .= '<p>Sem logs recentes.</p>';
else: 
  $content .= '<ul>';
  foreach($logs as $l): 
    $content .= '<li><strong>' . htmlspecialchars($l['type']) . '</strong>: ' . htmlspecialchars($l['message']) . ' <span class="small">(' . $l['created_at'] . ')</span></li>';
  endforeach;
  $content .= '</ul>';
endif;
$content .= '</div>';

echo renderDashboardLayout('Logs', 'Histórico de atividades do sistema', $content, 'logs');
?>
