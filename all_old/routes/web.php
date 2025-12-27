<?php
/**
 * Web Routes - Public Website
 * Routes for marketing pages and authentication
 */

use App\Middleware\Authenticate;
use App\Middleware\CheckRole;
use App\Middleware\VerifyCSRF;

// Public pages (no authentication required)
Router::get('/', function() {
    require __DIR__ . '/../index.php';
});

Router::get('/services', function() {
    require __DIR__ . '/../services.php';
});

Router::get('/pricing', function() {
    require __DIR__ . '/../pricing.php';
});

Router::get('/contact', function() {
    require __DIR__ . '/../contact.php';
});

Router::get('/about', function() {
    require __DIR__ . '/../website/about.php';
});

Router::post('/contact/submit', function() {
    require __DIR__ . '/../contact_submit.php';
});

// Authentication routes
Router::get('/login', function() {
    require __DIR__ . '/../login.php';
});

Router::post('/login', function() {
    require __DIR__ . '/../login.php';
});

Router::get('/register', function() {
    require __DIR__ . '/../register.php';
});

Router::post('/register', function() {
    require __DIR__ . '/../register.php';
});

Router::get('/logout', function() {
    require __DIR__ . '/../logout.php';
});

Router::get('/forgot-password', function() {
    require __DIR__ . '/../forgot_password.php';
});

Router::post('/forgot-password', function() {
    require __DIR__ . '/../forgot_password.php';
});

Router::get('/reset-password', function() {
    require __DIR__ . '/../reset_password.php';
});

Router::post('/reset-password', function() {
    require __DIR__ . '/../reset_password.php';
});

Router::get('/verify-email', function() {
    require __DIR__ . '/../verify_email.php';
});

// Legal pages
Router::get('/terms', function() {
    require __DIR__ . '/../terms.php';
});

Router::get('/privacy', function() {
    require __DIR__ . '/../privacy.php';
});
