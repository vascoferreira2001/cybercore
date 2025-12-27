<?php
/**
 * CyberCore - Gestão de Bilhetes de Suporte
 * Sistema de Tickets de Suporte Técnico
 */

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

checkRole(['Gestor', 'Suporte Técnico']);

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
                t.id,
                t.ticket_number,
                t.title,
                t.description,
                t.status,
                t.priority,
                t.created_at,
                t.updated_at,
                t.assigned_to,
                u.id as user_id,
                u.first_name as client_first_name,
                u.last_name as client_last_name,
                u.company_name,
                u.entity_type,
                s.first_name as support_first_name,
                s.last_name as support_last_name
            FROM tickets t
            LEFT JOIN users u ON t.user_id = u.id
            LEFT JOIN users s ON t.assigned_to = s.id
            WHERE 1=1";

    $params = [];

    // Aplicar filtro de busca
    if ($search) {
        $query .= " AND (t.ticket_number LIKE ? OR t.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    // Aplicar filtro de status
    if ($status) {
        $query .= " AND t.status = ?";
        $params[] = $status;
    }

    // Aplicar filtro de prioridade
    if ($priority) {
        $query .= " AND t.priority = ?";
        $params[] = $priority;
    }

    $query .= " ORDER BY CASE WHEN t.status = 'Aberto' THEN 0 WHEN t.status = 'Em Progresso' THEN 1 ELSE 2 END, t.priority DESC, t.updated_at DESC";

    // Obter total de registos
    $countQuery = "SELECT COUNT(*) as total FROM tickets t
                   LEFT JOIN users u ON t.user_id = u.id
                   LEFT JOIN users s ON t.assigned_to = s.id
                   WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (t.ticket_number LIKE ? OR t.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    }
    if ($status) {
        $countQuery .= " AND t.status = ?";
    }
    if ($priority) {
        $countQuery .= " AND t.priority = ?";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalTickets = $countStmt->fetchColumn();
    $totalPages = ceil($totalTickets / $perPage);

    // Obter dados com paginação
    $stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $statuses = ['Aberto', 'Em Progresso', 'Aguardando Cliente', 'Resolvido', 'Fechado'];
    $priorities = ['Baixa', 'Média', 'Alta', 'Crítica'];

} catch (PDOException $e) {
    error_log('Erro ao obter tickets: ' . $e->getMessage());
    $tickets = [];
    $totalTickets = 0;
}

// Helper functions
function formatDate($date) {
    if (!$date) return 'N/A';
    try {
        $dt = new DateTime($date);
        $now = new DateTime();
        $diff = $now->diff($dt);
        
        if ($diff->days === 0 && $diff->h === 0) {
            return 'Há ' . $diff->i . ' min';
        } elseif ($diff->days === 0) {
            return 'Há ' . $diff->h . 'h';
        } else {
            return $dt->format('d/m/Y H:i');
        }
    } catch (Exception $e) {
        return 'N/A';
    }
}

function getClientName($ticket) {
    if (!$ticket['user_id']) return 'N/A';
    if ($ticket['entity_type'] === 'Coletiva' && $ticket['company_name']) {
        return htmlspecialchars($ticket['company_name']);
    }
    return htmlspecialchars($ticket['client_first_name'] . ' ' . $ticket['client_last_name']);
}

function getStatusBadge($status) {
    $badges = [
        'Aberto' => ['badge-danger', '🔴 Aberto'],
        'Em Progresso' => ['badge-warning', '🟡 Em Progresso'],
        'Aguardando Cliente' => ['badge-secondary', '⏸ Aguardando'],
        'Resolvido' => ['badge-success', '✓ Resolvido'],
        'Fechado' => ['badge-secondary', '✗ Fechado']
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
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilhetes de Suporte | CyberCore</title>
    <link rel="stylesheet" href="/assets/css/pages/admin-modern.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1>🎫 Bilhetes de Suporte</h1>
                <p style="color: #666;">Gerir e responder a solicitações de suporte técnico</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='/admin/tickets-add.php'">
                    + Novo Bilhete
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total de Bilhetes</div>
                <div class="stat-value"><?php echo $totalTickets; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Abertos</div>
                <div class="stat-value" style="color: #ef4444;">
                    <?php 
                    $openCount = count(array_filter($tickets, fn($t) => $t['status'] === 'Aberto'));
                    echo $openCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Em Progresso</div>
                <div class="stat-value" style="color: #f59e0b;">
                    <?php 
                    $inProgressCount = count(array_filter($tickets, fn($t) => $t['status'] === 'Em Progresso'));
                    echo $inProgressCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Resolvidos</div>
                <div class="stat-value" style="color: #10b981;">
                    <?php 
                    $resolvedCount = count(array_filter($tickets, fn($t) => $t['status'] === 'Resolvido'));
                    echo $resolvedCount;
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
                        <input type="text" name="search" placeholder="Bilhete ou Cliente..." 
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

        <!-- Tabela de Bilhetes -->
        <div class="table-container">
            <?php if (empty($tickets)): ?>
                <div class="empty-state">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <p>Nenhum bilhete encontrado.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Bilhete</th>
                                <th>Assunto</th>
                                <th>Cliente</th>
                                <th>Atribuído a</th>
                                <th>Prioridade</th>
                                <th>Status</th>
                                <th>Última Atualização</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>
                                    <div class="client-name">#<?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                                    <div class="client-id"><?php echo formatDate($ticket['created_at']); ?></div>
                                </td>
                                <td>
                                    <span style="overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                        <?php echo htmlspecialchars(substr($ticket['title'], 0, 60)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo getClientName($ticket); ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($ticket['assigned_to']) {
                                        echo htmlspecialchars($ticket['support_first_name'] . ' ' . $ticket['support_last_name']);
                                    } else {
                                        echo '<span style="color: #999;">Não atribuído</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    [$priorityClass, $priorityText] = getPriorityBadge($ticket['priority']);
                                    ?>
                                    <span class="badge <?php echo $priorityClass; ?>">
                                        <?php echo $priorityText; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    [$statusClass, $statusText] = getStatusBadge($ticket['status']);
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 12px;">
                                        <?php echo formatDate($ticket['updated_at']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/tickets-view.php?id=<?php echo $ticket['id']; ?>'">
                                            👁️
                                        </button>
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/tickets-edit.php?id=<?php echo $ticket['id']; ?>'">
                                            ✏️
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

