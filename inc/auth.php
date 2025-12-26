<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Session cookie hardening
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    } else {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_samesite', 'Strict');
    }
    session_start();
}

function currentUser()
{
    if (empty($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user === null) {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id,first_name,last_name,email,role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}

function requireLogin()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireRole($roles)
{
    $user = currentUser();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'], $allowed)) {
        http_response_code(403);
        echo 'Acesso negado.';
        exit;
    }
}

function isRole($role)
{
    $u = currentUser();
    return $u && $u['role'] === $role;
}

// Role-based access helper
// Usage: checkRole(['Cliente','Gestor']);
// - Redirects to login if not authenticated
// - Redirects to no_access.php if role not allowed
// - Logs access attempts
// - Returns true when access granted
function checkRole($allowedRoles)
{
    if (empty($_SESSION['user_id'])) {
        // Log tentativa de acesso não autenticada
        error_log('Unauthorized access attempt to ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
        header('Location: /login.php');
        exit;
    }
    
    $user = currentUser();
    if (!$user) {
        error_log('User session exists but user not found: ' . $_SESSION['user_id']);
        header('Location: /login.php');
        exit;
    }
    
    $allowed = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    
    if (!in_array($user['role'], $allowed)) {
        // Log acesso negado
        $pdo = getDB();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO logs (user_id, type, message, created_at) 
                 VALUES (?, ?, ?, NOW())'
            );
            $stmt->execute([
                $user['id'],
                'access_denied',
                'Tentativa de acesso negado a ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . 
                ' (role: ' . $user['role'] . ', permitido: ' . implode(', ', $allowed) . ')'
            ]);
        } catch (PDOException $e) {
            error_log('Failed to log access denied: ' . $e->getMessage());
        }
        
        // Redirecionar para página de acesso negado
        header('Location: /no_access.php?reason=insufficient_role&required=' . urlencode(implode(',', $allowed)));
        exit;
    }
    
    return true;
}

/**
 * Verifica se usuário tem permissão específica
 * @param string $permission - Nome da permissão (ex: 'can_manage_users')
 * @return bool
 */
function hasPermission($permission) {
    $user = currentUser();
    if (!$user) return false;
    
    require_once __DIR__ . '/menu_config.php';
    return \hasPermission($user['role'], $permission);
}

/**
 * Require permission ou retorna 403
 * @param string $permission - Nome da permissão
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        http_response_code(403);
        echo '<h1>403 - Acesso Negado</h1>';
        echo '<p>Você não tem permissão para acessar este recurso.</p>';
        echo '<p>Permissão necessária: <code>' . htmlspecialchars($permission) . '</code></p>';
        exit;
    }
}
