<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
requireLogin();
$user = currentUser();
$pdo = getDB();
if (!$user) { header('Location: logout.php'); exit; }

// Gerar CWC ID
$cwc = 'CWC#' . str_pad($user['id'], 4, '0', STR_PAD_LEFT);

// Resumos e métricas conforme role
// Função segura para contagens que evita falhas caso tabelas não existam
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

$metrics = [];
if ($user['role'] === 'Gestor') {
  $metrics['users_total'] = safeCount($pdo, 'SELECT COUNT(*) FROM users');
  $metrics['domains_total'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains');
  $metrics['invoices_unpaid'] = safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'");
  $metrics['tickets_open'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'open'");
} elseif ($user['role'] === 'Suporte Financeira') {
  $metrics['invoices_total'] = safeCount($pdo, 'SELECT COUNT(*) FROM invoices');
  $metrics['invoices_unpaid'] = safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'");
} elseif (in_array($user['role'], ['Suporte ao Cliente','Suporte Técnica'])) {
  $metrics['tickets_open'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'open'");
  $metrics['domains_total'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains');
} else { // Cliente
  $metrics['total_services'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains WHERE user_id = ? AND status = "active"', [$user['id']]);
  $metrics['my_services_active'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains WHERE user_id = ? AND type = "Domínios" AND status = "active"', [$user['id']]);
  $metrics['my_tickets_active'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status = 'open'", [$user['id']]);
  $metrics['overdue_invoices'] = safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE user_id = ? AND status != 'paid' AND due_date < NOW()", [$user['id']]);
}

?>
<?php include __DIR__ . '/inc/header.php'; ?>

<div class="shell single">
  <div class="panel">
    <div class="panel-header">
      <h1>Painel</h1>
      <p>Bem-vindo, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> · ID <?php echo $cwc; ?></p>
    </div>

    <?php if ($user['role'] === 'Gestor'): ?>
      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-title">Utilizadores</div>
          <div class="metric-value"><?php echo $metrics['users_total']; ?></div>
        </div>
        <div class="metric-card">
          <div class="metric-title">Domínios</div>
          <div class="metric-value"><?php echo $metrics['domains_total']; ?></div>
        </div>
        <div class="metric-card">
          <div class="metric-title">Faturas por pagar</div>
          <div class="metric-value"><?php echo $metrics['invoices_unpaid']; ?></div>
        </div>
        <div class="metric-card">
          <div class="metric-title">Tickets abertos</div>
          <div class="metric-value"><?php echo $metrics['tickets_open']; ?></div>
        </div>
      </div>
      <div class="info-text">Utilize o menu lateral para aceder às áreas detalhadas.</div>
    <?php elseif ($user['role'] === 'Suporte Financeira'): ?>
      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-title">Total de faturas</div>
          <div class="metric-value"><?php echo $metrics['invoices_total']; ?></div>
        </div>
        <div class="metric-card">
          <div class="metric-title">Faturas por pagar</div>
          <div class="metric-value"><?php echo $metrics['invoices_unpaid']; ?></div>
        </div>
      </div>
    <?php elseif (in_array($user['role'], ['Suporte ao Cliente','Suporte Técnica'])): ?>
      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-title">Tickets abertos</div>
          <div class="metric-value"><?php echo $metrics['tickets_open']; ?></div>
        </div>
        <div class="metric-card">
          <div class="metric-title">Domínios total</div>
          <div class="metric-value"><?php echo $metrics['domains_total']; ?></div>
        </div>
      </div>
      <div class="info-text">Aceda a Suporte para gerir tickets.</div>
    <?php else: ?>
      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-title">Serviços Ativos</div>
          <div class="metric-value"><?php echo $metrics['total_services']; ?></div>
        </div>
        <div class="metric-card">
          <div class="metric-title">Domínios Ativos</div>
          <div class="metric-value"><?php echo $metrics['my_services_active']; ?></div>
        </div>
        <div class="metric-card">
          <div class="metric-title">Avisos de Pagamento em Atraso</div>
          <div class="metric-value"><?php echo $metrics['overdue_invoices']; ?></div>
        </div>
        <div class="metric-card">
          <div class="metric-title">Pedidos de Suporte</div>
          <div class="metric-value"><?php echo $metrics['my_tickets_active']; ?></div>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>

