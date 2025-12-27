<?php
/**
 * Client Area Routes
 * Routes for authenticated clients (all roles)
 */

use App\Middleware\Authenticate;
use App\Middleware\CheckRole;
use App\Middleware\VerifyCSRF;

// All client area routes require authentication
Router::group(['prefix' => '/manager', 'middleware' => [Authenticate::class]], function() {
    
    // Dashboard (accessible to all authenticated users)
    Router::get('/', function() {
        require __DIR__ . '/../manager/dashboard.php';
    });
    
    Router::get('/dashboard', function() {
        require __DIR__ . '/../manager/dashboard.php';
    });
    
    // Profile management (all roles)
    Router::get('/profile', function() {
        require __DIR__ . '/../manager/profile.php';
    });
    
    Router::post('/profile/update', function() {
        require __DIR__ . '/../inc/profile_update.php';
    });
    
    // Services (Cliente, Gestor, Support roles)
    Router::get('/services', function() {
        require __DIR__ . '/../manager/services.php';
    });
    
    Router::get('/domains', function() {
        require __DIR__ . '/../manager/domains.php';
    });
    
    Router::get('/domains/edit/{id}', function($id) {
        $_GET['id'] = $id;
        require __DIR__ . '/../manager/domains_edit.php';
    });
    
    Router::get('/hosting', function() {
        require __DIR__ . '/../manager/hosting.php';
    });
    
    // Billing & Finance (Cliente, Gestor, Suporte Financeiro)
    Router::get('/finance', function() {
        require __DIR__ . '/../manager/finance.php';
    });
    
    // Support tickets (all roles)
    Router::get('/support', function() {
        require __DIR__ . '/../manager/support.php';
    });
    
    // Updates/News (all roles)
    Router::get('/updates', function() {
        require __DIR__ . '/../manager/updates.php';
    });
    
    // Logs (Gestor only)
    Router::get('/logs', function() {
        require __DIR__ . '/../manager/logs.php';
    });
});
