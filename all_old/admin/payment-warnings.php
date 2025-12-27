<?php
/**
 * CyberCore - Avisos de Pagamento
 * Gerir avisos de atraso de pagamento
 */

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

checkRole(['Gestor', 'Suporte Financeiro']);

$user = currentUser();
$pdo = getDB();

// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$priority = isset($_GET['priority']) ? trim($_GET['priority']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    // Construir query base
    $query = "SELECT 
                w.id,
                w.reference_number,
                w.invoice_id,
                w.amount_due,
                w.status,
                w.priority,
                w.created_at,
                w.due_date,
                w.last_reminder,
                w.reminder_count,
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.company_name,
                u.entity_type,
                u.email,
                i.reference_number as invoice_ref
            FROM payment_warnings w
            LEFT JOIN invoices i ON w.invoice_id = i.id
            LEFT JOIN users u ON w.user_id = u.id
            WHERE 1=1";

    $params = [];

    // Aplicar filtro de busca
    if ($search) {
        $query .= " AND (w.reference_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    // Aplicar filtro de status
    if ($status) {
        $query .= " AND w.status = ?";
        $params[] = $status;
    }

    // Aplicar filtro de prioridade
    if ($priority) {
        $query .= " AND w.priority = ?";
        $params[] = $priority;
    }

    $query .= " ORDER BY w.priority DESC, w.due_date ASC, w.id DESC";

    // Obter total de registos
    $countQuery = "SELECT COUNT(*) as total FROM payment_warnings w
                   LEFT JOIN invoices i ON w.invoice_id = i.id
                   LEFT JOIN users u ON w.user_id = u.id
                   WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (w.reference_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    }
    if ($status) {
        $countQuery .= " AND w.status = ?";
    }
    if ($priority) {
        $countQuery .= " AND w.priority = ?";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalWarnings = $countStmt->fetchColumn();
    $totalPages = ceil($totalWarnings / $perPage);

    // Obter dados com paginação
    $stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $warnings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prioridades
    $priorities = ['Baixa', 'Média', 'Alta', 'Crítica'];
    $statuses = ['Ativo', 'Resolvido', 'Cancelado'];

} catch (PDOException $e) {
    error_log('Erro ao obter avisos: ' . $e->getMessage());
    $warnings = [];
    $totalWarnings = 0;
}

// Helper functions
function formatCurrency($value) {
    return '€ ' . number_format($value, 2, ',', '.');
}

function formatDate($date) {
    if (!$date) return 'N/A';
    try {
        $dt = new DateTime($date);
        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return 'N/A';
    }
}

function getClientName($warning) {
    if (!$warning['user_id']) return 'N/A';
    if ($warning['entity_type'] === 'Coletiva' && $warning['company_name']) {
        return htmlspecialchars($warning['company_name']);
    }
    return htmlspecialchars($warning['first_name'] . ' ' . $warning['last_name']);
}

function getStatusBadge($status) {
    $badges = [
        'Ativo' => ['badge-danger', '⚠️ Ativo'],
        'Resolvido' => ['badge-success', '✓ Resolvido'],
        'Cancelado' => ['badge-secondary', '✗ Cancelado']
    ];
    
    if (isset($badges[$status])) {
        return $badges[$status];
    }
    return ['badge-secondary', htmlspecialchars($status)];
}

function getPriorityBadge($priority) {
    $badges = [
        'Baixa' => ['badge-secondary', '◇ Baixa'],
        'Média' => ['badge-warning', '◆ Média'],
        'Alta' => ['badge-warning', '● Alta'],
        'Crítica' => ['badge-danger', '★ Crítica']
    ];
    
    if (isset($badges[$priority])) {
        return $badges[$priority];
    }
    return ['badge-secondary', htmlspecialchars($priority)];
}

function getDaysOverdue($dueDate) {
    if (!$dueDate) return 0;
    try {
        $due = new DateTime($dueDate);
        $today = new DateTime();
        if ($today > $due) {
            return $today->diff($due)->days;
        }
        return 0;
    } catch (Exception $e) {
        return 0;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avisos de Pagamento | CyberCore</title>
    <link rel="stylesheet" href="/assets/css/pages/admin-modern.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1>⚠️ Avisos de Pagamento</h1>
                <p style="color: #666;">Gerir avisos e acompanhamento de atrasos</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='/admin/payment-warning-add.php'">
                    + Novo Aviso
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total de Avisos</div>
                <div class="stat-value"><?php echo $totalWarnings; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Ativos</div>
                <div class="stat-value" style="color: #ef4444;">
                    <?php 
                    $activeCount = count(array_filter($warnings, fn($w) => $w['status'] === 'Ativo'));
                    echo $activeCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Valor Total em Atraso</div>
                <div class="stat-value" style="color: #ef4444; font-size: 20px;">
                    <?php 
                    $activeWarnings = array_filter($warnings, fn($w) => $w['status'] === 'Ativo');
                    $totalDue = array_sum(array_column($activeWarnings, 'amount_due'));
                    echo formatCurrency($totalDue);
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Críticos</div>
                <div class="stat-value" style="color: #ef4444;">
                    <?php 
                    $criticalCount = count(array_filter($warnings, fn($w) => $w['status'] === 'Ativo' && $w['priority'] === 'Crítica'));
                    echo $criticalCount;
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
                        <input type="text" name="search" placeholder="Cliente ou Referência..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Todos</option>
                            <?php foreach ($statuses as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>" 
                                    <?php echo $status === $s ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Prioridade</label>
                        <select name="priority">
                            <option value="">Todos</option>
                            <?php foreach ($priorities as $p): ?>
                            <option value="<?php echo htmlspecialchars($p); ?>" 
                                    <?php echo $priority === $p ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">🔍 Filtrar</button>
                        <a href="?page=1" class="btn-reset">↻ Limpar</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabela de Avisos -->
        <div class="table-container">
            <?php if (empty($warnings)): ?>
                <div class="empty-state">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <p>Nenhum aviso encontrado.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Referência</th>
                                <th>Cliente</th>
                                <th>Montante</th>
                                <th>Prioridade</th>
                                <th>Status</th>
                                <th>Atraso</th>
                                <th>Lembretes</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($warnings as $warning): ?>
                            <tr>
                                <td>
                                    <div class="client-name"><?php echo htmlspecialchars($warning['reference_number']); ?></div>
                                    <div class="client-id"><?php echo formatDate($warning['created_at']); ?></div>
                                </td>
                                <td>
                                    <?php echo getClientName($warning); ?>
                                </td>
                                <td>
                                    <span class="client-name"><?php echo formatCurrency($warning['amount_due']); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    [$priorityClass, $priorityText] = getPriorityBadge($warning['priority']);
                                    ?>
                                    <span class="badge <?php echo $priorityClass; ?>">
                                        <?php echo $priorityText; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    [$statusClass, $statusText] = getStatusBadge($warning['status']);
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $daysOverdue = getDaysOverdue($warning['due_date']);
                                    if ($daysOverdue > 0) {
                                        echo '<span style="color: #ef4444; font-weight: bold;">' . $daysOverdue . ' dias</span>';
                                    } else {
                                        echo '<span style="color: #10b981;">OK</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                        📧 <?php echo $warning['reminder_count']; ?>x
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/payment-warning-view.php?id=<?php echo $warning['id']; ?>'">
                                            👁️
                                        </button>
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/payment-warning-edit.php?id=<?php echo $warning['id']; ?>'">
                                            ✏️
                                        </button>
                                        <button class="btn btn-small btn-danger" 
                                                onclick="if(confirm('Tem a certeza?')) window.location.href='#delete-<?php echo $warning['id']; ?>'">
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

