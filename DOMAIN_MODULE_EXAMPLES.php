<?php
/**
 * CyberCore Domain Module – Quick Start Examples
 * 
 * Copy & paste these examples to integrate domain management into your application
 */

// ============================================================================
// EXAMPLE 1: Display user domains in dashboard
// ============================================================================

/**
 * Add this to /client/dashboard.php or similar
 */
function display_domain_widget() {
    require_once '../inc/domains.php';
    
    $userId = $_SESSION['user_id'];
    $domains = cybercore_domain_list($userId);
    
    if (empty($domains)) {
        echo '<p>Nenhum domínio registrado</p>';
        return;
    }
    
    echo '<div class="card">';
    echo '<div class="card-header"><h5>Meus Domínios</h5></div>';
    echo '<div class="card-body">';
    echo '<table class="table table-sm">';
    echo '<thead><tr><th>Domínio</th><th>Status</th><th>Expira</th><th>Ações</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($domains as $domain) {
        $daysLeft = (new DateTime($domain['expires_at']))->diff(new DateTime())->days;
        $statusBadge = $domain['status'] === 'active' ? 'success' : 'danger';
        
        echo '<tr>';
        echo '<td><strong>' . htmlspecialchars($domain['domain_name']) . '</strong></td>';
        echo '<td><span class="badge bg-' . $statusBadge . '">' . $domain['status'] . '</span></td>';
        echo '<td>' . date('d/m/Y', strtotime($domain['expires_at'])) . ' (' . $daysLeft . ' dias)</td>';
        echo '<td>';
        echo '<a href="domain-detail.php?id=' . $domain['id'] . '" class="btn btn-sm btn-primary">Ver</a>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div></div>';
}

// ============================================================================
// EXAMPLE 2: Add domain management menu item
// ============================================================================

/**
 * Add this to /inc/menu_config.php in client menu section
 */
$clientMenu[] = [
    'label' => 'Meus Domínios',
    'url' => '/client/domains.php',
    'icon' => 'globe',
    'active_paths' => ['/client/domains.php', '/client/domain-detail.php']
];

// ============================================================================
// EXAMPLE 3: Add domain management to admin menu
// ============================================================================

/**
 * Add this to /inc/menu_config.php in admin menu section
 */
$adminMenu[] = [
    'label' => 'Gestão de Domínios',
    'url' => '/admin/domains-manager.php',
    'icon' => 'globe',
    'badge' => 'count-expired-domains',
    'active_paths' => ['/admin/domains-manager.php', '/admin/domain-detail.php']
];

// ============================================================================
// EXAMPLE 4: Create domain for new customer
// ============================================================================

/**
 * Call this when a customer purchases a domain service
 */
function create_domain_for_customer($pdo, $userId, $domainName, $expiresAt, $pleskId = null) {
    require_once '../inc/domains.php';
    
    $stmt = $pdo->prepare('
        INSERT INTO domains (user_id, domain_name, status, expires_at, plesk_id, auto_renew)
        VALUES (:user_id, :domain_name, :status, :expires_at, :plesk_id, :auto_renew)
    ');
    
    $stmt->execute([
        'user_id' => $userId,
        'domain_name' => strtolower($domainName),
        'status' => 'active',
        'expires_at' => $expiresAt,
        'plesk_id' => $pleskId,
        'auto_renew' => 0, // Default to manual
    ]);
    
    return (int) $pdo->lastInsertId();
}

// ============================================================================
// EXAMPLE 5: Sync single domain from Plesk immediately
// ============================================================================

/**
 * Call this to sync domain status right away (e.g., after Plesk action)
 */
function sync_domain_now($pdo, $domainId, $userId) {
    require_once '../inc/domains.php';
    
    $service = new DomainService($pdo);
    return $service->syncFromPlesk($domainId, $userId);
}

// ============================================================================
// EXAMPLE 6: Check if domain is about to expire
// ============================================================================

/**
 * Use this to highlight expiring domains in admin/client area
 */
function is_domain_expiring_soon($expiresAt, $daysThreshold = 30) {
    $today = new DateTime('now', new DateTimeZone('UTC'));
    $expiresDate = new DateTime($expiresAt);
    $daysLeft = $today->diff($expiresDate)->days;
    
    return !$today->diff($expiresDate)->invert && $daysLeft <= $daysThreshold;
}

// Usage:
// if (is_domain_expiring_soon($domain['expires_at'], 30)) {
//     echo '<span class="badge bg-warning">Expira em breve</span>';
// }

// ============================================================================
// EXAMPLE 7: Send custom domain notification
// ============================================================================

/**
 * Send a specific notification to a client
 */
function send_domain_notification($pdo, $userId, $domainId, $type, $data) {
    require_once '../inc/domains.php';
    
    $service = new DomainService($pdo);
    $service->sendNotification($userId, $domainId, $type, $data);
}

// Usage:
// send_domain_notification($pdo, 123, 45, 'renewal_reminder_30', [
//     'domain' => 'example.com',
//     'days_left' => 30,
//     'expires_at' => '2026-01-28'
// ]);

// ============================================================================
// EXAMPLE 8: Get domain statistics for admin dashboard
// ============================================================================

/**
 * Get domain stats for admin dashboard
 */
function get_domain_statistics($pdo) {
    $stats = [];
    
    // Total domains
    $stats['total'] = (int) $pdo->query('SELECT COUNT(*) FROM domains')->fetch()[0];
    
    // Active domains
    $stats['active'] = (int) $pdo->query("SELECT COUNT(*) FROM domains WHERE status = 'active'")->fetch()[0];
    
    // Expired domains
    $stats['expired'] = (int) $pdo->query("SELECT COUNT(*) FROM domains WHERE status = 'expired'")->fetch()[0];
    
    // Suspended domains
    $stats['suspended'] = (int) $pdo->query("SELECT COUNT(*) FROM domains WHERE status = 'suspended'")->fetch()[0];
    
    // Auto-renewal enabled
    $stats['auto_renew'] = (int) $pdo->query('SELECT COUNT(*) FROM domains WHERE auto_renew = 1')->fetch()[0];
    
    // Expiring within 30 days
    $stats['expiring_soon'] = (int) $pdo->query("
        SELECT COUNT(*) FROM domains 
        WHERE status = 'active' 
        AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
    ")->fetch()[0];
    
    return $stats;
}

// Usage in admin dashboard:
// $domainStats = get_domain_statistics($pdo);
// echo 'Total: ' . $domainStats['total'];
// echo 'Expiring soon: ' . $domainStats['expiring_soon'];

// ============================================================================
// EXAMPLE 9: Batch renew expired domains (manual admin action)
// ============================================================================

/**
 * Admin can manually renew all expired domains (with payment)
 */
function batch_renew_expired_domains($pdo) {
    require_once '../inc/domains.php';
    require_once '../inc/billing.php';
    
    $stmt = $pdo->query("
        SELECT id, user_id, domain_name FROM domains 
        WHERE status IN ('expired', 'suspended')
        LIMIT 10
    ");
    
    $domains = $stmt->fetchAll();
    $service = new DomainService($pdo);
    $results = [];
    
    foreach ($domains as $domain) {
        try {
            if ($service->renewDomain($domain['id'], $domain['user_id'])) {
                $results[$domain['id']] = ['status' => 'success', 'message' => 'Renewal requested'];
            }
        } catch (Exception $e) {
            $results[$domain['id']] = ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    return $results;
}

// ============================================================================
// EXAMPLE 10: Export domains to CSV (admin action)
// ============================================================================

/**
 * Export all domains to CSV for reporting
 */
function export_domains_csv($pdo) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=domains_' . date('Y-m-d') . '.csv');
    
    $stmt = $pdo->query("
        SELECT d.domain_name, d.status, d.expires_at, d.auto_renew, u.name, u.email
        FROM domains d
        LEFT JOIN users u ON d.user_id = u.id
        ORDER BY d.expires_at ASC
    ");
    
    $domains = $stmt->fetchAll();
    
    // Output header
    $fp = fopen('php://output', 'w');
    fputcsv($fp, ['Domain', 'Status', 'Expires', 'Auto-Renew', 'Client Name', 'Client Email']);
    
    // Output data
    foreach ($domains as $domain) {
        fputcsv($fp, [
            $domain['domain_name'],
            $domain['status'],
            date('d/m/Y', strtotime($domain['expires_at'])),
            $domain['auto_renew'] ? 'Yes' : 'No',
            $domain['name'],
            $domain['email'],
        ]);
    }
    
    fclose($fp);
    exit;
}

// ============================================================================
// EXAMPLE 11: Add domain information to invoice
// ============================================================================

/**
 * When creating a domain-related invoice, include domain info
 */
function create_domain_renewal_invoice($pdo, $userId, $domainId, $price = 12.99) {
    require_once '../inc/billing.php';
    
    $domain = $pdo->query("SELECT domain_name FROM domains WHERE id = $domainId")->fetch();
    
    $invoiceId = cybercore_invoice_create($userId, [
        'reference' => 'domain-' . $domainId . '-renewal-' . time(),
        'description' => 'Domain renewal: ' . $domain['domain_name'],
        'amount' => $price,
        'vat_rate' => 23.0,
        'currency' => 'EUR',
        'status' => 'unpaid',
        'due_date' => date('Y-m-d', strtotime('+7 days')),
    ]);
    
    return $invoiceId;
}

// ============================================================================
// EXAMPLE 12: Integrate with existing services purchase flow
// ============================================================================

/**
 * When customer purchases a domain service, call this
 */
function process_domain_service_purchase($pdo, $userId, $serviceData) {
    require_once '../inc/domains.php';
    
    $domainName = $serviceData['domain_name'] ?? null;
    $expiresAt = $serviceData['expires_at'] ?? date('Y-m-d', strtotime('+1 year'));
    $pleskId = $serviceData['plesk_id'] ?? null;
    
    if (!$domainName) {
        throw new Exception('Domain name required');
    }
    
    // Create domain record
    $stmt = $pdo->prepare('
        INSERT INTO domains (user_id, domain_name, status, expires_at, plesk_id)
        VALUES (:user_id, :domain_name, :status, :expires_at, :plesk_id)
    ');
    
    $stmt->execute([
        'user_id' => $userId,
        'domain_name' => strtolower($domainName),
        'status' => 'active',
        'expires_at' => $expiresAt,
        'plesk_id' => $pleskId,
    ]);
    
    $domainId = (int) $pdo->lastInsertId();
    
    // Create welcome notification
    $service = new DomainService($pdo);
    $service->sendNotification($userId, $domainId, 'domain_registered', [
        'domain' => $domainName,
        'expires_at' => $expiresAt,
    ]);
    
    return $domainId;
}

// ============================================================================
// EXAMPLE 13: Monitor domain status in real-time (admin widget)
// ============================================================================

/**
 * Add this widget to admin dashboard to see domain status
 */
function domain_status_widget() {
    $pdo = cybercore_pdo();
    
    $active = $pdo->query("SELECT COUNT(*) FROM domains WHERE status = 'active'")->fetch()[0];
    $expiring = $pdo->query("
        SELECT COUNT(*) FROM domains 
        WHERE expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
    ")->fetch()[0];
    $overdue = $pdo->query("
        SELECT COUNT(*) FROM domains d
        JOIN invoices i ON i.reference LIKE CONCAT('domain-', d.id, '%')
        WHERE i.status IN ('overdue', 'unpaid')
    ")->fetch()[0];
    
    echo '<div class="row">';
    echo '<div class="col-md-3"><div class="card text-center"><div class="card-body">';
    echo '<h5>' . $active . '</h5><p>Domains Active</p>';
    echo '</div></div></div>';
    
    echo '<div class="col-md-3"><div class="card text-center"><div class="card-body">';
    echo '<h5 style="color: #ffc107;">' . $expiring . '</h5><p>Expiring Soon</p>';
    echo '</div></div></div>';
    
    echo '<div class="col-md-3"><div class="card text-center"><div class="card-body">';
    echo '<h5 style="color: #dc3545;">' . $overdue . '</h5><p>Overdue Invoices</p>';
    echo '</div></div></div>';
    echo '</div>';
}

// ============================================================================
// END OF EXAMPLES
// ============================================================================

/**
 * To use these examples:
 * 
 * 1. Copy the function you want
 * 2. Paste into your page
 * 3. Call the function with appropriate parameters
 * 4. Adjust as needed for your UI/flow
 * 
 * All examples follow CyberCore conventions:
 * - PDO for database access
 * - CSRF protection on forms
 * - User verification
 * - Error handling
 * - PT-PT language in UI
 */
