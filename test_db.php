<?php
/**
 * Database Connection Diagnostic
 * Usage: Visit https://cybercore.cyberworld.pt/test_db.php
 */

// Test 1: Load config
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=== CyberCore Database Connection Test ===\n\n";

echo "[1] Loading config.php...\n";
require_once __DIR__ . '/inc/config.php';
echo "✓ Config loaded.\n";
echo "  DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
echo "  DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
echo "  DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
echo "  DB_PASS: " . (defined('DB_PASS') ? (empty(DB_PASS) ? 'EMPTY' : '***') : 'NOT DEFINED') . "\n";
echo "\n";

// Test 2: Load db.php and create connection
echo "[2] Loading db.php and creating PDO connection...\n";
try {
    require_once __DIR__ . '/inc/db.php';
    $pdo = getDB();
    echo "✓ PDO connection successful.\n";
    echo "  PDO driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    echo "  Server version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 3: Query the database
echo "[3] Testing database query...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Query successful.\n";
    echo "  Users in database: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "✗ Query failed: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 4: Check settings table
echo "[4] Testing settings table...\n";
try {
    require_once __DIR__ . '/inc/settings.php';
    $maintenance = getSetting($pdo, 'maintenance_disable_login', '0');
    echo "✓ Settings function works.\n";
    echo "  maintenance_disable_login: " . $maintenance . "\n";
} catch (Exception $e) {
    echo "✗ Settings test failed: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

echo "=== All tests passed! ===\n";
?>
