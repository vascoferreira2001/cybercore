<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/permissions.php';

requireLogin();
$user = currentUser();

// Apenas roles de admin têm acesso
if (!in_array($user['role'], ['Gestor','Suporte ao Cliente','Suporte Técnica','Suporte Financeira'])) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}

// Função safeCount (definir ANTES de usar)
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

$pdo = getDB();

// Obtém as permissões do utilizador
$permissions = getUserPermissions($pdo, $user);

// Métricas conforme permissões
$metrics = [];

// Clientes - mostrar se tem acesso
if (canAccessResource($pdo, $user, 'customers', 'view')) {
    $metrics['total_clients'] = safeCount($pdo, 'SELECT COUNT(*) FROM users WHERE role = "Cliente"');
}

// Tickets - mostrar se tem acesso
if (canAccessResource($pdo, $user, 'tickets', 'view')) {
    $metrics['total_tickets'] = safeCount($pdo, 'SELECT COUNT(*) FROM tickets');
    $metrics['open_tickets'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'open'");
    $metrics['pending_tickets'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'pending'");
}

// Domínios - mostrar se tem acesso
if (canAccessResource($pdo, $user, 'services', 'view')) {
    $metrics['total_domains'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains');
}

// Faturas/Avisos de Pagamento - mostrar se tem acesso
if (canAccessResource($pdo, $user, 'payment_warnings', 'view')) {
    $metrics['total_invoices'] = safeCount($pdo, 'SELECT COUNT(*) FROM invoices');
    $metrics['unpaid_invoices'] = safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'");
    $metrics['paid_invoices'] = safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'paid'");
}

// Despesas - mostrar se tem acesso
if (canAccessResource($pdo, $user, 'expenses', 'view')) {
    $metrics['total_expenses'] = safeCount($pdo, 'SELECT COUNT(*) FROM expenses');
}

?>
<?php include __DIR__ . '/../inc/header.php'; ?>

<div class="shell single">
  <div class="panel">
    <div class="panel-header">
      <h1>Painel de Administração</h1>
      <p>Bem-vindo, <?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?>.</p>
    </div>

    <div class="section-title">Ações rápidas</div>
    <div class="actions">
      <?php if ($user['role'] === 'Gestor'): ?>
        <a class="action-btn primary" href="/admin/customers.php">Clientes</a>
        <a class="action-btn" href="/admin/tickets.php">Tickets</a>
        <a class="action-btn" href="/admin/reports.php">Relatórios</a>
        <a class="action-btn" href="/admin/settings.php">Definições</a>
      <?php elseif ($user['role'] === 'Suporte Financeira'): ?>
        <a class="action-btn primary" href="/admin/payments.php">Faturas</a>
        <a class="action-btn" href="/admin/payment-warnings.php">Avisos de Pagamento</a>
        <a class="action-btn" href="/admin/expenses.php">Despesas</a>
      <?php else: ?>
        <a class="action-btn primary" href="/admin/tickets.php">Tickets</a>
        <a class="action-btn" href="/admin/documents.php">Documentos</a>
        <a class="action-btn" href="/admin/knowledge-base.php">Bancos de Conhecimento</a>
      <?php endif; ?>
    </div>

    <?php if ($user['role'] === 'Gestor'): ?>
      <div class="metrics-grid">
        <div class="metric-card"><div class="metric-title">Clientes</div><div class="metric-value"><?php echo $metrics['total_clients']; ?></div></div>
        <div class="metric-card"><div class="metric-title">Tickets (Abertos/Total)</div><div class="metric-value"><?php echo $metrics['open_tickets']; ?> / <?php echo $metrics['total_tickets']; ?></div></div>
        <div class="metric-card"><div class="metric-title">Domínios</div><div class="metric-value"><?php echo $metrics['total_domains']; ?></div></div>
        <div class="metric-card"><div class="metric-title">Faturas por pagar</div><div class="metric-value"><?php echo $metrics['unpaid_invoices']; ?></div></div>
      </div>
    <?php elseif ($user['role'] === 'Suporte Financeira'): ?>
      <div class="metrics-grid">
        <div class="metric-card"><div class="metric-title">Total de Faturas</div><div class="metric-value"><?php echo $metrics['total_invoices']; ?></div></div>
        <div class="metric-card"><div class="metric-title">Por Pagar</div><div class="metric-value"><?php echo $metrics['unpaid_invoices']; ?></div></div>
        <div class="metric-card"><div class="metric-title">Pagas</div><div class="metric-value"><?php echo $metrics['paid_invoices']; ?></div></div>
      </div>
    <?php else: ?>
      <div class="metrics-grid">
        <div class="metric-card"><div class="metric-title">Tickets Abertos</div><div class="metric-value"><?php echo $metrics['open_tickets']; ?></div></div>
        <div class="metric-card"><div class="metric-title">Tickets Pendentes</div><div class="metric-value"><?php echo $metrics['pending_tickets']; ?></div></div>
        <div class="metric-card"><div class="metric-title">Clientes</div><div class="metric-value"><?php echo $metrics['total_clients']; ?></div></div>
      </div>
    <?php endif; ?>
    
    <?php
      $activities = [];
      try {
        $stmt = $pdo->query('SELECT u.first_name, u.last_name, l.type, l.message, l.created_at FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 5');
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (Throwable $e) {}
    ?>
    <div class="section-title">Atividade recente</div>
    <div class="activity-list">
      <?php if (empty($activities)): ?>
        <div class="activity-item"><div>Sem atividade recente.</div></div>
      <?php else: ?>
        <?php foreach ($activities as $act): ?>
          <div class="activity-item">
            <div>
              <strong><?php echo htmlspecialchars($act['type']); ?></strong> — <?php echo htmlspecialchars($act['message']); ?>
              <?php if (!empty($act['first_name'])): ?>
                <span class="meta">(<?php echo htmlspecialchars($act['first_name'].' '.$act['last_name']); ?>)</span>
              <?php endif; ?>
            </div>
            <div class="meta"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($act['created_at']))); ?></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../inc/footer.php'; ?>
