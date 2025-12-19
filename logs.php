<?php
require_once __DIR__ . '/inc/auth.php';
requireRole(['Cliente','Suporte Financeira','Gestor']);
$pdo = getDB();
$user = currentUser();
if (in_array($user['role'], ['Suporte Financeira','Gestor'])) {
  $stmt = $pdo->query('SELECT l.*, u.email AS owner_email FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC');
  $logs = $stmt->fetchAll();
} else {
  $stmt = $pdo->prepare('SELECT * FROM logs WHERE user_id = ? ORDER BY created_at DESC');
  $stmt->execute([$user['id']]);
  $logs = $stmt->fetchAll();
}
?>
<?php include __DIR__ . '/inc/header.php'; ?>
<div class="card">
  <h2>Logs</h2>
  <?php if(empty($logs)): ?>Sem logs recentes.<?php else: ?>
    <ul>
      <?php foreach($logs as $l): ?>
        <li><strong><?php echo htmlspecialchars($l['type']); ?></strong>: <?php echo htmlspecialchars($l['message']); ?> <span class="small">(<?php echo $l['created_at']; ?>)</span></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
