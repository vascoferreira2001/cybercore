<?php
// Thin DB wrapper for direct includes
require_once __DIR__ . '/inc/db.php';

// Re-export getDB for convenience
if (!function_exists('db')) {
    function db() {
        return getDB();
    }
}
