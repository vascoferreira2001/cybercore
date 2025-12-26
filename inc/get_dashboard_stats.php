<?php
/**
 * CyberCore Dashboard Stats API
 * Retorna métricas do dashboard baseado no cargo do usuário
 */

header('Content-Type: application/json');
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$user = currentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Utilizador não encontrado']);
    exit;
}

$pdo = getDB();
$role = $user['role'];
$userId = $user['id'];

/**
 * Helper para executar queries de forma segura
 */
function safeCount($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchColumn();
        return $result !== false ? (int)$result : 0;
    } catch (PDOException $e) {
        error_log('Stats query error: ' . $e->getMessage());
        return 0;
    }
}

function safeFetchOne($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Stats fetch error: ' . $e->getMessage());
        return null;
    }
}

// Inicializar array de estatísticas
$stats = [
    'role' => $role,
    'metrics' => [],
    'charts' => [],
    'recent_activity' => []
];

// Estatísticas baseadas no cargo
switch ($role) {
    case 'Gestor':
        // Métricas do Gestor (visão completa do sistema)
        $stats['metrics'] = [
            'total_clients' => safeCount($pdo, "SELECT COUNT(*) FROM users WHERE role = 'Cliente'"),
            'total_services' => safeCount($pdo, 'SELECT COUNT(*) FROM domains'),
            'active_services' => safeCount($pdo, "SELECT COUNT(*) FROM domains WHERE status = 'active'"),
            'total_invoices' => safeCount($pdo, 'SELECT COUNT(*) FROM invoices'),
            'unpaid_invoices' => safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'"),
            'paid_invoices' => safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'paid'"),
            'overdue_invoices' => safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'unpaid' AND due_date < NOW()"),
            'open_tickets' => safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'open'"),
            'closed_tickets' => safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'closed'"),
            'total_team_members' => safeCount($pdo, "SELECT COUNT(*) FROM users WHERE role != 'Cliente'")
        ];

        // Receita total do mês atual
        $monthlyRevenue = safeFetchOne($pdo, 
            "SELECT COALESCE(SUM(amount), 0) FROM invoices 
             WHERE status = 'paid' 
             AND MONTH(paid_at) = MONTH(CURRENT_DATE()) 
             AND YEAR(paid_at) = YEAR(CURRENT_DATE())"
        );
        $stats['metrics']['monthly_revenue'] = (float)$monthlyRevenue;

        // Receita total (histórico)
        $totalRevenue = safeFetchOne($pdo, 
            "SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status = 'paid'"
        );
        $stats['metrics']['total_revenue'] = (float)$totalRevenue;

        // Valor pendente
        $pendingAmount = safeFetchOne($pdo, 
            "SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status = 'unpaid'"
        );
        $stats['metrics']['pending_amount'] = (float)$pendingAmount;

        // Próximas renovações (próximos 30 dias)
        $upcomingRenewals = safeCount($pdo, 
            "SELECT COUNT(*) FROM domains 
             WHERE status = 'active' 
             AND renewal_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)"
        );
        $stats['metrics']['upcoming_renewals'] = $upcomingRenewals;

        break;

    case 'Suporte ao Cliente':
        // Métricas do Suporte ao Cliente
        $stats['metrics'] = [
            'total_clients' => safeCount($pdo, "SELECT COUNT(*) FROM users WHERE role = 'Cliente'"),
            'my_tickets' => safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE assigned_to = ?", [$userId]),
            'open_tickets' => safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE status = 'open'"),
            'closed_tickets_today' => safeCount($pdo, 
                "SELECT COUNT(*) FROM tickets 
                 WHERE status = 'closed' 
                 AND DATE(updated_at) = CURDATE()"
            ),
            'pending_tickets' => safeCount($pdo, 
                "SELECT COUNT(*) FROM tickets 
                 WHERE status = 'open' 
                 AND assigned_to IS NULL"
            ),
            'high_priority_tickets' => safeCount($pdo, 
                "SELECT COUNT(*) FROM tickets 
                 WHERE status = 'open' 
                 AND priority = 'high'"
            )
        ];
        break;

    case 'Suporte Técnico':
        // Métricas do Suporte Técnico
        $stats['metrics'] = [
            'total_services' => safeCount($pdo, 'SELECT COUNT(*) FROM domains'),
            'active_services' => safeCount($pdo, "SELECT COUNT(*) FROM domains WHERE status = 'active'"),
            'suspended_services' => safeCount($pdo, "SELECT COUNT(*) FROM domains WHERE status = 'suspended'"),
            'my_tickets' => safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE assigned_to = ?", [$userId]),
            'open_technical_tickets' => safeCount($pdo, 
                "SELECT COUNT(*) FROM tickets 
                 WHERE status = 'open' 
                 AND category = 'technical'"
            ),
            'services_expiring_soon' => safeCount($pdo, 
                "SELECT COUNT(*) FROM domains 
                 WHERE status = 'active' 
                 AND renewal_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)"
            )
        ];
        break;

    case 'Suporte Financeiro':
        // Métricas do Suporte Financeiro
        $stats['metrics'] = [
            'total_invoices' => safeCount($pdo, 'SELECT COUNT(*) FROM invoices'),
            'unpaid_invoices' => safeCount($pdo, "SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'"),
            'overdue_invoices' => safeCount($pdo, 
                "SELECT COUNT(*) FROM invoices 
                 WHERE status = 'unpaid' 
                 AND due_date < NOW()"
            ),
            'paid_today' => safeCount($pdo, 
                "SELECT COUNT(*) FROM invoices 
                 WHERE status = 'paid' 
                 AND DATE(paid_at) = CURDATE()"
            ),
            'my_tickets' => safeCount($pdo, "SELECT COUNT(*) FROM tickets WHERE assigned_to = ?", [$userId])
        ];

        // Valor total pendente
        $pendingAmount = safeFetchOne($pdo, 
            "SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status = 'unpaid'"
        );
        $stats['metrics']['pending_amount'] = (float)$pendingAmount;

        // Receita do mês
        $monthlyRevenue = safeFetchOne($pdo, 
            "SELECT COALESCE(SUM(amount), 0) FROM invoices 
             WHERE status = 'paid' 
             AND MONTH(paid_at) = MONTH(CURRENT_DATE()) 
             AND YEAR(paid_at) = YEAR(CURRENT_DATE())"
        );
        $stats['metrics']['monthly_revenue'] = (float)$monthlyRevenue;

        break;

    case 'Cliente':
    default:
        // Métricas do Cliente
        $stats['metrics'] = [
            'total_services' => safeCount($pdo, 
                'SELECT COUNT(*) FROM domains WHERE user_id = ?', 
                [$userId]
            ),
            'active_services' => safeCount($pdo, 
                "SELECT COUNT(*) FROM domains WHERE user_id = ? AND status = 'active'", 
                [$userId]
            ),
            'unpaid_invoices' => safeCount($pdo, 
                "SELECT COUNT(*) FROM invoices WHERE user_id = ? AND status = 'unpaid'", 
                [$userId]
            ),
            'open_tickets' => safeCount($pdo, 
                "SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status = 'open'", 
                [$userId]
            )
        ];

        // Valor total pendente
        $unpaidAmount = safeFetchOne($pdo, 
            "SELECT COALESCE(SUM(amount), 0) FROM invoices 
             WHERE user_id = ? AND status = 'unpaid'", 
            [$userId]
        );
        $stats['metrics']['unpaid_amount'] = (float)$unpaidAmount;

        // Próxima renovação
        $nextRenewal = safeFetchOne($pdo, 
            "SELECT MIN(renewal_date) FROM domains 
             WHERE user_id = ? 
             AND status = 'active' 
             AND renewal_date > NOW()", 
            [$userId]
        );
        
        if ($nextRenewal) {
            $renewalDate = new DateTime($nextRenewal);
            $now = new DateTime();
            $diff = $now->diff($renewalDate);
            $stats['metrics']['next_renewal_date'] = $renewalDate->format('Y-m-d');
            $stats['metrics']['next_renewal_days'] = $diff->days;
        } else {
            $stats['metrics']['next_renewal_date'] = null;
            $stats['metrics']['next_renewal_days'] = null;
        }

        break;
}

// Atividade recente (últimas 10 ações)
try {
    $activityQuery = "SELECT * FROM logs WHERE 1=1";
    $activityParams = [];

    // Clientes só vêem seus próprios logs
    if ($role === 'Cliente') {
        $activityQuery .= " AND user_id = ?";
        $activityParams[] = $userId;
    }
    // Suporte vê logs relevantes à sua área
    elseif ($role === 'Suporte ao Cliente') {
        $activityQuery .= " AND (type LIKE '%ticket%' OR type LIKE '%customer%')";
    }
    elseif ($role === 'Suporte Técnico') {
        $activityQuery .= " AND (type LIKE '%service%' OR type LIKE '%domain%' OR type LIKE '%hosting%')";
    }
    elseif ($role === 'Suporte Financeiro') {
        $activityQuery .= " AND (type LIKE '%invoice%' OR type LIKE '%payment%')";
    }
    // Gestor vê tudo (sem filtro adicional)

    $activityQuery .= " ORDER BY created_at DESC LIMIT 10";
    $stmt = $pdo->prepare($activityQuery);
    $stmt->execute($activityParams);
    $stats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Activity log error: ' . $e->getMessage());
    $stats['recent_activity'] = [];
}

// Retornar JSON
echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
