<?php
/**
 * CyberCore - Gestão de Alojamentos Web
 * Gerir Serviços de Alojamento
 */

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

checkRole(['Gestor', 'Suporte Técnico']);

$user = currentUser();
$pdo = getDB();

// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$client = isset($_GET['client']) ? trim($_GET['client']) : '';
$plan = isset($_GET['plan']) ? trim($_GET['plan']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    // Construir query base
    $query = "SELECT 
                h.id,
                h.domain,
                h.package_name,
                h.control_panel,
                h.status,
                h.renewal_date,
                h.expiration_date,
                h.storage_gb,
                h.bandwidth_gb,
                h.email_accounts,
                h.databases,
                h.ssl_enabled,
                h.backup_enabled,
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.company_name,
                u.entity_type,
                COUNT(DISTINCT d.id) as domain_count
            FROM hosting h
            LEFT JOIN users u ON h.user_id = u.id
            LEFT JOIN domains d ON d.hosting_id = h.id
            WHERE 1=1";

    $params = [];

    // Aplicar filtro de busca
    if ($search) {
        $query .= " AND (h.domain LIKE ? OR h.package_name LIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam);
    }

    // Aplicar filtro de status
    if ($status) {
        $query .= " AND h.status = ?";
        $params[] = $status;
    }

    // Aplicar filtro de cliente
    if ($client) {
        $query .= " AND h.user_id = ?";
        $params[] = $client;
    }

    // Aplicar filtro de plano
    if ($plan) {
        $query .= " AND h.package_name = ?";
        $params[] = $plan;
    }

    $query .= " GROUP BY h.id ORDER BY h.expiration_date ASC, h.id DESC";

    // Obter total de registos
    $countQuery = "SELECT COUNT(DISTINCT h.id) as total FROM hosting h
                   LEFT JOIN users u ON h.user_id = u.id
                   LEFT JOIN domains d ON d.hosting_id = h.id
                   WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (h.domain LIKE ? OR h.package_name LIKE ?)";
    }
    if ($status) {
        $countQuery .= " AND h.status = ?";
    }
    if ($client) {
        $countQuery .= " AND h.user_id = ?";
    }
    if ($plan) {
        $countQuery .= " AND h.package_name = ?";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalHosting = $countStmt->fetchColumn();
    $totalPages = ceil($totalHosting / $perPage);

    // Obter dados com paginação
    $stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $hostings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obter lista de clientes
    $clientStmt = $pdo->query("SELECT id, first_name, last_name, company_name, entity_type FROM users WHERE role = 'Cliente' ORDER BY last_name, first_name");
    $clients = $clientStmt->fetchAll(PDO::FETCH_ASSOC);

    // Planos disponíveis
    $plans = ['Básico', 'Profissional', 'Empresarial', 'Premium'];
    $statuses = ['Ativo', 'Inativo', 'Suspenso', 'Cancelado'];

} catch (PDOException $e) {
    error_log('Erro ao obter alojamentos: ' . $e->getMessage());
    $hostings = [];
    $totalHosting = 0;
}

// Helper functions
function formatDate($date) {
    if (!$date) return 'N/A';
    try {
        $dt = new DateTime($date);
        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return 'N/A';
    }
}

function getClientName($hosting) {
    if (!$hosting['user_id']) return 'N/A';
    if ($hosting['entity_type'] === 'Coletiva' && $hosting['company_name']) {
        return htmlspecialchars($hosting['company_name']);
    }
    return htmlspecialchars($hosting['first_name'] . ' ' . $hosting['last_name']);
}

function getStatusBadge($status) {
    $badges = [
        'Ativo' => ['badge-success', '✓ Ativo'],
        'Inativo' => ['badge-secondary', '○ Inativo'],
        'Suspenso' => ['badge-warning', '⚠ Suspenso'],
        'Cancelado' => ['badge-danger', '✗ Cancelado']
    ];
    
    if (isset($badges[$status])) {
        return $badges[$status];
    }
    return ['badge-secondary', htmlspecialchars($status)];
}

function getExpiryBadge($date) {
    if (!$date) return ['badge-secondary', 'N/A'];
    
    try {
        $expiry = new DateTime($date);
        $today = new DateTime();
        $diff = $expiry->diff($today);
        
        if ($expiry < $today) {
            return ['badge-danger', '⚠ Expirado'];
        } elseif ($diff->days <= 30) {
            return ['badge-warning', '📅 ' . $diff->days . ' dias'];
        } else {
            return ['badge-success', '✓ ' . $diff->days . ' dias'];
        }
    } catch (Exception $e) {
        return ['badge-secondary', 'N/A'];
    }
}

function getPanelIcon($panel) {
    $icons = [
        'cPanel' => '⚙️',
        'Plesk' => '🔧',
        'Directadmin' => '🛠️',
        'ISPManager' => '⚡',
        'Outro' => '📱'
    ];
    
    return isset($icons[$panel]) ? $icons[$panel] : '📱';
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Alojamentos | CyberCore</title>
    <link rel="stylesheet" href="/assets/css/pages/admin-modern.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1>🌐 Alojamentos Web</h1>
                <p style="color: #666;">Gerir serviços de alojamento e hospedagem</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='/admin/hosting-add.php'">
                    + Novo Alojamento
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total de Alojamentos</div>
                <div class="stat-value"><?php echo $totalHosting; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Ativos</div>
                <div class="stat-value" style="color: #10b981;">
                    <?php 
                    $activeCount = count(array_filter($hostings, fn($h) => $h['status'] === 'Ativo'));
                    echo $activeCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Para Renovar</div>
                <div class="stat-value" style="color: #f59e0b;">
                    <?php 
                    $renewCount = 0;
                    foreach ($hostings as $h) {
                        if ($h['expiration_date']) {
                            $expiry = new DateTime($h['expiration_date']);
                            $today = new DateTime();
                            $diff = $expiry->diff($today);
                            if ($diff->days <= 30 && $expiry >= $today) {
                                $renewCount++;
                            }
                        }
                    }
                    echo $renewCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Expirados</div>
                <div class="stat-value" style="color: #ef4444;">
                    <?php 
                    $expiredCount = 0;
                    foreach ($hostings as $h) {
                        if ($h['expiration_date']) {
                            $expiry = new DateTime($h['expiration_date']);
                            $today = new DateTime();
                            if ($expiry < $today) {
                                $expiredCount++;
                            }
                        }
                    }
                    echo $expiredCount;
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
                        <input type="text" name="search" placeholder="Domínio ou Plano..." 
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
                        <label>Plano</label>
                        <select name="plan">
                            <option value="">Todos</option>
                            <?php foreach ($plans as $p): ?>
                            <option value="<?php echo htmlspecialchars($p); ?>" 
                                    <?php echo $plan === $p ? 'selected' : ''; ?>>
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

        <!-- Tabela de Alojamentos -->
        <div class="table-container">
            <?php if (empty($hostings)): ?>
                <div class="empty-state">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <p>Nenhum alojamento encontrado.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Domínio</th>
                                <th>Cliente</th>
                                <th>Plano</th>
                                <th>Painel</th>
                                <th>Storage</th>
                                <th>Status</th>
                                <th>Validade</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hostings as $hosting): ?>
                            <tr>
                                <td>
                                    <div class="client-name"><?php echo htmlspecialchars($hosting['domain']); ?></div>
                                </td>
                                <td>
                                    <?php echo getClientName($hosting); ?>
                                </td>
                                <td>
                                    <span style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                        <?php echo htmlspecialchars($hosting['package_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span title="<?php echo htmlspecialchars($hosting['control_panel']); ?>">
                                        <?php echo getPanelIcon($hosting['control_panel']); ?> 
                                        <span style="font-size: 12px;">
                                            <?php echo htmlspecialchars(substr($hosting['control_panel'], 0, 10)); ?>
                                        </span>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($hosting['storage_gb']); ?> GB
                                </td>
                                <td>
                                    <?php 
                                    [$badgeClass, $badgeText] = getStatusBadge($hosting['status']);
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo $badgeText; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    [$expiryClass, $expiryText] = getExpiryBadge($hosting['expiration_date']);
                                    ?>
                                    <span class="badge <?php echo $expiryClass; ?>">
                                        <?php echo $expiryText; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/hosting-view.php?id=<?php echo $hosting['id']; ?>'">
                                            👁️
                                        </button>
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/hosting-edit.php?id=<?php echo $hosting['id']; ?>'">
                                            ✏️
                                        </button>
                                        <button class="btn btn-small btn-danger" 
                                                onclick="if(confirm('Tem a certeza?')) window.location.href='#delete-<?php echo $hosting['id']; ?>'">
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
