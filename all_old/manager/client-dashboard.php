<?php
/**
 * CyberCore Client Dashboard
 * Dashboard específico para clientes
 */

require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/dashboard_helper.php';

// Ensure only clients
checkRole(['Cliente']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;
$pdo = getDB();

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

// Fetch user's own data
try {
  // Services query - user's services only
  $recentServices = safeFetch($pdo, 
    'SELECT * FROM domains 
     WHERE user_id = ? 
     ORDER BY created_at DESC LIMIT 5',
    [$user['id']]
  );
  
  // Invoices query - user's invoices only
  $recentInvoices = safeFetch($pdo,
    'SELECT * FROM invoices 
     WHERE user_id = ? 
     ORDER BY created_at DESC LIMIT 5',
    [$user['id']]
  );
  
  // Tickets query - user's tickets only
  $recentTickets = safeFetch($pdo,
    'SELECT * FROM tickets 
     WHERE user_id = ? 
     ORDER BY created_at DESC LIMIT 5',
    [$user['id']]
  );
  
} catch (Throwable $e) {
  error_log('Error fetching client dashboard data: ' . $e->getMessage());
}

// Calculate client metrics
$metrics = [
  'services' => 0,
  'activeServices' => 0,
  'invoices' => 0,
  'unpaidAmount' => 0,
  'tickets' => 0,
  'nextRenewal' => 'N/A',
];

try {
  // Count user's services
  $metrics['services'] = safeCount($pdo, 
    'SELECT COUNT(*) FROM domains WHERE user_id = ?', 
    [$user['id']]
  );
  
  $metrics['activeServices'] = safeCount($pdo, 
    'SELECT COUNT(*) FROM domains WHERE user_id = ? AND status = "active"', 
    [$user['id']]
  );
  
  // Count user's invoices
  $metrics['invoices'] = safeCount($pdo, 
    "SELECT COUNT(*) FROM invoices WHERE user_id = ? AND status = 'unpaid'", 
    [$user['id']]
  );
  
  // Count user's tickets
  $metrics['tickets'] = safeCount($pdo, 
    "SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status = 'open'", 
    [$user['id']]
  );
  
  // Calculate unpaid amount
  $stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(amount), 0) 
     FROM invoices 
     WHERE user_id = ? AND status = 'unpaid'"
  );
  $stmt->execute([$user['id']]);
  $metrics['unpaidAmount'] = (float)$stmt->fetchColumn();
  
  // Get next renewal date
  $stmt = $pdo->prepare(
    "SELECT MIN(renewal_date) 
     FROM domains 
     WHERE user_id = ? 
     AND status = 'active' 
     AND renewal_date > NOW()"
  );
  $stmt->execute([$user['id']]);
  $nextRenewal = $stmt->fetchColumn();
  
  if ($nextRenewal) {
    $renewalDate = new DateTime($nextRenewal);
    $now = new DateTime();
    $diff = $now->diff($renewalDate);
    
    if ($diff->days <= 30) {
      $metrics['nextRenewal'] = $renewalDate->format('d/m/Y') . ' (' . $diff->days . ' dias)';
    } else {
      $metrics['nextRenewal'] = $renewalDate->format('d/m/Y');
    }
  }
  
} catch (Exception $e) {
  error_log('Error calculating client metrics: ' . $e->getMessage());
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
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Serviços Ativos</div>
      <div class="stat-value"><?php echo $metrics['activeServices']; ?></div>
      <div class="stat-footer">de <?php echo $metrics['services']; ?> total</div>
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
      <div class="stat-label">Faturas por Pagar</div>
      <div class="stat-value"><?php echo $metrics['invoices']; ?></div>
      <?php if ($metrics['unpaidAmount'] > 0): ?>
      <div class="stat-footer"><?php echo formatCurrency($metrics['unpaidAmount']); ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Tickets Abertos</div>
      <div class="stat-value"><?php echo $metrics['tickets']; ?></div>
      <div class="stat-footer">
        <a href="/support.php" style="color: #10b981; text-decoration: none; font-size: 13px;">
          Ver tickets →
        </a>
      </div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="12 6 12 12 16 14"></polyline>
      </svg>
    </div>
    <div class="stat-content">
      <div class="stat-label">Próxima Renovação</div>
      <div class="stat-value" style="font-size: 18px;">
        <?php echo htmlspecialchars($metrics['nextRenewal']); ?>
      </div>
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
          <?php foreach ($recentServices as $service): ?>
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
          <?php foreach ($recentInvoices as $invoice): ?>
            <div class="invoice-item">
              <div class="invoice-info">
                <div class="invoice-number">
                  #<?php echo htmlspecialchars($invoice['id']); ?>
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
      Tickets de Suporte
    </h2>
    <a href="/support.php" class="btn-primary">Abrir Ticket</a>
  </div>
  <div class="card-body">
    <?php if (empty($recentTickets)): ?>
      <div class="empty-state">
        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <p>Nenhum ticket encontrado</p>
        <a href="/support.php" class="btn-secondary">Abrir Primeiro Ticket</a>
      </div>
    <?php else: ?>
      <div class="ticket-list">
        <?php foreach ($recentTickets as $ticket): ?>
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
echo renderDashboardLayout('Bem-vindo', 'Aqui está uma visão geral da sua conta', $content, 'dashboard');
?>
