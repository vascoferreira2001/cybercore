<?php
/**
 * CyberCore - Propostas e Orçamentos
 * Elaborar e Gerir Propostas para Clientes
 */

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

checkRole(['Gestor', 'Suporte Financeiro']);

$user = currentUser();
$pdo = getDB();

// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$client = isset($_GET['client']) ? trim($_GET['client']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    // Construir query base
    $query = "SELECT 
                p.id,
                p.reference_number,
                p.title,
                p.status,
                p.total_amount,
                p.tax_amount,
                p.final_amount,
                p.created_at,
                p.valid_until,
                p.conversion_date,
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.company_name,
                u.entity_type,
                u.email
            FROM proposals p
            LEFT JOIN users u ON p.client_id = u.id
            WHERE 1=1";

    $params = [];

    // Aplicar filtro de busca
    if ($search) {
        $query .= " AND (p.reference_number LIKE ? OR p.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    // Aplicar filtro de status
    if ($status) {
        $query .= " AND p.status = ?";
        $params[] = $status;
    }

    // Aplicar filtro de cliente
    if ($client) {
        $query .= " AND p.client_id = ?";
        $params[] = $client;
    }

    $query .= " ORDER BY p.created_at DESC";

    // Obter total de registos
    $countQuery = "SELECT COUNT(*) as total FROM proposals p 
                   LEFT JOIN users u ON p.client_id = u.id 
                   WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (p.reference_number LIKE ? OR p.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    }
    if ($status) {
        $countQuery .= " AND p.status = ?";
    }
    if ($client) {
        $countQuery .= " AND p.client_id = ?";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalProposals = $countStmt->fetchColumn();
    $totalPages = ceil($totalProposals / $perPage);

    // Obter dados com paginação
    $stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obter lista de clientes
    $clientStmt = $pdo->query("SELECT id, first_name, last_name, company_name, entity_type FROM users WHERE role = 'Cliente' ORDER BY last_name, first_name");
    $clients = $clientStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Erro ao obter propostas: ' . $e->getMessage());
    $proposals = [];
    $totalProposals = 0;
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

function getClientName($proposal) {
    if (!$proposal['user_id']) return 'N/A';
    if ($proposal['entity_type'] === 'Coletiva' && $proposal['company_name']) {
        return htmlspecialchars($proposal['company_name']);
    }
    return htmlspecialchars($proposal['first_name'] . ' ' . $proposal['last_name']);
}

function getStatusBadge($status) {
    $badges = [
        'draft' => ['badge-secondary', '📝 Rascunho'],
        'sent' => ['badge-info', '📧 Enviada'],
        'viewed' => ['badge-info', '👁️ Visualizada'],
        'accepted' => ['badge-success', '✓ Aceita'],
        'rejected' => ['badge-danger', '✗ Rejeitada'],
        'expired' => ['badge-danger', '⚠ Expirada']
    ];
    
    if (isset($badges[$status])) {
        return $badges[$status];
    }
    return ['badge-secondary', htmlspecialchars($status)];
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propostas e Orçamentos | CyberCore</title>
    <link rel="stylesheet" href="/assets/css/pages/admin-modern.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1>📋 Propostas e Orçamentos</h1>
                <p style="color: #666;">Elaborar e gerir propostas para clientes</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='/admin/proposal-create.php'">
                    + Nova Proposta
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total de Propostas</div>
                <div class="stat-value"><?php echo $totalProposals; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Rascunhos</div>
                <div class="stat-value" style="color: #6b7280;">
                    <?php 
                    $draftCount = count(array_filter($proposals, fn($p) => $p['status'] === 'draft'));
                    echo $draftCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Aceitas</div>
                <div class="stat-value" style="color: #10b981;">
                    <?php 
                    $acceptedCount = count(array_filter($proposals, fn($p) => $p['status'] === 'accepted'));
                    echo $acceptedCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Valor Total</div>
                <div class="stat-value" style="color: #007dff; font-size: 20px;">
                    <?php 
                    $totalValue = array_sum(array_column($proposals, 'final_amount'));
                    echo formatCurrency($totalValue);
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
                        <input type="text" name="search" placeholder="Referência, Título ou Cliente..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Todos</option>
                            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>
                                Rascunho
                            </option>
                            <option value="sent" <?php echo $status === 'sent' ? 'selected' : ''; ?>>
                                Enviada
                            </option>
                            <option value="accepted" <?php echo $status === 'accepted' ? 'selected' : ''; ?>>
                                Aceita
                            </option>
                            <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>
                                Rejeitada
                            </option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Cliente</label>
                        <select name="client">
                            <option value="">Todos</option>
                            <?php foreach ($clients as $c): ?>
                            <option value="<?php echo $c['id']; ?>" 
                                    <?php echo $client === (string)$c['id'] ? 'selected' : ''; ?>>
                                <?php 
                                if ($c['entity_type'] === 'Coletiva' && $c['company_name']) {
                                    echo htmlspecialchars($c['company_name']);
                                } else {
                                    echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']);
                                }
                                ?>
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

        <!-- Tabela de Propostas -->
        <div class="table-container">
            <?php if (empty($proposals)): ?>
                <div class="empty-state">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                    </svg>
                    <p>Nenhuma proposta encontrada.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Referência</th>
                                <th>Cliente</th>
                                <th>Título</th>
                                <th>Status</th>
                                <th>Valor</th>
                                <th>Data Criação</th>
                                <th>Válida até</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proposals as $proposal): ?>
                            <tr>
                                <td>
                                    <div class="client-name"><?php echo htmlspecialchars($proposal['reference_number']); ?></div>
                                </td>
                                <td>
                                    <?php echo getClientName($proposal); ?>
                                </td>
                                <td>
                                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars($proposal['title']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    [$badgeClass, $badgeText] = getStatusBadge($proposal['status']);
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo $badgeText; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="client-name"><?php echo formatCurrency($proposal['final_amount']); ?></span>
                                </td>
                                <td>
                                    <?php echo formatDate($proposal['created_at']); ?>
                                </td>
                                <td>
                                    <?php echo formatDate($proposal['valid_until']); ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/proposal-view.php?id=<?php echo $proposal['id']; ?>'">
                                            👁️
                                        </button>
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/proposal-edit.php?id=<?php echo $proposal['id']; ?>'">
                                            ✏️
                                        </button>
                                        <button class="btn btn-small btn-danger" 
                                                onclick="if(confirm('Tem a certeza?')) window.location.href='#delete-<?php echo $proposal['id']; ?>'">
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