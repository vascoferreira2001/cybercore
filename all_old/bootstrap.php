<?php
/**
 * CyberCore Bootstrap
 * Main entry point for the application
 * 
 * This file initializes the application, loads routes, and dispatches requests
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/inc/config.php';

// Load autoloader
require_once __DIR__ . '/autoload.php';

// Load Router
require_once __DIR__ . '/app/Router.php';

// Load route files
require_once __DIR__ . '/routes/web.php';
require_once __DIR__ . '/routes/client.php';
require_once __DIR__ . '/routes/admin.php';

// Dispatch the request
try {
    Router::dispatch();
} catch (Exception $e) {
    // Log error
    error_log('Router error: ' . $e->getMessage());
    
    // Show error page in development, generic error in production
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<h1>Application Error</h1>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>500 - Internal Server Error</h1>';
        echo '<p>Ocorreu um erro. Por favor, tente novamente mais tarde.</p>';
    }
}
