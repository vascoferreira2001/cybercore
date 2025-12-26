<?php
/**
 * CyberCore Admin Dashboard
 * Unified dashboard for all admin roles: Gestor, Suporte ao Cliente, Suporte Financeiro, Suporte Técnico
 */

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';

// Ensure user is authenticated and is an admin role
checkRole(['Gestor', 'Suporte ao Cliente', 'Suporte Financeiro', 'Suporte Técnico']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;
$pdo = getDB();

// Set admin dashboard layout flag
define('ADMIN_DASHBOARD', true);

// Safe count function with error handling
function safeCount($pdo, $sql, $params = []) {
  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchColumn();
    return $result !== false ? (int)$result : 0;
  } catch (PDOException $e) {
    error_log('Dashboard metric error: ' . $e->getMessage());
    return 0;
  }
}

// Safe fetch function for multiple rows
function safeFetch($pdo, $sql, $params = [], $limit = 10) {
  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    error_log('Dashboard fetch error: ' . $e->getMessage());
    return [];
  }
}

// Initialize data arrays
$recentServices = [];
$recentInvoices = [];
$recentTickets = [];
$systemMetrics = [];

// Fetch data for admin dashboard
try {
  // Services query - all services
  $recentServices = safeFetch($pdo, 
    'SELECT d.*, u.first_name, u.last_name, u.email
     FROM domains d 
     LEFT JOIN users u ON d.user_id = u.id 
     ORDER BY d.created_at DESC LIMIT 10'
  );
  
  // Invoices query - all invoices
  $recentInvoices = safeFetch($pdo,
    'SELECT i.*, u.first_name, u.last_name, u.email
     FROM invoices i 
     LEFT JOIN users u ON i.user_id = u.id 
     ORDER BY i.created_at DESC LIMIT 10'
  );
  
  // Tickets query - all tickets
  $recentTickets = safeFetch($pdo,
    'SELECT t.*, u.first_name, u.last_name, u.email
     FROM tickets t 
     LEFT JOIN users u ON t.user_id = u.id 
     ORDER BY t.created_at DESC LIMIT 10'
  );
  
} catch (Throwable $e) {
  error_log('Error fetching admin dashboard data: ' . $e->getMessage());
}

// Calculate admin metrics
$metrics = [
  'totalClients' => 0,
  'totalServices' => 0,
  'activeServices' => 0,
  'unpaidInvoices' => 0,
  'totalInvoices' => 0,
  'openTickets' => 0,
  'totalTickets' => 0,
  'thisMonthRevenue' => 0,
  'totalRevenue' => 0,
];

try {
  // Count clients
  $metrics['totalClients'] = safeCount($pdo, "SELECT COUNT(*) FROM users WHERE role = 'Cliente'");
  
  // Count services
  $metrics['totalServices'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains');
  $metrics['activeServices'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains WHERE status = "active"');
  
  // Count invoices
  $metrics['totalInvoices'] = safeCount($pdo, 'SELECT COUNT(*) FROM invoices');
  $metrics['unpaidInvoices'] = safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'");
  
  // Count tickets
  $metrics['totalTickets'] = safeCount($pdo, 'SELECT COUNT(*) FROM tickets');
  $metrics['openTickets'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'open'");
  
  // Calculate this month revenue
  $stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(amount), 0) 
     FROM invoices 
     WHERE status = 'paid' 
     AND MONTH(paid_at) = MONTH(CURRENT_DATE()) 
     AND YEAR(paid_at) = YEAR(CURRENT_DATE())"
  );
  $stmt->execute();
  $metrics['thisMonthRevenue'] = (float)$stmt->fetchColumn();
  
  // Calculate total revenue
  $stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status = 'paid'"
  );
  $stmt->execute();
  $metrics['totalRevenue'] = (float)$stmt->fetchColumn();
  
} catch (Exception $e) {
  error_log('Error calculating admin metrics: ' . $e->getMessage());
}

// Format currency helper
function formatCurrency($amount) {
  return '€' . number_format((float)$amount, 2, ',', '.');
}

// Format date helper  
function formatDate($date, $format = 'd/m/Y') {
  if (!$date) return 'N/A';
  try {
    $dt = new DateTime($date);
    return $dt->format($format);
  } catch (Exception $e) {
    return 'N/A';
  }
}

// Get status badge class
function getStatusBadge($status) {
  $badges = [
    'active' => 'badge-success',
    'ativo' => 'badge-success',
    'paid' => 'badge-success',
    'pago' => 'badge-success',
    'open' => 'badge-warning',
    'aberto' => 'badge-warning',
    'unpaid' => 'badge-warning',
    'pending' => 'badge-warning',
    'pendente' => 'badge-warning',
    'closed' => 'badge-secondary',
    'fechado' => 'badge-secondary',
    'suspended' => 'badge-danger',
    'suspenso' => 'badge-danger',
  ];
  
  $status = strtolower($status);
  return isset($badges[$status]) ? $badges[$status] : 'badge-secondary';
}

// Build dashboard content
ob_start();
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
        <circle cx="9" cy="7" r="4"></circle>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Total de Clientes</div>
      <div class="stat-value"><?php echo $metrics['totalClients']; ?></div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Serviços Ativos</div>
      <div class="stat-value"><?php echo $metrics['activeServices']; ?></div>
      <div class="stat-footer">de <?php echo $metrics['totalServices']; ?> total</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon orange">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
        <line x1="2" y1="10" x2="22" y2="10"></line>
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Faturas Pendentes</div>
      <div class="stat-value"><?php echo $metrics['unpaidInvoices']; ?></div>
      <div class="stat-footer">de <?php echo $metrics['totalInvoices']; ?> total</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon red">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Tickets Abertos</div>
      <div class="stat-value"><?php echo $metrics['openTickets']; ?></div>
      <div class="stat-footer">de <?php echo $metrics['totalTickets']; ?> total</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="12" y1="1" x2="12" y2="23"></line>
        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Receita Este Mês</div>
      <div class="stat-value" style="font-size: 24px;"><?php echo formatCurrency($metrics['thisMonthRevenue']); ?></div>
    </div>
  </div>
</div>

<!-- Main Grid -->
<div class="content-grid">
  <!-- Recent Services -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">
        <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        </svg>
        Serviços Recentes
      </h2>
      <a href="/services.php" class="card-action">Ver todos →</a>
    </div>
    <div class="card-body">
      <?php if (empty($recentServices)): ?>
        <div class="empty-state">
          <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
          </svg>
          <p>Nenhum serviço encontrado</p>
        </div>
      <?php else: ?>
        <div class="service-list">
          <?php foreach (array_slice($recentServices, 0, 10) as $service): ?>
            <div class="service-item">
              <div class="service-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="2" y1="12" x2="22" y2="12"></line>
                  <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
              </div>
              <div class="service-info">
                <div class="service-name">
                  <?php echo htmlspecialchars($service['domain_name'] ?? $service['domain'] ?? 'Serviço'); ?>
                </div>
                <div class="service-meta">
                  <?php 
                  echo htmlspecialchars($service['type'] ?? 'N/A');
                  if (isset($service['first_name'])) {
                    echo ' • ' . htmlspecialchars($service['first_name'] . ' ' . $service['last_name']);
                  }
                  if (isset($service['renewal_date'])) {
                    echo ' • Renova: ' . formatDate($service['renewal_date']);
                  }
                  ?>
                </div>
              </div>
              <span class="badge <?php echo getStatusBadge($service['status'] ?? 'N/A'); ?>">
                <?php echo htmlspecialchars($service['status'] ?? 'N/A'); ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Invoices -->
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">
        <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="2" y="5" width="20" height="14" rx="2"></rect>
          <line x1="2" y1="10" x2="22" y2="10"></line>
        </svg>
        Faturas Recentes
      </h2>
      <a href="/finance.php" class="card-action">Ver todas →</a>
    </div>
    <div class="card-body">
      <?php if (empty($recentInvoices)): ?>
        <div class="empty-state">
          <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
            <line x1="2" y1="10" x2="22" y2="10"></line>
          </svg>
          <p>Nenhuma fatura encontrada</p>
        </div>
      <?php else: ?>
        <div class="invoice-list">
          <?php foreach (array_slice($recentInvoices, 0, 10) as $invoice): ?>
            <div class="invoice-item">
              <div class="invoice-info">
                <div class="invoice-number">
                  #<?php echo htmlspecialchars($invoice['id']); ?>
                  <?php if (isset($invoice['first_name'])): ?>
                    <span style="font-weight: normal; font-size: 12px; color: #666;">
                      • <?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="invoice-date">
                  <?php 
                  echo formatDate($invoice['created_at'] ?? null);
                  if (isset($invoice['due_date'])) {
                    echo ' • Vence: ' . formatDate($invoice['due_date']);
                  }
                  ?>
                </div>
              </div>
              <div class="invoice-amount"><?php echo formatCurrency($invoice['amount'] ?? 0); ?></div>
              <span class="badge <?php echo getStatusBadge($invoice['status'] ?? ''); ?>">
                <?php echo htmlspecialchars($invoice['status'] ?? 'N/A'); ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Tickets Section -->
<div class="card">
  <div class="card-header">
    <h2 class="card-title">
      <svg class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
      </svg>
      Tickets Recentes
    </h2>
  </div>
  <div class="card-body">
    <?php if (empty($recentTickets)): ?>
      <div class="empty-state">
        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <p>Nenhum ticket encontrado</p>
      </div>
    <?php else: ?>
      <div class="ticket-list">
        <?php foreach (array_slice($recentTickets, 0, 10) as $ticket): ?>
          <div class="ticket-item">
            <div class="ticket-status-indicator <?php echo ($ticket['status'] ?? '') === 'open' ? 'status-open' : 'status-closed'; ?>"></div>
            <div class="ticket-content">
              <div class="ticket-subject">
                <?php echo htmlspecialchars($ticket['subject'] ?? 'Sem assunto'); ?>
                <?php if (isset($ticket['priority']) && $ticket['priority'] === 'high'): ?>
                  <span style="color: #ef4444; font-size: 12px;">🔥</span>
                <?php endif; ?>
              </div>
              <div class="ticket-meta">
                <span>Ticket #<?php echo $ticket['id']; ?></span>
                <?php if (isset($ticket['first_name'])): ?>
                  <span>•</span>
                  <span><?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?></span>
                <?php endif; ?>
                <span>•</span>
                <span><?php echo formatDate($ticket['created_at'] ?? null, 'd/m/Y H:i'); ?></span>
              </div>
            </div>
            <span class="badge <?php echo getStatusBadge($ticket['status'] ?? ''); ?>">
              <?php echo htmlspecialchars($ticket['status'] ?? 'N/A'); ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
echo renderDashboardLayout('Dashboard Administrativo', 'Visão geral completa do sistema', $content, 'dashboard');
?>
