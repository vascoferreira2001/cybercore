<?php
require_once __DIR__ . '/inc/auth.php';
requireRole(['Cliente','Suporte Financeira','Gestor']);
$pdo = getDB();
$user = currentUser();
if (in_array($user['role'], ['Suporte Financeira','Gestor'])) {
  $stmt = $pdo->query('SELECT i.*, u.email AS owner_email, u.first_name, u.last_name FROM invoices i JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC');
  $invoices = $stmt->fetchAll();
} else {
  $stmt = $pdo->prepare('SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC');
  $stmt->execute([$user['id']]);
  $invoices = $stmt->fetchAll();
}
?>
<?php include __DIR__ . '/inc/header.php'; ?>
<div class="card">
  <h2>Financeiro - Avisos de Pagamento</h2>
  <?php if(empty($invoices)): ?>Nenhuma fatura encontrada.<?php else: ?>
    <table style="width:100%"><thead><tr><th>Ref</th><th>Valor</th><th>Vencimento</th><th>Status</th></tr></thead><tbody>
    <?php foreach($invoices as $inv): ?>
      <tr><td><?php echo htmlspecialchars($inv['reference']); ?></td><td><?php echo $inv['amount']; ?></td><td><?php echo $inv['due_date']; ?></td><td><?php echo $inv['status']; ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
