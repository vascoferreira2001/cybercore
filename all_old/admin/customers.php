<?php
/**
 * CyberCore - Gestão de Clientes
 * Lista completa de clientes com informações financeiras
 */

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

checkRole(['Gestor', 'Suporte Financeiro', 'Suporte Técnico', 'Suporte ao Cliente']);

$user = currentUser();
$pdo = getDB();

// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$group = isset($_GET['group']) ? trim($_GET['group']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    // Construir query base
    $query = "SELECT 
                u.id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.entity_type,
                u.company_name,
                u.nif,
                u.client_group,
                u.created_at,
                COUNT(DISTINCT d.id) as total_services,
                COUNT(DISTINCT CASE WHEN d.status = 'active' THEN d.id END) as active_services,
                COALESCE(SUM(CASE WHEN i.status IN ('pending', 'paid') THEN i.amount ELSE 0 END), 0) as total_invoiced,
                COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.amount ELSE 0 END), 0) as paid_amount,
                COALESCE(SUM(CASE WHEN i.status = 'pending' THEN i.amount ELSE 0 END), 0) as pending_amount
            FROM users u
            LEFT JOIN domains d ON u.id = d.user_id
            LEFT JOIN invoices i ON u.id = i.user_id
            WHERE u.role = 'Cliente'";

    $params = [];

    // Aplicar filtro de busca
    if ($search) {
        $query .= " AND (
                    u.first_name LIKE ? OR 
                    u.last_name LIKE ? OR 
                    u.email LIKE ? OR 
                    u.company_name LIKE ? OR
                    u.nif LIKE ? OR
                    u.phone LIKE ?
                )";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    // Aplicar filtro de grupo
    if ($group) {
        $query .= " AND u.client_group = ?";
        $params[] = $group;
    }

    // Aplicar filtro de status
    if ($status) {
        if ($status === 'with_services') {
            $query .= " AND d.id IS NOT NULL";
        } elseif ($status === 'no_services') {
            $query .= " AND d.id IS NULL";
        } elseif ($status === 'overdue') {
            $query .= " AND i.status = 'pending' AND i.due_date < NOW()";
        }
    }

    $query .= " GROUP BY u.id ORDER BY u.last_name, u.first_name ASC";

    // Obter total de registos
    $countQuery = "SELECT COUNT(DISTINCT u.id) as total FROM users u 
                   LEFT JOIN domains d ON u.id = d.user_id 
                   LEFT JOIN invoices i ON u.id = i.user_id 
                   WHERE u.role = 'Cliente'";
    if ($search) {
        $countQuery .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.company_name LIKE ? OR u.nif LIKE ? OR u.phone LIKE ?)";
    }
    if ($group) {
        $countQuery .= " AND u.client_group = ?";
    }
    if ($status === 'with_services') {
        $countQuery .= " AND d.id IS NOT NULL";
    } elseif ($status === 'no_services') {
        $countQuery .= " AND d.id IS NULL";
    } elseif ($status === 'overdue') {
        $countQuery .= " AND i.status = 'pending' AND i.due_date < NOW()";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalClients = $countStmt->fetchColumn();
    $totalPages = ceil($totalClients / $perPage);

    // Obter dados com paginação
    $stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obter grupos de clientes
    $groupStmt = $pdo->query("SELECT DISTINCT client_group FROM users WHERE role = 'Cliente' AND client_group IS NOT NULL ORDER BY client_group");
    $groups = $groupStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log('Erro ao obter clientes: ' . $e->getMessage());
    $customers = [];
    $totalClients = 0;
}

// Helper functions
function formatCurrency($value) {
    return '€ ' . number_format($value, 2, ',', '.');
}

function formatPhone($phone) {
    if (strlen($phone) === 9) {
        return substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6);
    }
    return $phone;
}

function getClientName($customer) {
    if ($customer['entity_type'] === 'Coletiva' && $customer['company_name']) {
        return htmlspecialchars($customer['company_name']);
    }
    return htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']);
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes | CyberCore</title>
    <link rel="stylesheet" href="/assets/css/pages/admin-modern.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1>👥 Clientes</h1>
                <p style="color: #666;">Gestão completa de clientes e informações financeiras</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='/admin/customer-add.php'">
                    + Novo Cliente
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total de Clientes</div>
                <div class="stat-value"><?php echo $totalClients; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Faturado</div>
                <div class="stat-value" style="color: #10b981;">
                    <?php 
                    $totalInvoiced = array_sum(array_column($customers, 'total_invoiced'));
                    echo formatCurrency($totalInvoiced);
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Valor Recebido</div>
                <div class="stat-value" style="color: #059669;">
                    <?php 
                    $totalPaid = array_sum(array_column($customers, 'paid_amount'));
                    echo formatCurrency($totalPaid);
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Em Atraso</div>
                <div class="stat-value" style="color: #dc2626;">
                    <?php 
                    $totalPending = array_sum(array_column($customers, 'pending_amount'));
                    echo formatCurrency($totalPending);
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
                        <input type="text" name="search" placeholder="Nome, Email, NIF, Empresa..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Grupo de Cliente</label>
                        <select name="group">
                            <option value="">Todos os grupos</option>
                            <?php foreach ($groups as $g): ?>
                            <option value="<?php echo htmlspecialchars($g); ?>" 
                                    <?php echo $group === $g ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Todos</option>
                            <option value="with_services" <?php echo $status === 'with_services' ? 'selected' : ''; ?>>
                                Com Serviços
                            </option>
                            <option value="no_services" <?php echo $status === 'no_services' ? 'selected' : ''; ?>>
                                Sem Serviços
                            </option>
                            <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>
                                Em Atraso
                            </option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">🔍 Filtrar</button>
                        <a href="?page=1" class="btn-reset">↻ Limpar</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabela de Clientes -->
        <div class="table-container">
            <?php if (empty($customers)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <p>Nenhum cliente encontrado.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Contacto</th>
                                <th>Grupo</th>
                                <th>Serviços</th>
                                <th>Total Faturado</th>
                                <th>Recebido</th>
                                <th>Em Atraso</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>
                                    <div class="client-id">#<?php echo str_pad($customer['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                </td>
                                <td>
                                    <div class="client-name"><?php echo getClientName($customer); ?></div>
                                    <div class="client-id"><?php echo htmlspecialchars($customer['email']); ?></div>
                                </td>
                                <td>
                                    <?php if ($customer['phone']): ?>
                                    <?php echo formatPhone($customer['phone']); ?>
                                    <?php else: ?>
                                    <span style="color: #d1d5db;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($customer['client_group']): ?>
                                    <span class="badge badge-silver"><?php echo htmlspecialchars($customer['client_group']); ?></span>
                                    <?php else: ?>
                                    <span style="color: #d1d5db;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-silver">
                                        <?php echo $customer['active_services']; ?>/<?php echo $customer['total_services']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="currency"><?php echo formatCurrency($customer['total_invoiced']); ?></span>
                                </td>
                                <td>
                                    <span class="currency"><?php echo formatCurrency($customer['paid_amount']); ?></span>
                                </td>
                                <td>
                                    <span class="currency pending"><?php echo formatCurrency($customer['pending_amount']); ?></span>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <button class="btn-small btn-edit" 
                                                onclick="window.location.href='/admin/customer-edit.php?id=<?php echo $customer['id']; ?>'">
                                            ✏️ Editar
                                        </button>
                                        <button class="btn-small btn-delete" 
                                                onclick="if(confirm('Tem a certeza que quer eliminar este cliente?')) window.location.href='#delete-<?php echo $customer['id']; ?>'">
                                            🗑️ Eliminar
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
                    <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $group ? '&group=' . urlencode($group) : ''; ?>">
                        « Primeira
                    </a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $group ? '&group=' . urlencode($group) : ''; ?>">
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
                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $group ? '&group=' . urlencode($group) : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $group ? '&group=' . urlencode($group) : ''; ?>">
                        Próxima ›
                    </a>
                    <a href="?page=<?php echo $totalPages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $group ? '&group=' . urlencode($group) : ''; ?>">
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
