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

// Ações por papel (renderização comum)
$actions = [];
if ($user['role'] === 'Gestor') {
  $actions = [
    ['label' => 'Clientes', 'href' => '/admin/customers.php', 'primary' => true],
    ['label' => 'Tickets', 'href' => '/admin/tickets.php'],
    ['label' => 'Relatórios', 'href' => '/admin/reports.php'],
    ['label' => 'Definições', 'href' => '/admin/settings.php']
  ];
} elseif ($user['role'] === 'Suporte Financeira') {
  $actions = [
    ['label' => 'Faturas', 'href' => '/admin/payments.php', 'primary' => true],
    ['label' => 'Avisos de Pagamento', 'href' => '/admin/payment-warnings.php'],
    ['label' => 'Despesas', 'href' => '/admin/expenses.php']
  ];
} elseif (in_array($user['role'], ['Suporte ao Cliente','Suporte Técnica'])) {
  $actions = [
    ['label' => 'Tickets', 'href' => '/admin/tickets.php', 'primary' => true],
    ['label' => 'Documentos', 'href' => '/admin/documents.php'],
    ['label' => 'Bancos de Conhecimento', 'href' => '/admin/knowledge-base.php']
  ];
} else {
  $actions = [
    ['label' => 'Abrir Ticket', 'href' => '/support.php', 'primary' => true],
    ['label' => 'Ver Faturas', 'href' => '/finance.php'],
    ['label' => 'Gerir Domínios', 'href' => '/domains.php']
  ];
}

// Atividade recente
$activities = [];
try {
  $stmt = $pdo->prepare('SELECT type, message, created_at FROM logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
  $stmt->execute([$user['id']]);
  $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  // silencioso
}

?>
<?php define('DASHBOARD_LAYOUT', true); ?>
<?php include __DIR__ . '/inc/header.php'; ?>

<div class="dashboard-page">
  <div class="page-header">
    <div class="page-header-left">
      <h1 class="page-title">Painel</h1>
      <p class="page-subtitle">Bem-vindo, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> · ID <?php echo $cwc; ?></p>
    </div>
    <div class="page-header-right">
      <a href="/support.php" class="btn btn-secondary btn-sm"><span class="icon">📋</span> Ver Tickets</a>
      <?php if ($user['role'] === 'Gestor'): ?>
        <a href="/admin/services.php" class="btn btn-primary btn-sm"><span class="icon">➕</span> Novo Serviço</a>
      <?php else: ?>
        <a href="/support.php" class="btn btn-primary btn-sm"><span class="icon">➕</span> Abrir Ticket</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Metrics Overview -->
  <div class="metrics-grid">
    <?php if ($user['role'] === 'Gestor'): ?>
      <div class="metric-card">
        <div class="metric-icon" style="background: #e0f2fe;"><span style="color: #0284c7;">👥</span></div>
        <div class="metric-content">
          <div class="metric-title">Utilizadores</div>
          <div class="metric-value"><?php echo $metrics['users_total']; ?></div>
          <div class="metric-subtitle">0 total</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon" style="background: #dcfce7;"><span style="color: #16a34a;">🌐</span></div>
        <div class="metric-content">
          <div class="metric-title">Domínios</div>
          <div class="metric-value"><?php echo $metrics['domains_total']; ?></div>
          <div class="metric-subtitle">Total registados</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon" style="background: #fef3c7;"><span style="color: #d97706;">💰</span></div>
        <div class="metric-content">
          <div class="metric-title">Faturas por pagar</div>
          <div class="metric-value"><?php echo $metrics['invoices_unpaid']; ?></div>
          <div class="metric-subtitle">Pendentes</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon" style="background: #ede9fe;"><span style="color: #7c3aed;">🎫</span></div>
        <div class="metric-content">
          <div class="metric-title">Tickets abertos</div>
          <div class="metric-value"><?php echo $metrics['tickets_open']; ?></div>
          <div class="metric-subtitle">Requerem atenção</div>
        </div>
      </div>
    <?php elseif ($user['role'] === 'Suporte Financeira'): ?>
      <div class="metric-card">
        <div class="metric-icon" style="background: #e0f2fe;"><span style="color: #0284c7;">📊</span></div>
        <div class="metric-content">
          <div class="metric-title">Total de faturas</div>
          <div class="metric-value"><?php echo $metrics['invoices_total']; ?></div>
          <div class="metric-subtitle">Todas as faturas</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon" style="background: #fef3c7;"><span style="color: #d97706;">⚠️</span></div>
        <div class="metric-content">
          <div class="metric-title">Faturas por pagar</div>
          <div class="metric-value"><?php echo $metrics['invoices_unpaid']; ?></div>
          <div class="metric-subtitle">Pendentes</div>
        </div>
      </div>
    <?php elseif (in_array($user['role'], ['Suporte ao Cliente','Suporte Técnica'])): ?>
      <div class="metric-card">
        <div class="metric-icon" style="background: #ede9fe;"><span style="color: #7c3aed;">🎫</span></div>
        <div class="metric-content">
          <div class="metric-title">Tickets abertos</div>
          <div class="metric-value"><?php echo $metrics['tickets_open']; ?></div>
          <div class="metric-subtitle">Requerem atenção</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon" style="background: #dcfce7;"><span style="color: #16a34a;">🌐</span></div>
        <div class="metric-content">
          <div class="metric-title">Domínios total</div>
          <div class="metric-value"><?php echo $metrics['domains_total']; ?></div>
          <div class="metric-subtitle">Geridos</div>
        </div>
      </div>
    <?php else: ?>
      <div class="metric-card">
        <div class="metric-icon" style="background: #dcfce7;"><span style="color: #16a34a;">✅</span></div>
        <div class="metric-content">
          <div class="metric-title">Serviços Ativos</div>
          <div class="metric-value"><?php echo $metrics['total_services']; ?></div>
          <div class="metric-subtitle">A funcionar</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon" style="background: #e0f2fe;"><span style="color: #0284c7;">🌐</span></div>
        <div class="metric-content">
          <div class="metric-title">Domínios Ativos</div>
          <div class="metric-value"><?php echo $metrics['my_services_active']; ?></div>
          <div class="metric-subtitle">Registados</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon" style="background: #fee2e2;"><span style="color: #dc2626;">⚠️</span></div>
        <div class="metric-content">
          <div class="metric-title">Pagamentos em Atraso</div>
          <div class="metric-value"><?php echo $metrics['overdue_invoices']; ?></div>
          <div class="metric-subtitle">Requerem atenção</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon" style="background: #ede9fe;"><span style="color: #7c3aed;">🎫</span></div>
        <div class="metric-content">
          <div class="metric-title">Tickets Abertos</div>
          <div class="metric-value"><?php echo $metrics['my_tickets_active']; ?></div>
          <div class="metric-subtitle">Pedidos ativos</div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Recent Activity Section -->
  <div class="dashboard-section">
    <div class="section-header">
      <h2 class="section-title"><span class="icon">📋</span> Atividade Recente</h2>
      <a href="/logs.php" class="view-all-link">Ver tudo <span class="arrow">→</span></a>
    </div>
    <div class="dashboard-card">
      <?php if (empty($activities)): ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <p>Sem atividade recente.</p>
        </div>
      <?php else: ?>
        <div class="activity-list">
          <?php foreach ($activities as $act): ?>
            <div class="activity-item">
              <div class="activity-content">
                <strong><?php echo htmlspecialchars($act['type']); ?></strong> — <?php echo htmlspecialchars($act['message']); ?>
              </div>
              <div class="activity-meta"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($act['created_at']))); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php include __DIR__ . '/inc/footer.php'; ?>

