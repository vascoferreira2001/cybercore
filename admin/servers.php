<?php
/**
 * CyberCore - Gestão de Servidores
 * Gerir Servidores e Infraestrutura
 */

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

checkRole(['Gestor', 'Suporte Técnico']);

$user = currentUser();
$pdo = getDB();

// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    // Construir query base
    $query = "SELECT 
                s.id,
                s.server_name,
                s.ip_address,
                s.server_type,
                s.operating_system,
                s.cpu_cores,
                s.ram_gb,
                s.storage_gb,
                s.status,
                s.uptime_percentage,
                s.last_check,
                s.provider,
                s.monthly_cost,
                COUNT(DISTINCT h.id) as hosting_count
            FROM servers s
            LEFT JOIN hosting h ON h.server_id = s.id
            WHERE 1=1";

    $params = [];

    // Aplicar filtro de busca
    if ($search) {
        $query .= " AND (s.server_name LIKE ? OR s.ip_address LIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam);
    }

    // Aplicar filtro de status
    if ($status) {
        $query .= " AND s.status = ?";
        $params[] = $status;
    }

    // Aplicar filtro de tipo
    if ($type) {
        $query .= " AND s.server_type = ?";
        $params[] = $type;
    }

    $query .= " GROUP BY s.id ORDER BY s.status DESC, s.server_name ASC";

    // Obter total de registos
    $countQuery = "SELECT COUNT(DISTINCT s.id) as total FROM servers s WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (s.server_name LIKE ? OR s.ip_address LIKE ?)";
    }
    if ($status) {
        $countQuery .= " AND s.status = ?";
    }
    if ($type) {
        $countQuery .= " AND s.server_type = ?";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalServers = $countStmt->fetchColumn();
    $totalPages = ceil($totalServers / $perPage);

    // Obter dados com paginação
    $stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tipos de servidor
    $types = ['VPS', 'Dedicado', 'Cloud', 'Compartilhado', 'Outro'];
    $statuses = ['Online', 'Offline', 'Manutenção', 'Monitorização'];

} catch (PDOException $e) {
    error_log('Erro ao obter servidores: ' . $e->getMessage());
    $servers = [];
    $totalServers = 0;
}

// Helper functions
function formatCurrency($value) {
    return '€ ' . number_format($value, 2, ',', '.');
}

function getStatusBadge($status) {
    $badges = [
        'Online' => ['badge-success', '✓ Online'],
        'Offline' => ['badge-danger', '✗ Offline'],
        'Manutenção' => ['badge-warning', '⚙️ Manutenção'],
        'Monitorização' => ['badge-secondary', '📊 Monitorização']
    ];
    
    if (isset($badges[$status])) {
        return $badges[$status];
    }
    return ['badge-secondary', htmlspecialchars($status)];
}

function getTypeIcon($type) {
    $icons = [
        'VPS' => '⚡',
        'Dedicado' => '🖥️',
        'Cloud' => '☁️',
        'Compartilhado' => '🌐',
        'Outro' => '📱'
    ];
    
    return isset($icons[$type]) ? $icons[$type] : '📱';
}

function getUptimeColor($uptime) {
    if (!$uptime) return '#999';
    $uptime = floatval($uptime);
    if ($uptime >= 99.9) return '#10b981';
    if ($uptime >= 99) return '#f59e0b';
    return '#ef4444';
}

function getLastCheckStatus($lastCheck) {
    if (!$lastCheck) return ['❓', 'Nunca'];
    
    try {
        $check = new DateTime($lastCheck);
        $now = new DateTime();
        $diff = $now->diff($check);
        
        if ($diff->days === 0 && $diff->h === 0 && $diff->i < 5) {
            return ['✓', 'Há ' . $diff->i . ' min'];
        } elseif ($diff->days === 0 && $diff->h === 0) {
            return ['✓', 'Há ' . $diff->i . ' min'];
        } elseif ($diff->days === 0) {
            return ['⚠', 'Há ' . $diff->h . 'h'];
        } else {
            return ['❌', 'Há ' . $diff->days . 'd'];
        }
    } catch (Exception $e) {
        return ['❓', 'N/A'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Servidores | CyberCore</title>
    <link rel="stylesheet" href="/assets/css/pages/admin-modern.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1>🖥️ Servidores</h1>
                <p style="color: #666;">Monitorizar e gerir infraestrutura de servidores</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='/admin/servers-add.php'">
                    + Novo Servidor
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total de Servidores</div>
                <div class="stat-value"><?php echo $totalServers; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Online</div>
                <div class="stat-value" style="color: #10b981;">
                    <?php 
                    $onlineCount = count(array_filter($servers, fn($s) => $s['status'] === 'Online'));
                    echo $onlineCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Custo Mensal Total</div>
                <div class="stat-value" style="color: #007dff; font-size: 20px;">
                    <?php 
                    $totalCost = array_sum(array_column($servers, 'monthly_cost'));
                    echo formatCurrency($totalCost);
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Offline</div>
                <div class="stat-value" style="color: #ef4444;">
                    <?php 
                    $offlineCount = count(array_filter($servers, fn($s) => $s['status'] === 'Offline'));
                    echo $offlineCount;
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
                        <input type="text" name="search" placeholder="Nome ou IP..." 
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
                        <label>Tipo</label>
                        <select name="type">
                            <option value="">Todos</option>
                            <?php foreach ($types as $t): ?>
                            <option value="<?php echo htmlspecialchars($t); ?>" 
                                    <?php echo $type === $t ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t); ?>
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

        <!-- Tabela de Servidores -->
        <div class="table-container">
            <?php if (empty($servers)): ?>
                <div class="empty-state">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <p>Nenhum servidor encontrado.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Nome do Servidor</th>
                                <th>IP</th>
                                <th>Tipo</th>
                                <th>Recursos</th>
                                <th>Uptime</th>
                                <th>Status</th>
                                <th>Última Verificação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($servers as $server): ?>
                            <tr>
                                <td>
                                    <div class="client-name"><?php echo htmlspecialchars($server['server_name']); ?></div>
                                    <div class="client-id"><?php echo htmlspecialchars($server['provider']); ?></div>
                                </td>
                                <td>
                                    <code style="background: #f3f4f6; padding: 2px 6px; border-radius: 3px;">
                                        <?php echo htmlspecialchars($server['ip_address']); ?>
                                    </code>
                                </td>
                                <td>
                                    <?php echo getTypeIcon($server['server_type']); ?>
                                    <span style="font-size: 12px;">
                                        <?php echo htmlspecialchars($server['server_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 12px; display: block;">
                                        💻 <?php echo $server['cpu_cores']; ?> cores
                                    </span>
                                    <span style="font-size: 12px; display: block;">
                                        🧠 <?php echo $server['ram_gb']; ?> GB RAM
                                    </span>
                                    <span style="font-size: 12px; display: block;">
                                        💾 <?php echo $server['storage_gb']; ?> GB
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: bold; color: <?php echo getUptimeColor($server['uptime_percentage']); ?>;">
                                        <?php echo $server['uptime_percentage'] ? number_format($server['uptime_percentage'], 2) . '%' : 'N/A'; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    [$badgeClass, $badgeText] = getStatusBadge($server['status']);
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo $badgeText; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    [$checkIcon, $checkText] = getLastCheckStatus($server['last_check']);
                                    ?>
                                    <span style="font-size: 12px;">
                                        <?php echo $checkIcon; ?> <?php echo $checkText; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/servers-view.php?id=<?php echo $server['id']; ?>'">
                                            👁️
                                        </button>
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/servers-edit.php?id=<?php echo $server['id']; ?>'">
                                            ✏️
                                        </button>
                                        <button class="btn btn-small btn-danger" 
                                                onclick="if(confirm('Tem a certeza?')) window.location.href='#delete-<?php echo $server['id']; ?>'">
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
