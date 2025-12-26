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
  $stmt = $pdo->query('SELECT i.*, u.email AS owner_email, u.first_name, u.last_name FROM invoices i JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC');
  $invoices = $stmt->fetchAll();
} else {
  $stmt = $pdo->prepare('SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC');
  $stmt->execute([$user['id']]);
  $invoices = $stmt->fetchAll();
}

$content = '<div class="card">
  <h2>Financeiro - Avisos de Pagamento</h2>';
if(empty($invoices)): 
  $content .= '<p>Nenhuma fatura encontrada.</p>';
else:
  $content .= '<table style="width:100%"><thead><tr><th>Ref</th><th>Valor</th><th>Vencimento</th><th>Status</th></tr></thead><tbody>';
  foreach($invoices as $inv):
    $content .= '<tr><td>'.htmlspecialchars($inv['id']).'</td><td>€ '.number_format($inv['amount'],2).'</td><td>'.htmlspecialchars($inv['due_date']).'</td><td>'.htmlspecialchars($inv['status']).'</td></tr>';
  endforeach;
  $content .= '</tbody></table>';
endif;
$content .= '</div>';

echo renderDashboardLayout('Faturação', 'Gestão de faturas e pagamentos', $content, 'finance');
?>
