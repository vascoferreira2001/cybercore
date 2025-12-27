<?php
/**
 * CyberCore Client Dashboard
 * Professional client area with real-time data integration
 */

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/dashboard_helper.php';

// Ensure user is authenticated
checkRole(['Cliente','Suporte ao Cliente','Suporte Financeiro','Suporte Técnico','Gestor']);
$user = currentUser();
$pdo = getDB();

if (!$user) {
  header('Location: logout.php');
  exit;
}

// Session security check
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user['id']) {
  session_destroy();
  header('Location: login.php');
  exit;
}

// Update last activity timestamp
try {
  $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
  $stmt->execute([$user['id']]);
} catch (Exception $e) {
  error_log('Failed to update last login: ' . $e->getMessage());
}

// Generate unique Client ID
$clientId = 'CYC#' . str_pad($user['id'], 5, '0', STR_PAD_LEFT);
$profileUrl = '/profile.php';

// Sidebar active key (match menu_config keys)
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/dashboard.php', PHP_URL_PATH);
$sidebarActive = basename($currentPath, '.php');

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
$activityLog = [];

// Fetch recent data based on user role
try {
  $isManager = ($user['role'] !== 'Cliente');
  
  // Services query
  if ($isManager) {
    $recentServices = safeFetch($pdo, 
      'SELECT d.*, u.first_name, u.last_name 
       FROM domains d 
       LEFT JOIN users u ON d.user_id = u.id 
       ORDER BY d.created_at DESC LIMIT 5'
    );
  } else {
    $recentServices = safeFetch($pdo,
      'SELECT * FROM domains 
       WHERE user_id = ? 
       ORDER BY created_at DESC LIMIT 5',
      [$user['id']]
    );
  }
  
  // Invoices query
  if ($isManager) {
    $recentInvoices = safeFetch($pdo,
      'SELECT i.*, u.first_name, u.last_name 
       FROM invoices i 
       LEFT JOIN users u ON i.user_id = u.id 
       ORDER BY i.created_at DESC LIMIT 5'
    );
  } else {
    $recentInvoices = safeFetch($pdo,
      'SELECT * FROM invoices 
       WHERE user_id = ? 
       ORDER BY created_at DESC LIMIT 5',
      [$user['id']]
    );
  }
  
  // Tickets query
  if ($isManager) {
    $recentTickets = safeFetch($pdo,
      'SELECT t.*, u.first_name, u.last_name 
       FROM tickets t 
       LEFT JOIN users u ON t.user_id = u.id 
       ORDER BY t.created_at DESC LIMIT 5'
    );
  } else {
    $recentTickets = safeFetch($pdo,
      'SELECT * FROM tickets 
       WHERE user_id = ? 
       ORDER BY created_at DESC LIMIT 5',
      [$user['id']]
    );
  }
  
  // Activity log for current user
  if (!$isManager) {
    $activityLog = safeFetch($pdo,
      'SELECT * FROM logs 
       WHERE user_id = ? 
       ORDER BY created_at DESC LIMIT 10',
      [$user['id']]
    );
  }
  
} catch (Throwable $e) {
  error_log('Error fetching dashboard data: ' . $e->getMessage());
}


// Calculate metrics based on user role
$metrics = [
  'services' => 0,
  'invoices' => 0,
  'tickets' => 0,
  'clients' => 0,
  'nextRenewal' => 'N/A',
  'revenue' => 0,
  'unpaidAmount' => 0,
  'activeServices' => 0
];

try {
  $isManager = ($user['role'] !== 'Cliente');
  
  if ($isManager) {
    // Manager metrics
    $metrics['clients'] = safeCount($pdo, "SELECT COUNT(*) FROM users WHERE role = 'Cliente'");
    $metrics['services'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains');
    $metrics['activeServices'] = safeCount($pdo, 'SELECT COUNT(*) FROM domains WHERE status = "active"');
    $metrics['invoices'] = safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'");
    $metrics['tickets'] = safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'open'");
    
    // Calculate total revenue this month
    $stmt = $pdo->prepare(
      "SELECT COALESCE(SUM(amount), 0) 
       FROM invoices 
       WHERE status = 'paid' 
       AND MONTH(paid_at) = MONTH(CURRENT_DATE()) 
       AND YEAR(paid_at) = YEAR(CURRENT_DATE())"
    );
    $stmt->execute();
    $metrics['revenue'] = (float)$stmt->fetchColumn();
    
  } else {
    // Client metrics
    $metrics['services'] = safeCount($pdo, 
      'SELECT COUNT(*) FROM domains WHERE user_id = ?', 
      [$user['id']]
    );
    $metrics['activeServices'] = safeCount($pdo, 
      'SELECT COUNT(*) FROM domains WHERE user_id = ? AND status = "active"', 
      [$user['id']]
    );
    $metrics['invoices'] = safeCount($pdo, 
      "SELECT COUNT(*) FROM invoices WHERE user_id = ? AND status = 'unpaid'", 
      [$user['id']]
    );
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
  }
  
} catch (Exception $e) {
  error_log('Error calculating metrics: ' . $e->getMessage());
}

// Store metrics in session for quick access
$_SESSION['dashboard_metrics'] = $metrics;
$_SESSION['dashboard_last_updated'] = time();

// Calculate unread notifications
$unreadNotifications = 0;
try {
  $unreadNotifications = safeCount($pdo,
    "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
    [$user['id']]
  );
} catch (Exception $e) {
  error_log('Error fetching notifications: ' . $e->getMessage());
}

// User display name
$userDisplayName = trim($user['first_name'] . ' ' . $user['last_name']);
if (empty($userDisplayName)) {
  $userDisplayName = $user['email'];
}

// Account status badge
$accountStatus = 'active'; // Default
if (isset($user['status'])) {
  $accountStatus = $user['status'];
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
    'cancelled' => 'badge-danger',
    'cancelado' => 'badge-danger',
    'overdue' => 'badge-danger',
    'atrasado' => 'badge-danger'
  ];
  
  $status = strtolower($status);
  return isset($badges[$status]) ? $badges[$status] : 'badge-secondary';
}
?>

<?php
$GLOBALS['currentUser'] = $user;
$GLOBALS['unreadNotifications'] = $unreadNotifications;

ob_start();
?>
    <div class="dashboard-header">
      <div>
        <h1 class="page-title">Bem-vindo, <?php echo htmlspecialchars($user['first_name']); ?>! 👋</h1>
        <p class="page-subtitle">Aqui está uma visão geral da sua conta</p>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <?php if ($user['role'] !== 'Cliente'): ?>
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <?php else: ?>
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <?php endif; ?>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">
            <?php echo $user['role'] !== 'Cliente' ? 'Total de Clientes' : 'Serviços Ativos'; ?>
          </div>
          <div class="stat-value" data-stat="<?php echo $user['role'] !== 'Cliente' ? 'clients' : 'services'; ?>">
            <?php echo $user['role'] !== 'Cliente' ? $metrics['clients'] : $metrics['activeServices']; ?>
          </div>
          <?php if ($user['role'] === 'Cliente'): ?>
          <div class="stat-footer">de <?php echo $metrics['services']; ?> total</div>
          <?php endif; ?>
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
            <div class="stat-label">
              <?php echo $user['role'] !== 'Cliente' ? 'Faturas Pendentes' : 'Faturas por Pagar'; ?>
            </div>
            <div class="stat-value" data-stat="invoices"><?php echo $metrics['invoices']; ?></div>
            <?php if ($user['role'] === 'Cliente' && $metrics['unpaidAmount'] > 0): ?>
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
            <div class="stat-value" data-stat="tickets"><?php echo $metrics['tickets']; ?></div>
            <div class="stat-footer">
              <a href="/support.php" style="color: #10b981; text-decoration: none; font-size: 13px;">
                Ver tickets →
              </a>
            </div>
          </div>
        </div>

        <?php if ($user['role'] !== 'Cliente'): ?>
        <div class="stat-card">
          <div class="stat-icon purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="1" x2="12" y2="23"></line>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>
          <div class="stat-content">
            <div class="stat-label">Receita Este Mês</div>
            <div class="stat-value" style="font-size: 24px;"><?php echo formatCurrency($metrics['revenue']); ?></div>
          </div>
        </div>
        <?php else: ?>
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
        <?php endif; ?>
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
                <?php foreach (array_slice($recentServices, 0, 5) as $service): ?>
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
                        <?php echo htmlspecialchars($service['domain_name'] ?? $service['name'] ?? 'Serviço'); ?>
                      </div>
                      <div class="service-meta">
                        <?php 
                        echo htmlspecialchars($service['type'] ?? 'N/A');
                        if (isset($service['first_name']) && $user['role'] !== 'Cliente') {
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
                <?php foreach (array_slice($recentInvoices, 0, 5) as $invoice): ?>
                  <div class="invoice-item">
                    <div class="invoice-info">
                      <div class="invoice-number">
                        #<?php echo htmlspecialchars($invoice['id']); ?>
                        <?php if (isset($invoice['first_name']) && $user['role'] !== 'Cliente'): ?>
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
              <?php foreach (array_slice($recentTickets, 0, 5) as $ticket): ?>
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
                      <?php if (isset($ticket['first_name']) && $user['role'] !== 'Cliente'): ?>
                        <span>•</span>
                        <span><?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?></span>
                      <?php endif; ?>
                      <span>•</span>
                      <span><?php echo formatDate($ticket['created_at'] ?? null, 'd/m/Y H:i'); ?></span>
                      <?php if (isset($ticket['updated_at']) && $ticket['updated_at'] != $ticket['created_at']): ?>
                        <span>•</span>
                        <span>Atualizado: <?php echo formatDate($ticket['updated_at'], 'd/m/Y H:i'); ?></span>
                      <?php endif; ?>
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
    </div>

<?php
$content = ob_get_clean();
echo renderDashboardLayout('Dashboard', 'Visão geral da sua conta', $content, $sidebarActive);
echo '<script src="/assets/js/pages/dashboard-modern.js"></script>';
?>

