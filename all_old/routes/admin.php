<?php
/**
 * Admin Routes
 * Routes for administrative functions (Gestor and Support roles)
 */

use App\Middleware\Authenticate;
use App\Middleware\CheckRole;
use App\Middleware\VerifyCSRF;

// Admin routes - require authentication + admin roles
Router::group([
    'prefix' => '/manager/admin',
    'middleware' => [Authenticate::class]
], function() {
    
    // Admin Dashboard (Gestor + all support roles)
    Router::get('/', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte ao Cliente', 'Suporte Técnico', 'Suporte Financeiro']);
        require __DIR__ . '/../admin/dashboard.php';
    });
    
    Router::get('/dashboard', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte ao Cliente', 'Suporte Técnico', 'Suporte Financeiro']);
        require __DIR__ . '/../admin/dashboard.php';
    });
    
    // Customer Management (Gestor, Suporte ao Cliente)
    Router::get('/customers', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte ao Cliente']);
        require __DIR__ . '/../admin/customers.php';
    });
    
    // User Management (Gestor only)
    Router::get('/users', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor']);
        require __DIR__ . '/../admin/manage_users.php';
    });
    
    // Service Management (Gestor, Suporte Técnico)
    Router::get('/services', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte Técnico']);
        require __DIR__ . '/../admin/services.php';
    });
    
    Router::get('/hosting', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte Técnico']);
        require __DIR__ . '/../admin/hosting.php';
    });
    
    Router::get('/domains', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte Técnico']);
        require __DIR__ . '/../admin/domains.php';
    });
    
    Router::get('/servers', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte Técnico']);
        require __DIR__ . '/../admin/servers.php';
    });
    
    // Financial Management (Gestor, Suporte Financeiro)
    Router::get('/payments', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte Financeiro']);
        require __DIR__ . '/../admin/payments.php';
    });
    
    Router::get('/payment-warnings', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte Financeiro']);
        require __DIR__ . '/../admin/payment-warnings.php';
    });
    
    Router::get('/expenses', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte Financeiro']);
        require __DIR__ . '/../admin/expenses.php';
    });
    
    Router::get('/fiscal-approvals', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte Financeiro']);
        require __DIR__ . '/../admin/fiscal-approvals.php';
    });
    
    // Support & Tickets (All support roles)
    Router::get('/tickets', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte ao Cliente', 'Suporte Técnico', 'Suporte Financeiro']);
        require __DIR__ . '/../admin/tickets.php';
    });
    
    Router::get('/live-chat', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte ao Cliente']);
        require __DIR__ . '/../admin/live-chat.php';
    });
    
    Router::get('/knowledge-base', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte ao Cliente', 'Suporte Técnico']);
        require __DIR__ . '/../admin/knowledge-base.php';
    });
    
    // Business Management (Gestor, Suporte ao Cliente)
    Router::get('/quotes', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte ao Cliente']);
        require __DIR__ . '/../admin/quotes.php';
    });
    
    Router::get('/proposals', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte ao Cliente']);
        require __DIR__ . '/../admin/proposals.php';
    });
    
    Router::get('/contracts', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor']);
        require __DIR__ . '/../admin/contracts.php';
    });
    
    // Reports (Gestor only)
    Router::get('/reports', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor']);
        require __DIR__ . '/../admin/reports.php';
    });
    
    // System Management (Gestor only)
    Router::get('/settings', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor']);
        require __DIR__ . '/../admin/settings.php';
    });
    
    Router::get('/system-logs', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor']);
        require __DIR__ . '/../admin/system-logs.php';
    });
    
    Router::get('/team', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor']);
        require __DIR__ . '/../admin/team.php';
    });
    
    // Task & Schedule Management
    Router::get('/tasks', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte ao Cliente', 'Suporte Técnico']);
        require __DIR__ . '/../admin/tasks.php';
    });
    
    Router::get('/schedule', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte ao Cliente', 'Suporte Técnico']);
        require __DIR__ . '/../admin/schedule.php';
    });
    
    // Documents & Files
    Router::get('/documents', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte Financeiro']);
        require __DIR__ . '/../admin/documents.php';
    });
    
    Router::get('/notes', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte ao Cliente', 'Suporte Técnico']);
        require __DIR__ . '/../admin/notes.php';
    });
    
    // Alerts & Monitoring
    Router::get('/alerts', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte Técnico']);
        require __DIR__ . '/../admin/alerts.php';
    });
    
    // System Updates
    Router::get('/updates', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor']);
        require __DIR__ . '/../admin/updates.php';
    });
    
    // Deployment Verification
    Router::get('/verify-deploy', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor']);
        require __DIR__ . '/../admin/verify-deploy.php';
    });
    
    // Licenses
    Router::get('/licenses', function() {
        require_once __DIR__ . '/../inc/auth.php';
        checkRole(['Gestor', 'Suporte Técnico']);
        require __DIR__ . '/../admin/licenses.php';
    });
});
