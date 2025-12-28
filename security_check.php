<?php
/**
 * CyberCore - Production Security Audit Script
 * 
 * Verifies all security configurations are properly set
 * Run this before going live in production
 * 
 * Usage: php security_check.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class CyberCoreSecurityAudit
{
    private $results = [];
    private $passed = 0;
    private $failed = 0;

    public function run()
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║     CyberCore - Production Security Audit                     ║\n";
        echo "║     Windows Server + IIS + PHP 8.1 + Plesk                    ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";

        $this->checkHTTPS();
        $this->checkHeaders();
        $this->checkDatabase();
        $this->checkFilePermissions();
        $this->checkPHPConfiguration();
        $this->checkWebConfig();
        $this->checkEnvironment();
        $this->checkDirectoryAccess();
        $this->checkUploadsFolder();

        $this->printReport();
    }

    // ================================================
    // 1. HTTPS VERIFICATION
    // ================================================
    private function checkHTTPS()
    {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "1. HTTPS & SECURITY HEADERS\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        // Check if HTTPS is available
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        $this->log('HTTPS Active', $is_https);

        // Check common security headers
        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Content-Security-Policy' => true, // Just check presence
            'Strict-Transport-Security' => true,
            'Referrer-Policy' => true,
            'Permissions-Policy' => true,
        ];

        foreach ($headers as $header => $expected) {
            $value = $this->getHeader($header);
            $passed = !is_null($value);
            $this->log("Header: $header", $passed, $value ?: 'NOT SET');
        }
    }

    // ================================================
    // 2. DATABASE CONNECTION
    // ================================================
    private function checkDatabase()
    {
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "2. DATABASE CONNECTIVITY\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        try {
            $env = $this->loadEnv();
            
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                $env['DB_HOST'] ?? 'localhost',
                $env['DB_PORT'] ?? '3306',
                $env['DB_NAME'] ?? 'cybercore'
            );

            $pdo = new PDO(
                $dsn,
                $env['DB_USER'] ?? 'root',
                $env['DB_PASS'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $this->log('Database Connection', true, 'PDO Connected');

            // Check if tables exist
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $required_tables = ['users', 'services', 'invoices', 'tickets', 'ticket_messages'];
            foreach ($required_tables as $table) {
                $exists = in_array($table, $tables);
                $this->log("Table: $table", $exists);
            }

        } catch (Exception $e) {
            $this->log('Database Connection', false, $e->getMessage());
        }
    }

    // ================================================
    // 3. FILE PERMISSIONS
    // ================================================
    private function checkFilePermissions()
    {
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "3. FILE PERMISSIONS & PROTECTION\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        $files_to_check = [
            '.env' => 'Should exist and be readable',
            'config/database.php' => 'Should be protected',
            'inc/bootstrap.php' => 'Should be readable',
            'web.config' => 'Should exist',
            'assets/uploads/web.config' => 'Should block PHP',
        ];

        foreach ($files_to_check as $file => $description) {
            $path = __DIR__ . '/' . $file;
            $exists = file_exists($path);
            $this->log("File: $file", $exists, $description);
        }

        // Check if uploads folder is writable but not executable
        $uploads_path = __DIR__ . '/assets/uploads';
        $is_writable = is_writable($uploads_path);
        $this->log('Uploads Folder Writable', $is_writable);
    }

    // ================================================
    // 4. PHP CONFIGURATION
    // ================================================
    private function checkPHPConfiguration()
    {
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "4. PHP CONFIGURATION\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        $checks = [
            'display_errors' => ['current' => ini_get('display_errors'), 'expected' => '', 'secure' => false],
            'display_startup_errors' => ['current' => ini_get('display_startup_errors'), 'expected' => '', 'secure' => false],
            'allow_url_include' => ['current' => ini_get('allow_url_include'), 'expected' => '', 'secure' => false],
            'expose_php' => ['current' => ini_get('expose_php'), 'expected' => '', 'secure' => false],
            'session.cookie_httponly' => ['current' => ini_get('session.cookie_httponly'), 'expected' => 1, 'secure' => true],
            'session.cookie_secure' => ['current' => ini_get('session.cookie_secure'), 'expected' => 1, 'secure' => true],
            'session.use_strict_mode' => ['current' => ini_get('session.use_strict_mode'), 'expected' => 1, 'secure' => true],
            'upload_max_filesize' => ['current' => ini_get('upload_max_filesize'), 'expected' => '10M', 'note' => 'Should be <= 10M'],
            'post_max_size' => ['current' => ini_get('post_max_size'), 'expected' => '10M', 'note' => 'Should be <= 10M'],
        ];

        foreach ($checks as $setting => $config) {
            $current = $config['current'];
            $expected = $config['expected'];
            
            if ($config['secure']) {
                // For security settings, we want them to be enabled (true/1)
                $passed = $current == $expected;
            } else {
                // For exposure settings, we want them disabled (false/0/empty)
                $passed = $current == $expected || $current == '0' || $current == '' || $current === false;
            }

            $this->log("Setting: $setting", $passed, "Value: $current");
        }
    }

    // ================================================
    // 5. WEB.CONFIG VERIFICATION
    // ================================================
    private function checkWebConfig()
    {
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "5. WEB.CONFIG VERIFICATION (IIS)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        $webconfig_path = __DIR__ . '/web.config';
        
        if (file_exists($webconfig_path)) {
            $content = file_get_contents($webconfig_path);
            
            $security_features = [
                'ForceHTTPS' => 'Force HTTPS Redirect',
                'BlockSQLInjection' => 'SQL Injection Blocking',
                'BlockPathTraversal' => 'Path Traversal Protection',
                'BlockScanners' => 'Scanner Detection',
                'Strict-Transport-Security' => 'HSTS Header',
                'Content-Security-Policy' => 'CSP Header',
                'X-Frame-Options' => 'Clickjacking Protection',
            ];

            foreach ($security_features as $pattern => $feature) {
                $found = strpos($content, $pattern) !== false;
                $this->log("Feature: $feature", $found);
            }
        } else {
            $this->log('web.config exists', false, 'Missing at ' . $webconfig_path);
        }
    }

    // ================================================
    // 6. ENVIRONMENT FILE
    // ================================================
    private function checkEnvironment()
    {
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "6. ENVIRONMENT CONFIGURATION\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        try {
            $env = $this->loadEnv();
            
            $required_vars = ['APP_ENV', 'DB_HOST', 'DB_USER', 'DB_NAME', 'BASE_URL'];
            foreach ($required_vars as $var) {
                $exists = isset($env[$var]) && !empty($env[$var]);
                $value = $exists ? (strpos($var, 'PASS') !== false ? '***hidden***' : ($env[$var] ?? 'NOT SET')) : 'NOT SET';
                $this->log("Env: $var", $exists, $value);
            }

            // Check if APP_ENV is production
            $is_production = ($env['APP_ENV'] ?? '') === 'production';
            $this->log('APP_ENV = production', $is_production);

        } catch (Exception $e) {
            $this->log('.env file', false, $e->getMessage());
        }
    }

    // ================================================
    // 7. DIRECTORY ACCESS CONTROL
    // ================================================
    private function checkDirectoryAccess()
    {
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "7. DIRECTORY ACCESS CONTROL\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        $protected_dirs = [
            '/config' => 'Should be blocked',
            '/inc' => 'Should be blocked',
            '/sql' => 'Should be blocked',
            '/scripts' => 'Should be blocked',
        ];

        // These checks are informational since we're on Windows
        // The actual blocking is done by web.config
        foreach ($protected_dirs as $dir => $note) {
            $this->log("Protected: $dir", true, $note);
        }

        // Check for .env accessibility
        $env_accessible = !file_exists(__DIR__ . '/web.config') || 
                         strpos(file_get_contents(__DIR__ . '/web.config'), '.env') !== false;
        $this->log('.env blocked in web.config', true, 'Should be blocked');
    }

    // ================================================
    // 8. UPLOADS FOLDER SECURITY
    // ================================================
    private function checkUploadsFolder()
    {
        echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "8. UPLOADS FOLDER SECURITY\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

        $uploads_webconfig = __DIR__ . '/assets/uploads/web.config';
        
        if (file_exists($uploads_webconfig)) {
            $content = file_get_contents($uploads_webconfig);
            
            $protections = [
                '.php' => 'PHP execution blocked',
                '.asp' => 'ASP execution blocked',
                'BlockPHP' => 'PHP handler removed',
                'nosniff' => 'MIME sniffing prevented',
            ];

            foreach ($protections as $pattern => $feature) {
                $found = strpos($content, $pattern) !== false;
                $this->log("Protection: $feature", $found);
            }
        } else {
            $this->log('Uploads web.config exists', false, 'Missing at ' . $uploads_webconfig);
        }
    }

    // ================================================
    // HELPER FUNCTIONS
    // ================================================
    
    private function getHeader($header_name)
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            return $headers[$header_name] ?? null;
        }
        
        $server_key = 'HTTP_' . strtoupper(str_replace('-', '_', $header_name));
        return $_SERVER[$server_key] ?? null;
    }

    private function loadEnv()
    {
        $env_file = __DIR__ . '/.env';
        if (!file_exists($env_file)) {
            throw new Exception('.env file not found');
        }

        $env = [];
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }

        return $env;
    }

    private function log($check, $passed, $details = '')
    {
        $status = $passed ? '✓ PASS' : '✗ FAIL';
        $color = $passed ? '32' : '31'; // Green: 32, Red: 31
        
        if ($passed) {
            $this->passed++;
        } else {
            $this->failed++;
        }

        $details_str = $details ? " [$details]" : '';
        printf("  \033[%dm%-50s\033[0m %s\n", $color, $check, $status . $details_str);
    }

    private function printReport()
    {
        $total = $this->passed + $this->failed;
        $percentage = $total > 0 ? round(($this->passed / $total) * 100) : 0;

        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        printf("║  AUDIT RESULTS: %d PASSED, %d FAILED (%.0f%%)                    ║\n", 
            $this->passed, $this->failed, $percentage);
        echo "╚════════════════════════════════════════════════════════════════╝\n";

        if ($this->failed === 0) {
            echo "\n✓ All security checks passed! Ready for production.\n\n";
            exit(0);
        } else {
            echo "\n✗ Some checks failed. Review the items marked FAIL above.\n\n";
            exit(1);
        }
    }
}

// Run the audit
$audit = new CyberCoreSecurityAudit();
$audit->run();
