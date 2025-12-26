<?php
/**
 * CyberCore - Gestão de Documentos da Equipa
 * Partilhar e Gerir Documentos Internos
 */

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';

checkRole(['Gestor', 'Suporte Técnico']);

$user = currentUser();
$pdo = getDB();

// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    // Construir query base
    $query = "SELECT 
                d.id,
                d.title,
                d.description,
                d.file_path,
                d.file_type,
                d.file_size,
                d.category,
                d.visibility,
                d.created_at,
                d.updated_at,
                u.id as user_id,
                u.first_name,
                u.last_name,
                COUNT(DISTINCT v.id) as view_count
            FROM team_documents d
            LEFT JOIN users u ON d.created_by = u.id
            LEFT JOIN document_views v ON d.id = v.document_id
            WHERE 1=1";

    $params = [];

    // Aplicar filtro de busca
    if ($search) {
        $query .= " AND (d.title LIKE ? OR d.description LIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam);
    }

    // Aplicar filtro de tipo
    if ($type) {
        $query .= " AND d.file_type = ?";
        $params[] = $type;
    }

    // Aplicar filtro de categoria
    if ($category) {
        $query .= " AND d.category = ?";
        $params[] = $category;
    }

    $query .= " GROUP BY d.id ORDER BY d.updated_at DESC, d.id DESC";

    // Obter total de registos
    $countQuery = "SELECT COUNT(DISTINCT d.id) as total FROM team_documents d
                   LEFT JOIN users u ON d.created_by = u.id
                   LEFT JOIN document_views v ON d.id = v.document_id
                   WHERE 1=1";
    if ($search) {
        $countQuery .= " AND (d.title LIKE ? OR d.description LIKE ?)";
    }
    if ($type) {
        $countQuery .= " AND d.file_type = ?";
    }
    if ($category) {
        $countQuery .= " AND d.category = ?";
    }

    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalDocuments = $countStmt->fetchColumn();
    $totalPages = ceil($totalDocuments / $perPage);

    // Obter dados com paginação
    $stmt = $pdo->prepare($query . " LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$perPage, $offset]));
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Categorias
    $categories = ['Procedimentos', 'Políticas', 'Manuais', 'Templates', 'Relatórios', 'Outro'];
    $types = ['PDF', 'WORD', 'EXCEL', 'PPT', 'TXT', 'IMG'];

} catch (PDOException $e) {
    error_log('Erro ao obter documentos: ' . $e->getMessage());
    $documents = [];
    $totalDocuments = 0;
}
// Helper functions
function formatDate($date) {
    if (!$date) return 'N/A';
    try {
        $dt = new DateTime($date);
        return $dt->format('d/m/Y H:i');
    } catch (Exception $e) {
        return 'N/A';
    }
}

function formatFileSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 2) . ' KB';
    if ($bytes < 1024 * 1024 * 1024) return round($bytes / (1024 * 1024), 2) . ' MB';
    return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
}

function getFileIcon($type) {
    $icons = [
        'PDF' => '📄',
        'WORD' => '📝',
        'EXCEL' => '📊',
        'PPT' => '🎯',
        'TXT' => '📃',
        'IMG' => '🖼️',
        'ZIP' => '📦'
    ];
    
    return isset($icons[$type]) ? $icons[$type] : '📎';
}

function getVisibilityBadge($visibility) {
    $badges = [
        'public' => ['badge-success', '🌐 Público'],
        'private' => ['badge-secondary', '🔒 Privado'],
        'team' => ['badge-info', '👥 Equipa']
    ];
    
    if (isset($badges[$visibility])) {
        return $badges[$visibility];
    }
    return ['badge-secondary', htmlspecialchars($visibility)];
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos da Equipa | CyberCore</title>
    <link rel="stylesheet" href="/assets/css/pages/admin-modern.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1>📚 Documentos da Equipa</h1>
                <p style="color: #666;">Partilhar e gerir documentos internos da equipa</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='/admin/documents-add.php'">
                    + Novo Documento
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total de Documentos</div>
                <div class="stat-value"><?php echo $totalDocuments; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Públicos</div>
                <div class="stat-value" style="color: #10b981;">
                    <?php 
                    $publicCount = count(array_filter($documents, fn($d) => $d['visibility'] === 'public'));
                    echo $publicCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Privados</div>
                <div class="stat-value" style="color: #f59e0b;">
                    <?php 
                    $privateCount = count(array_filter($documents, fn($d) => $d['visibility'] === 'private'));
                    echo $privateCount;
                    ?>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Visualizações Totais</div>
                <div class="stat-value" style="color: #007dff;">
                    <?php 
                    $totalViews = array_sum(array_column($documents, 'view_count'));
                    echo $totalViews;
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
                        <input type="text" name="search" placeholder="Título ou descrição..." 
                               value="<?php echo htmlspecialchars($search); ?>">
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
                    <div class="filter-group">
                        <label>Categoria</label>
                        <select name="category">
                            <option value="">Todas</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>" 
                                    <?php echo $category === $c ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c); ?>
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

        <!-- Tabela de Documentos -->
        <div class="table-container">
            <?php if (empty($documents)): ?>
                <div class="empty-state">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                        <line x1="2" y1="10" x2="22" y2="10"></line>
                    </svg>
                    <p>Nenhum documento encontrado.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Categoria</th>
                                <th>Arquivo</th>
                                <th>Tamanho</th>
                                <th>Visibilidade</th>
                                <th>Visualizações</th>
                                <th>Autor</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td>
                                    <div class="client-name"><?php echo htmlspecialchars($doc['title']); ?></div>
                                    <div class="client-id" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars(substr($doc['description'], 0, 50)); ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                        <?php echo htmlspecialchars($doc['category']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span title="<?php echo htmlspecialchars($doc['file_type']); ?>">
                                        <?php echo getFileIcon($doc['file_type']); ?> 
                                        <span style="font-size: 12px;">
                                            <?php echo htmlspecialchars($doc['file_type']); ?>
                                        </span>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 12px;">
                                        <?php echo formatFileSize($doc['file_size']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    [$visibilityClass, $visibilityText] = getVisibilityBadge($doc['visibility']);
                                    ?>
                                    <span class="badge <?php echo $visibilityClass; ?>">
                                        <?php echo $visibilityText; ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                        👁️ <?php echo $doc['view_count']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?>
                                </td>
                                <td>
                                    <span style="font-size: 12px;">
                                        <?php echo formatDate($doc['updated_at']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn btn-small btn-primary" 
                                           href="<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                           download>
                                            ⬇️
                                        </a>
                                        <button class="btn btn-small btn-primary" 
                                                onclick="window.location.href='/admin/documents-edit.php?id=<?php echo $doc['id']; ?>'">
                                            ✏️
                                        </button>
                                        <button class="btn btn-small btn-danger" 
                                                onclick="if(confirm('Tem a certeza?')) window.location.href='#delete-<?php echo $doc['id']; ?>'">
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