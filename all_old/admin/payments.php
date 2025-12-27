<?php
/**
 * CyberCore - Gestão de Pagamentos
 * Registar e Gerir Pagamentos de Clientes
 */

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

checkRole(['Gestor', 'Suporte Financeiro']);

$user = currentUser();
$pdo = getDB();

// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$method = isset($_GET['method']) ? trim($_GET['method']) : '';
$client = isset($_GET['client']) ? trim($_GET['client']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    // Construir query base
    $query = "SELECT 
                py.id,
                py.reference_number,
                py.invoice_id,
                py.amount,
                py.payment_method,
                py.status,
                py.payment_date,
                py.transaction_id,
                py.notes,
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.company_name,
                u.entity_type,
                i.reference_number as invoice_ref
            FROM payments py
            LEFT JOIN invoices i ON py.invoice_id = i.id
            LEFT JOIN users u ON py.user_id = u.id
            WHERE 1=1";

    $params = [];

    // Aplicar filtro de busca
    if ($search) {
        $query .= " AND (py.reference_number LIKE ? OR py.transaction_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    // Aplicar filtro de método
    if ($method) {
        $query .= " AND py.payment_method = ?";
        $params[] = $method;
    }

    // Aplicar filtro de cliente
    if ($client) {
        $query .= " AND py.user_id = ?";
        $params[] = $client;
    }

    // Aplicar filtro de data
    if ($date_from) {
        $query .= " AND py.payment_date >= ?";
        $params[] = $date_from;
    }
    if ($date_to) {
        $query .= " AND py.payment_date <= ?";
        $params[] = $date_to . ' 23:59:59';
    }

    $query .= " ORDER BY py.payment_date DESC, py.id DESC";

    // Obter total de registos
    $countQuery = "SELECT COUNT(*) as total FROM payments py 
                   LEFT JOIN invoices i ON py.invoice_id = i.id 
                   LEFT JOIN users u ON py.user_id = u.id 
                   WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (py.reference_number LIKE ? OR py.transaction_id LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    }
    if ($method) {
        $countQuery .= " AND py.payment_method = ?";
    }
    if ($client) {
        $countQuery .= " AND py.user_id = ?";
    }
    if ($date_from) {
        $countQuery .= " AND py.payment_date >= ?";
    }
    if ($date_to) {
        $countQuery .= " AND py.payment_date <= ?";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalPayments = $countStmt->fetchColumn();
    $totalPages = ceil($totalPayments / $perPage);

    // Obter dados com paginação
    $stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obter lista de clientes
    $clientStmt = $pdo->query("SELECT id, first_name, last_name, company_name, entity_type FROM users WHERE role = 'Cliente' ORDER BY last_name, first_name");
    $clients = $clientStmt->fetchAll(PDO::FETCH_ASSOC);

    // Métodos de pagamento disponíveis
    $paymentMethods = ['Transferência Bancária', 'Cartão de Crédito', 'PayPal', 'MBWay', 'Cheque', 'Dinheiro', 'Outro'];

} catch (PDOException $e) {
    error_log('Erro ao obter pagamentos: ' . $e->getMessage());
    $payments = [];
    $totalPayments = 0;
}

// Helper functions
function formatCurrency($value) {
    return '€ ' . number_format($value, 2, ',', '.');
}

function formatDate($date) {
    if (!$date) return 'N/A';
    try {
        $dt = new DateTime($date);
        return $dt->format('d/m/Y H:i');
    } catch (Exception $e) {
        return 'N/A';
    }
}

function getClientName($payment) {
    if (!$payment['user_id']) return 'N/A';
    if ($payment['entity_type'] === 'Coletiva' && $payment['company_name']) {
        return htmlspecialchars($payment['company_name']);
    }
    return htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']);
}

function getStatusBadge($status) {
    $badges = [
        'pending' => ['badge-warning', '⏳ Pendente'],
        'completed' => ['badge-success', '✓ Confirmado'],
        'failed' => ['badge-danger', '✗ Falhou'],
        'refunded' => ['badge-secondary', '↩️ Reembolsado']
    ];
    
    if (isset($badges[$status])) {
        return $badges[$status];
    }
    return ['badge-secondary', htmlspecialchars($status)];
}

function getMethodIcon($method) {
    $icons = [
        'Transferência Bancária' => '🏦',
        'Cartão de Crédito' => '💳',
        'PayPal' => '🅿️',
        'MBWay' => '📱',
        'Cheque' => '📄',
        'Dinheiro' => '💵',
        'Outro' => '💰'
    ];
    
    return isset($icons[$method]) ? $icons[$method] : '💰';
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pagamentos | CyberCore</title>
    <link rel="stylesheet" href="/assets/css/pages/admin-modern.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1>💳 Pagamentos</h1>
                <p style="color: #666;">Registar e gerir pagamentos de clientes</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='/admin/payment-add.php'">
                    + Registar Pagamento
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total de Pagamentos</div>
                <div class="stat-value"><?php echo $totalPayments; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Confirmados</div>
                <div class="stat-value" style="color: #10b981;">
                    <?php 
                    $completedCount = count(array_filter($payments, fn($p) => $p['status'] === 'completed'));
                    echo $completedCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Valor Total</div>
                <div class="stat-value" style="color: #10b981; font-size: 20px;">
                    <?php 
                    $totalValue = array_sum(array_column($payments, 'amount'));
                    echo formatCurrency($totalValue);
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Pendentes</div>
                <div class="stat-value" style="color: #f59e0b;">
                    <?php 
                    $pendingCount = count(array_filter($payments, fn($p) => $p['status'] === 'pending'));
                    echo $pendingCount;
                    ?>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-panel">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Pesquisar</label>
                        <input type="text" name="search" placeholder="Referência ou ID Transação..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Método</label>
                        <select name="method">
                            <option value="">Todos</option>
                            <?php foreach ($paymentMethods as $m): ?>
                            <option value="<?php echo htmlspecialchars($m); ?>" 
                                    <?php echo $method === $m ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Data Início</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Data Fim</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">🔍 Filtrar</button>
                        <a href="?page=1" class="btn-reset">↻ Limpar</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabela de Pagamentos -->
        <div class="table-container">
            <?php if (empty($payments)): ?>
                <div class="empty-state">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <p>Nenhum pagamento encontrado.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Referência</th>
                                <th>Cliente</th>
                                <th>Fatura</th>
                                <th>Método</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>
                                    <div class="client-name"><?php echo htmlspecialchars($payment['reference_number']); ?></div>
                                    <div class="client-id"><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></div>
                                </td>
                                <td>
                                    <?php echo getClientName($payment); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($payment['invoice_ref'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <span title="<?php echo htmlspecialchars($payment['payment_method']); ?>">
                                        <?php echo getMethodIcon($payment['payment_method']); ?> 
                                        <span style="font-size: 12px;">
                                            <?php echo htmlspecialchars(substr($payment['payment_method'], 0, 12)); ?>
                                        </span>
                                    </span>
                                </td>
                                <td>
                                    <span class="client-name"><?php echo formatCurrency($payment['amount']); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    [$badgeClass, $badgeText] = getStatusBadge($payment['status']);
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo $badgeText; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo formatDate($payment['payment_date']); ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/payment-view.php?id=<?php echo $payment['id']; ?>'">
                                            👁️
                                        </button>
                                        <button class="btn btn-small btn-danger" 
                                                onclick="if(confirm('Tem a certeza?')) window.location.href='#delete-<?php echo $payment['id']; ?>'">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        « Primeira
                    </a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        ‹ Anterior
                    </a>
                    <?php endif; ?>

                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                    <?php if ($i === $page): ?>
                    <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        Próxima ›
                    </a>
                    <a href="?page=<?php echo $totalPages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                        Última »
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
