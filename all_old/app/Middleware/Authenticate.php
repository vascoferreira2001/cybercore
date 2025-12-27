<?php
/**
 * Authentication Middleware
 * Ensures user is authenticated before accessing protected routes
 */

namespace App\Middleware;

class Authenticate
{
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['user_id'])) {
            // Save intended URL for redirect after login
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            
            header('Location: /login.php');
            exit;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
}
