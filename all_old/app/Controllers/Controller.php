<?php
/**
 * Base Controller
 * All controllers should extend this class
 */

namespace App\Controllers;

abstract class Controller
{
    protected $pdo;
    protected $user;
    
    public function __construct()
    {
        // Initialize database connection
        require_once __DIR__ . '/../../inc/db.php';
        $this->pdo = getDB();
        
        // Load current user if authenticated
        if (isset($_SESSION['user_id'])) {
            require_once __DIR__ . '/../../inc/auth.php';
            $this->user = currentUser();
        }
    }
    
    /**
     * Render a view
     */
    protected function view($viewPath, $data = [])
    {
        extract($data);
        
        $viewFile = __DIR__ . '/../Views/' . str_replace('.', '/', $viewPath) . '.php';
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View {$viewPath} not found");
        }
        
        require $viewFile;
    }
    
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Redirect to a URL
     */
    protected function redirect($url, $statusCode = 302)
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Get request input
     */
    protected function input($key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
    
    /**
     * Validate CSRF token
     */
    protected function validateCSRF()
    {
        require_once __DIR__ . '/../../inc/csrf.php';
        csrf_validate();
    }
    
    /**
     * Check if user has permission
     */
    protected function requirePermission($permission)
    {
        require_once __DIR__ . '/../../inc/permissions.php';
        requirePermission($permission, $this->user);
    }
    
    /**
     * Check if user has role
     */
    protected function requireRole($roles)
    {
        require_once __DIR__ . '/../../inc/auth.php';
        requireRole($roles);
    }
}
