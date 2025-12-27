<?php
// PDO connection helper (secure, reusable) ---------------------------------
if (!function_exists('cybercore_pdo')) {
    function cybercore_pdo(): PDO
    {
        static $pdo;
        if ($pdo instanceof PDO) return $pdo;

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (Throwable $e) {
            // In production avoid echo; rely on logs.
            error_log('[DB] Connection failed: ' . $e->getMessage());
            throw $e;
        }

        return $pdo;
    }
}
