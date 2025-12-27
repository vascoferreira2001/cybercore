<?php
/**
 * CSRF Protection Middleware
 * Validates CSRF token on POST requests
 */

namespace App\Middleware;

class VerifyCSRF
{
    public function handle()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../inc/csrf.php';
            csrf_validate();
        }
    }
}
