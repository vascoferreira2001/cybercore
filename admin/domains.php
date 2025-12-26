<?php
/**
 * CyberCore - Gestão de Domínios
 * Criar, Editar, Renovar e Gerir Domínios
 */

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

checkRole(['Gestor', 'Suporte Financeiro', 'Suporte Técnico']);

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
                d.id,
                d.domain_name,
                d.status,
                d.registration_date,
                d.expiration_date,
                d.renewal_date,
                d.registrar,
                d.type,
                u.id as user_id,
                u.first_name,
                u.last_name,
                u.company_name,
                u.entity_type,
                DATEDIFF(d.expiration_date, CURDATE()) as days_until_expiry
            FROM domains d
            LEFT JOIN users u ON d.user_id = u.id
            WHERE 1=1";

    $params = [];

    // Aplicar filtro de busca
    if ($search) {
        $query .= " AND d.domain_name LIKE ?";
        $params[] = "%$search%";
    }

    // Aplicar filtro de status
    if ($status) {
        $query .= " AND d.status = ?";
        $params[] = $status;
    }

    // Aplicar filtro de cliente
    if ($client) {
        $query .= " AND d.user_id = ?";
        $params[] = $client;
    }

    $query .= " ORDER BY d.expiration_date ASC, d.domain_name ASC";

    // Obter total de registos
    $countQuery = "SELECT COUNT(*) as total FROM domains d 
                   LEFT JOIN users u ON d.user_id = u.id 
                   WHERE 1=1";
    if ($search) {
        $countQuery .= " AND d.domain_name LIKE ?";
    }
    if ($status) {
        $countQuery .= " AND d.status = ?";
    }
    if ($client) {
        $countQuery .= " AND d.user_id = ?";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalDomains = $countStmt->fetchColumn();
    $totalPages = ceil($totalDomains / $perPage);

    // Obter dados com paginação
    $stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obter lista de clientes
    $clientStmt = $pdo->query("SELECT id, first_name, last_name, company_name, entity_type FROM users WHERE role = 'Cliente' ORDER BY last_name, first_name");
    $clients = $clientStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Erro ao obter domínios: ' . $e->getMessage());
    $domains = [];
    $totalDomains = 0;
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

function getClientName($domain) {
    if (!$domain['user_id']) return 'N/A';
    if ($domain['entity_type'] === 'Coletiva' && $domain['company_name']) {
        return htmlspecialchars($domain['company_name']);
    }
    return htmlspecialchars($domain['first_name'] . ' ' . $domain['last_name']);
}

function getStatusBadge($status) {
    $badges = [
        'active' => ['badge-success', '✓ Ativo'],
        'inactive' => ['badge-danger', '✗ Inativo'],
        'expired' => ['badge-danger', '⚠ Expirado'],
        'pending' => ['badge-warning', '⏳ Pendente'],
        'suspended' => ['badge-danger', '❌ Suspenso']
    ];
    
    if (isset($badges[$status])) {
        return $badges[$status];
    }
    return ['badge-secondary', htmlspecialchars($status)];
}

function getExpiryBadge($daysUntilExpiry) {
    if ($daysUntilExpiry === null) return 'badge-secondary';
    if ($daysUntilExpiry < 0) return 'badge-danger';
    if ($daysUntilExpiry < 30) return 'badge-warning';
    return 'badge-success';
}

function getExpiryText($daysUntilExpiry) {
    if ($daysUntilExpiry === null) return 'Data desconhecida';
    if ($daysUntilExpiry < 0) return 'EXPIRADO há ' . abs($daysUntilExpiry) . ' dias';
    if ($daysUntilExpiry === 0) return 'Expira HOJE';
    return 'Expira em ' . $daysUntilExpiry . ' dias';
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Domínios | CyberCore</title>
    <link rel="stylesheet" href="/assets/css/pages/admin-modern.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1>🌐 Domínios</h1>
                <p style="color: #666;">Gestão de domínios, renovações e status</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='/admin/domain-add.php'">
                    + Novo Domínio
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total de Domínios</div>
                <div class="stat-value"><?php echo $totalDomains; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Domínios Ativos</div>
                <div class="stat-value" style="color: #10b981;">
                    <?php 
                    $activeCount = count(array_filter($domains, fn($d) => $d['status'] === 'active'));
                    echo $activeCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Para Renovar</div>
                <div class="stat-value" style="color: #f59e0b;">
                    <?php 
                    $expiringCount = count(array_filter($domains, fn($d) => $d['days_until_expiry'] !== null && $d['days_until_expiry'] <= 30));
                    echo $expiringCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Expirados</div>
                <div class="stat-value" style="color: #ef4444;">
                    <?php 
                    $expiredCount = count(array_filter($domains, fn($d) => $d['days_until_expiry'] !== null && $d['days_until_expiry'] < 0));
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
                        <label>Pesquisar Domínio</label>
                        <input type="text" name="search" placeholder="ex: exemplo.pt" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Todos</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>
                                Ativo
                            </option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>
                                Inativo
                            </option>
                            <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>
                                Expirado
                            </option>
                            <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>
                                Suspenso
                            </option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Cliente</label>
                        <select name="client">
                            <option value="">Todos os clientes</option>
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

        <!-- Tabela de Domínios -->
        <div class="table-container">
            <?php if (empty($domains)): ?>
                <div class="empty-state">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                    <p>Nenhum domínio encontrado.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Domínio</th>
                                <th>Cliente</th>
                                <th>Status</th>
                                <th>Data de Registo</th>
                                <th>Expiração</th>
                                <th>Registrador</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($domains as $domain): ?>
                            <tr>
                                <td>
                                    <div class="client-name"><?php echo htmlspecialchars($domain['domain_name']); ?></div>
                                    <div class="client-id"><?php echo htmlspecialchars($domain['type'] ?? 'Domínio'); ?></div>
                                </td>
                                <td>
                                    <?php echo getClientName($domain); ?>
                                </td>
                                <td>
                                    <?php 
                                    [$badgeClass, $badgeText] = getStatusBadge($domain['status']);
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo $badgeText; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo formatDate($domain['registration_date']); ?>
                                </td>
                                <td>
                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                        <span><?php echo formatDate($domain['expiration_date']); ?></span>
                                        <span class="badge <?php echo getExpiryBadge($domain['days_until_expiry']); ?>" style="font-size: 11px;">
                                            <?php echo getExpiryText($domain['days_until_expiry']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($domain['registrar'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/domain-edit.php?id=<?php echo $domain['id']; ?>'">
                                            ✏️
                                        </button>
                                        <button class="btn btn-small btn-warning" 
                                                onclick="window.location.href='/admin/domain-renew.php?id=<?php echo $domain['id']; ?>'">
                                            🔄
                                        </button>
                                        <button class="btn btn-small btn-danger" 
                                                onclick="if(confirm('Tem a certeza?')) window.location.href='#delete-<?php echo $domain['id']; ?>'">
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