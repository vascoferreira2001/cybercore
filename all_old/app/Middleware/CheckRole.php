<?php
/**
 * Check Role Middleware
 * Validates user has required role(s) to access route
 */

namespace App\Middleware;

class CheckRole
{
    private $allowedRoles;
    
    public function __construct($roles)
    {
        $this->allowedRoles = is_array($roles) ? $roles : [$roles];
    }
    
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Must be authenticated first
        if (empty($_SESSION['user_id'])) {
            header('Location: /login.php');
            exit;
        }
        
        // Load user and auth functions
        require_once __DIR__ . '/../../inc/auth.php';
        $user = currentUser();
        
        if (!$user) {
            session_destroy();
            header('Location: /login.php');
            exit;
        }
        
        // Normalize role names
        $allowedRoles = array_map('normalizeRoleName', $this->allowedRoles);
        $userRole = normalizeRoleName($user['role']);
        
        // Check if user has required role
        if (!in_array($userRole, $allowedRoles)) {
            // Log unauthorized access attempt
            $pdo = getDB();
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO logs (user_id, type, message, ip_address) 
                     VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([
                    $user['id'],
                    'access_denied',
                    'Attempted to access: ' . $_SERVER['REQUEST_URI'],
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            } catch (\PDOException $e) {
                error_log('Failed to log access denial: ' . $e->getMessage());
            }
            
            // Redirect to access denied page
            header('Location: /no_access.php');
            exit;
        }
    }
}
