<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];
// Simple placeholder: search tickets titles and domains names if tables exist
try {
  $pdo = getDB();
  if ($q !== '') {
    try {
      $stmt = $pdo->prepare("SELECT id, subject AS title, 'ticket' AS type FROM tickets WHERE subject LIKE ? LIMIT 10");
      $stmt->execute(['%'.$q.'%']);
      $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {}
    try {
      $stmt = $pdo->prepare("SELECT id, domain AS title, 'domain' AS type FROM domains WHERE domain LIKE ? LIMIT 10");
      $stmt->execute(['%'.$q.'%']);
      $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {}
  }
} catch (Throwable $e) {}
?>
<?php define('DASHBOARD_LAYOUT', true); ?>
<?php include __DIR__ . '/inc/header.php'; ?>
<div class="panel">
  <div class="panel-header">
    <h1>Pesquisar</h1>
    <p>Resultados para: <?php echo htmlspecialchars($q); ?></p>
  </div>
  <?php if ($q === ''): ?>
    <div class="card">Introduza palavras-chave na barra acima.</div>
  <?php else: ?>
    <?php if (empty($results)): ?>
      <div class="card">Sem resultados.</div>
    <?php else: ?>
      <div class="metrics-grid">
        <?php foreach ($results as $r): ?>
          <div class="metric-card">
            <div class="metric-title"><?php echo htmlspecialchars(strtoupper($r['type'])); ?></div>
            <div class="metric-value" style="font-size:18px;font-weight:600;">
              <?php echo htmlspecialchars($r['title']); ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
