<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
requireLogin();
$user = currentUser();
$pdo = getDB();
if (!$user) { header('Location: logout.php'); exit; }

// Função segura para contagens
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

// Buscar dados recentes
$recentServices = [];
$recentInvoices = [];
$recentTickets = [];

try {
  if ($user['role'] === 'Cliente') {
    // Serviços recentes do cliente
    $stmt = $pdo->prepare('SELECT * FROM domains WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
    $stmt->execute([$user['id']]);
    $recentServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Faturas recentes
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
    $stmt->execute([$user['id']]);
    $recentInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tickets recentes
    $stmt = $pdo->prepare('SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 3');
    $stmt->execute([$user['id']]);
    $recentTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } else {
    // Para staff, mostrar dados gerais
    $stmt = $pdo->prepare('SELECT * FROM tickets ORDER BY created_at DESC LIMIT 5');
    $stmt->execute();
    $recentTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) {
  error_log('Error fetching dashboard data: ' . $e->getMessage());
}

// Métricas
$metrics = [];
if ($user['role'] === 'Gestor') {
  $metrics['clients'] = safeCount($pdo, "SELECT COUNT(*) FROM users WHERE role = 'Cliente'");
  $metrics['services'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains');
  $metrics['revenue'] = safeCount($pdo, "SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status = 'paid' AND MONTH(paid_at) = MONTH(CURRENT_DATE())");
  $metrics['tickets'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'open'");
} else {
  $metrics['services'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains WHERE user_id = ? AND status = "active"', [$user['id']]);
  $metrics['invoices'] = safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE user_id = ? AND status = 'unpaid'", [$user['id']]);
  $metrics['tickets'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status = 'open'", [$user['id']]);
  $metrics['spent'] = safeCount($pdo, "SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE user_id = ? AND status = 'paid'", [$user['id']]);
}

?>
<?php define('DASHBOARD_LAYOUT', true); ?>
<?php include __DIR__ . '/inc/header.php'; ?>

<style>
.modern-dashboard {
  max-width: 1400px;
  margin: 0 auto;
  padding: 32px 24px;
}

.welcome-section {
  margin-bottom: 40px;
}

.welcome-section h1 {
  font-size: 32px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 8px 0;
}

.welcome-section p {
  font-size: 16px;
  color: #666;
  margin: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 24px;
  margin-bottom: 40px;
}

.stat-card {
  background: white;
  border: 1px solid #e5e5e5;
  border-radius: 12px;
  padding: 24px;
  transition: all 0.2s ease;
}

.stat-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.stat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.stat-value {
  font-size: 32px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 4px 0;
}

.stat-label {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.stat-change {
  font-size: 13px;
  margin-top: 8px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
}

.stat-change.positive {
  color: #16a34a;
}

.stat-change.negative {
  color: #dc2626;
}

.content-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;
  margin-bottom: 40px;
}

@media (max-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
}

.card {
  background: white;
  border: 1px solid #e5e5e5;
  border-radius: 12px;
  padding: 0;
  overflow: hidden;
}

.card-header {
  padding: 20px 24px;
  border-bottom: 1px solid #e5e5e5;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-title {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
}

.card-action {
  font-size: 14px;
  color: #007dff;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s;
}

.card-action:hover {
  color: #0066cc;
}

.card-body {
  padding: 24px;
}

.table-responsive {
  overflow-x: auto;
}

.modern-table {
  width: 100%;
  border-collapse: collapse;
}

.modern-table thead th {
  text-align: left;
  padding: 12px 16px;
  font-size: 13px;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid #e5e5e5;
}

.modern-table tbody td {
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
  font-size: 14px;
  color: #1a1a1a;
}

.modern-table tbody tr:last-child td {
  border-bottom: none;
}

.modern-table tbody tr:hover {
  background: #f9fafb;
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.badge-success {
  background: #dcfce7;
  color: #16a34a;
}

.badge-warning {
  background: #fef3c7;
  color: #d97706;
}

.badge-danger {
  background: #fee2e2;
  color: #dc2626;
}

.badge-info {
  background: #e0f2fe;
  color: #0284c7;
}

.badge-secondary {
  background: #f3f4f6;
  color: #666;
}

.empty-state {
  text-align: center;
  padding: 48px 24px;
  color: #999;
}

.empty-state-icon {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state-text {
  font-size: 16px;
  color: #666;
  margin: 0;
}

.quick-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.action-btn {
  display: block;
  padding: 16px 20px;
  background: white;
  border: 2px solid #e5e5e5;
  border-radius: 10px;
  text-align: center;
  text-decoration: none;
  color: #1a1a1a;
  font-weight: 600;
  transition: all 0.2s ease;
}

.action-btn:hover {
  border-color: #007dff;
  color: #007dff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 125, 255, 0.15);
}

.action-btn.primary {
  background: #007dff;
  border-color: #007dff;
  color: white;
}

.action-btn.primary:hover {
  background: #0066cc;
  border-color: #0066cc;
  color: white;
}

.activity-item {
  padding: 16px 0;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 4px 0;
}

.activity-desc {
  font-size: 13px;
  color: #666;
  margin: 0;
}

.activity-time {
  font-size: 12px;
  color: #999;
  white-space: nowrap;
}
</style>

<div class="modern-dashboard">
  <!-- Welcome Section -->
  <div class="welcome-section">
    <h1>Bem-vindo de volta, <?php echo htmlspecialchars($user['first_name']); ?>! 👋</h1>
    <p>Aqui está uma visão geral da sua atividade recente</p>
  </div>

  <!-- Stats Grid -->
  <div class="stats-grid">
    <?php if ($user['role'] === 'Gestor'): ?>
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">👥</div>
        </div>
        <div class="stat-value"><?php echo $metrics['clients']; ?></div>
        <div class="stat-label">Total de Clientes</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">📦</div>
        </div>
        <div class="stat-value"><?php echo $metrics['services']; ?></div>
        <div class="stat-label">Serviços Ativos</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: #fef3c7; color: #d97706;">💰</div>
        </div>
        <div class="stat-value">€<?php echo number_format($metrics['revenue'], 2); ?></div>
        <div class="stat-label">Receita Este Mês</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: #ede9fe; color: #7c3aed;">🎫</div>
        </div>
        <div class="stat-value"><?php echo $metrics['tickets']; ?></div>
        <div class="stat-label">Tickets Abertos</div>
      </div>
    <?php else: ?>
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">✓</div>
        </div>
        <div class="stat-value"><?php echo $metrics['services']; ?></div>
        <div class="stat-label">Serviços Ativos</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: #fef3c7; color: #d97706;">📄</div>
        </div>
        <div class="stat-value"><?php echo $metrics['invoices']; ?></div>
        <div class="stat-label">Faturas Pendentes</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: #ede9fe; color: #7c3aed;">💬</div>
        </div>
        <div class="stat-value"><?php echo $metrics['tickets']; ?></div>
        <div class="stat-label">Tickets Abertos</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">💳</div>
        </div>
        <div class="stat-value">€<?php echo number_format($metrics['spent'], 2); ?></div>
        <div class="stat-label">Total Gasto</div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Content Grid -->
  <div class="content-grid">
    <!-- Main Content -->
    <div>
      <!-- Recent Services/Tickets -->
      <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
          <h2 class="card-title"><?php echo $user['role'] === 'Cliente' ? 'Serviços Recentes' : 'Tickets Recentes'; ?></h2>
          <a href="<?php echo $user['role'] === 'Cliente' ? '/services.php' : '/admin/tickets.php'; ?>" class="card-action">Ver todos →</a>
        </div>
        <div class="table-responsive">
          <?php if ($user['role'] === 'Cliente' && !empty($recentServices)): ?>
            <table class="modern-table">
              <thead>
                <tr>
                  <th>Serviço</th>
                  <th>Tipo</th>
                  <th>Estado</th>
                  <th>Data</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentServices as $service): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($service['domain_name'] ?? 'N/A'); ?></strong></td>
                    <td><?php echo htmlspecialchars($service['type'] ?? 'N/A'); ?></td>
                    <td>
                      <span class="badge badge-<?php echo $service['status'] === 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo htmlspecialchars($service['status'] ?? 'N/A'); ?>
                      </span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($service['created_at'])); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php elseif (!empty($recentTickets)): ?>
            <table class="modern-table">
              <thead>
                <tr>
                  <th>Assunto</th>
                  <th>Estado</th>
                  <th>Prioridade</th>
                  <th>Data</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentTickets as $ticket): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($ticket['subject'] ?? 'N/A'); ?></strong></td>
                    <td>
                      <span class="badge badge-<?php echo $ticket['status'] === 'open' ? 'warning' : 'success'; ?>">
                        <?php echo htmlspecialchars($ticket['status'] ?? 'N/A'); ?>
                      </span>
                    </td>
                    <td>
                      <span class="badge badge-<?php echo $ticket['priority'] === 'high' ? 'danger' : 'info'; ?>">
                        <?php echo htmlspecialchars($ticket['priority'] ?? 'normal'); ?>
                      </span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div class="empty-state">
              <div class="empty-state-icon">📭</div>
              <p class="empty-state-text">Nenhum item para exibir</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Recent Invoices (for clients) -->
      <?php if ($user['role'] === 'Cliente'): ?>
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Faturas Recentes</h2>
            <a href="/finance.php" class="card-action">Ver todas →</a>
          </div>
          <div class="table-responsive">
            <?php if (!empty($recentInvoices)): ?>
              <table class="modern-table">
                <thead>
                  <tr>
                    <th>Número</th>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Data</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentInvoices as $invoice): ?>
                    <tr>
                      <td><strong>#<?php echo htmlspecialchars($invoice['id']); ?></strong></td>
                      <td><?php echo htmlspecialchars($invoice['description'] ?? 'Fatura'); ?></td>
                      <td><strong>€<?php echo number_format($invoice['amount'], 2); ?></strong></td>
                      <td>
                        <span class="badge badge-<?php echo $invoice['status'] === 'paid' ? 'success' : ($invoice['status'] === 'unpaid' ? 'warning' : 'danger'); ?>">
                          <?php echo htmlspecialchars($invoice['status']); ?>
                        </span>
                      </td>
                      <td><?php echo date('d/m/Y', strtotime($invoice['created_at'])); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php else: ?>
              <div class="empty-state">
                <div class="empty-state-icon">💳</div>
                <p class="empty-state-text">Nenhuma fatura encontrada</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div>
      <!-- Quick Actions -->
      <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
          <h2 class="card-title">Ações Rápidas</h2>
        </div>
        <div class="card-body">
          <div class="quick-actions">
            <?php if ($user['role'] === 'Gestor'): ?>
              <a href="/admin/customers.php" class="action-btn primary">
                <div style="margin-bottom: 8px; font-size: 20px;">👥</div>
                Clientes
              </a>
              <a href="/admin/services.php" class="action-btn">
                <div style="margin-bottom: 8px; font-size: 20px;">📦</div>
                Serviços
              </a>
              <a href="/admin/tickets.php" class="action-btn">
                <div style="margin-bottom: 8px; font-size: 20px;">🎫</div>
                Tickets
              </a>
              <a href="/admin/payments.php" class="action-btn">
                <div style="margin-bottom: 8px; font-size: 20px;">💰</div>
                Pagamentos
              </a>
            <?php else: ?>
              <a href="/support.php" class="action-btn primary">
                <div style="margin-bottom: 8px; font-size: 20px;">➕</div>
                Novo Ticket
              </a>
              <a href="/domains.php" class="action-btn">
                <div style="margin-bottom: 8px; font-size: 20px;">🌐</div>
                Domínios
              </a>
              <a href="/finance.php" class="action-btn">
                <div style="margin-bottom: 8px; font-size: 20px;">💳</div>
                Faturas
              </a>
              <a href="/services.php" class="action-btn">
                <div style="margin-bottom: 8px; font-size: 20px;">📦</div>
                Serviços
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- System Status -->
      <div class="card">
        <div class="card-header">
          <h2 class="card-title">Estado do Sistema</h2>
        </div>
        <div class="card-body">
          <div class="activity-item">
            <div class="activity-icon" style="background: #dcfce7; color: #16a34a;">✓</div>
            <div class="activity-content">
              <h4 class="activity-title">Todos os Sistemas Operacionais</h4>
              <p class="activity-desc">Funcionando normalmente</p>
            </div>
          </div>
          <div class="activity-item">
            <div class="activity-icon" style="background: #e0f2fe; color: #0284c7;">🔒</div>
            <div class="activity-content">
              <h4 class="activity-title">Segurança</h4>
              <p class="activity-desc">Proteção ativa</p>
            </div>
          </div>
          <div class="activity-item">
            <div class="activity-icon" style="background: #ede9fe; color: #7c3aed;">📊</div>
            <div class="activity-content">
              <h4 class="activity-title">Performance</h4>
              <p class="activity-desc">Excelente</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>

