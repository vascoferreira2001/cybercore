<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

requireLogin();
$user = currentUser();

// Apenas roles de admin têm acesso
if (!in_array($user['role'], ['Gestor','Suporte ao Cliente','Suporte Técnica','Suporte Financeira'])) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}

$pdo = getDB();

// Métricas conforme role
$metrics = [];
if ($user['role'] === 'Gestor') {
    $metrics['total_clients'] = safeCount($pdo, 'SELECT COUNT(*) FROM users WHERE role = "Cliente"');
    $metrics['total_tickets'] = safeCount($pdo, 'SELECT COUNT(*) FROM tickets');
    $metrics['open_tickets'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'open'");
    $metrics['total_domains'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains');
    $metrics['unpaid_invoices'] = safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'");
} elseif ($user['role'] === 'Suporte Financeira') {
    $metrics['total_invoices'] = safeCount($pdo, 'SELECT COUNT(*) FROM invoices');
    $metrics['unpaid_invoices'] = safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'");
    $metrics['paid_invoices'] = safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'paid'");
} elseif (in_array($user['role'], ['Suporte ao Cliente','Suporte Técnica'])) {
    $metrics['open_tickets'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'open'");
    $metrics['pending_tickets'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'pending'");
    $metrics['total_clients'] = safeCount($pdo, 'SELECT COUNT(*) FROM users WHERE role = "Cliente"');
}

function safeCount($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Dashboard metric error: ' . $e->getMessage());
        return 0;
    }
}
?>
<?php include __DIR__ . '/../inc/header.php'; ?>

<div class="card">
  <h2>Painel de Administração</h2>
  <p>Bem-vindo ao painel administrativo, <?php echo htmlspecialchars($user['first_name']); ?>.</p>
</div>

<div class="widgets">
  <?php if ($user['role'] === 'Gestor'): ?>
    <div class="widget">
      <h4>Clientes</h4>
      <p><?php echo $metrics['total_clients']; ?></p>
    </div>
    <div class="widget">
      <h4>Tickets (Abertos)</h4>
      <p><?php echo $metrics['open_tickets']; ?> / <?php echo $metrics['total_tickets']; ?></p>
    </div>
    <div class="widget">
      <h4>Domínios</h4>
      <p><?php echo $metrics['total_domains']; ?></p>
    </div>
    <div class="widget">
      <h4>Faturas por Pagar</h4>
      <p><?php echo $metrics['unpaid_invoices']; ?></p>
    </div>
  <?php elseif ($user['role'] === 'Suporte Financeira'): ?>
    <div class="widget">
      <h4>Total de Faturas</h4>
      <p><?php echo $metrics['total_invoices']; ?></p>
    </div>
    <div class="widget">
      <h4>Por Pagar</h4>
      <p><?php echo $metrics['unpaid_invoices']; ?></p>
    </div>
    <div class="widget">
      <h4>Pagas</h4>
      <p><?php echo $metrics['paid_invoices']; ?></p>
    </div>
  <?php else: ?>
    <div class="widget">
      <h4>Tickets Abertos</h4>
      <p><?php echo $metrics['open_tickets']; ?></p>
    </div>
    <div class="widget">
      <h4>Tickets Pendentes</h4>
      <p><?php echo $metrics['pending_tickets']; ?></p>
    </div>
    <div class="widget">
      <h4>Clientes</h4>
      <p><?php echo $metrics['total_clients']; ?></p>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../inc/footer.php'; ?>
