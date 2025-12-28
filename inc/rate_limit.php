<?php
/**
 * CyberCore - Login Rate Limiting System
 * 
 * Prevents brute force attacks on login
 * Per-IP and per-username rate limiting
 * MySQL backed
 * 
 * Installation:
 * 1. Create login_attempts table (included in schema.sql)
 * 2. Include this file in login.php
 * 3. Use cybercore_check_rate_limit() before authentication
 */

class RateLimiter
{
    private $pdo;
    private $table = 'login_attempts';
    private $max_attempts_per_ip = 10;  // 10 attempts per IP
    private $max_attempts_per_user = 5; // 5 attempts per username
    private $lockout_duration = 900;    // 15 minutes in seconds
    private $attempt_window = 3600;     // 1 hour window

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Check if IP/username is rate limited
     * Returns: [
     *   'limited' => bool,
     *   'message' => string,
     *   'retry_after' => int (seconds until retry allowed)
     * ]
     */
    public function check($ip_address, $username = null)
    {
        // Check by IP
        $ip_check = $this->checkByIP($ip_address);
        if ($ip_check['limited']) {
            return $ip_check;
        }

        // Check by username (if provided)
        if ($username) {
            $user_check = $this->checkByUsername($username);
            if ($user_check['limited']) {
                return $user_check;
            }
        }

        return [
            'limited' => false,
            'message' => 'OK',
            'retry_after' => 0
        ];
    }

    /**
     * Log failed login attempt
     */
    public function logFailure($ip_address, $username = null)
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} (ip_address, email, attempted_at) 
                 VALUES (:ip, :email, NOW())"
            );
            
            $stmt->execute([
                ':ip' => $ip_address,
                ':email' => $username
            ]);

            // Cleanup old entries
            $this->cleanup();

        } catch (Exception $e) {
            error_log("RateLimiter error: " . $e->getMessage());
        }
    }

    /**
     * Log successful login (clears attempts for this user)
     */
    public function logSuccess($ip_address, $username = null)
    {
        try {
            if ($username) {
                $stmt = $this->pdo->prepare(
                    "DELETE FROM {$this->table} 
                     WHERE email = :email 
                     AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 DAY)"
                );
                $stmt->execute([':email' => $username]);
            }
        } catch (Exception $e) {
            error_log("RateLimiter cleanup error: " . $e->getMessage());
        }
    }

    /**
     * Check if IP is rate limited
     */
    private function checkByIP($ip_address)
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as attempts, 
                        MAX(attempted_at) as last_attempt 
                 FROM {$this->table} 
                 WHERE ip_address = :ip 
                 AND attempted_at > DATE_SUB(NOW(), INTERVAL :window SECOND)"
            );
            
            $stmt->execute([
                ':ip' => $ip_address,
                ':window' => $this->attempt_window
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $attempts = (int) $result['attempts'];

            if ($attempts >= $this->max_attempts_per_ip) {
                $last_attempt = new DateTime($result['last_attempt']);
                $retry_time = $last_attempt->getTimestamp() + $this->lockout_duration;
                $seconds_left = $retry_time - time();

                return [
                    'limited' => true,
                    'message' => "Too many login attempts from your IP. Please try again in " . 
                                ceil($seconds_left / 60) . " minutes.",
                    'retry_after' => max(0, $seconds_left)
                ];
            }

            return [
                'limited' => false,
                'message' => 'OK',
                'retry_after' => 0
            ];

        } catch (Exception $e) {
            error_log("RateLimiter IP check error: " . $e->getMessage());
            return ['limited' => false, 'message' => 'OK', 'retry_after' => 0];
        }
    }

    /**
     * Check if username is rate limited
     */
    private function checkByUsername($username)
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as attempts, 
                        MAX(attempted_at) as last_attempt 
                 FROM {$this->table} 
                 WHERE email = :email 
                 AND attempted_at > DATE_SUB(NOW(), INTERVAL :window SECOND)"
            );
            
            $stmt->execute([
                ':email' => $username,
                ':window' => $this->attempt_window
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $attempts = (int) $result['attempts'];

            if ($attempts >= $this->max_attempts_per_user) {
                $last_attempt = new DateTime($result['last_attempt']);
                $retry_time = $last_attempt->getTimestamp() + $this->lockout_duration;
                $seconds_left = $retry_time - time();

                return [
                    'limited' => true,
                    'message' => "Too many failed login attempts for this account. " .
                                "Please try again in " . ceil($seconds_left / 60) . " minutes.",
                    'retry_after' => max(0, $seconds_left)
                ];
            }

            return [
                'limited' => false,
                'message' => 'OK',
                'retry_after' => 0
            ];

        } catch (Exception $e) {
            error_log("RateLimiter user check error: " . $e->getMessage());
            return ['limited' => false, 'message' => 'OK', 'retry_after' => 0];
        }
    }

    /**
     * Clean up old attempts (older than attempt_window)
     */
    private function cleanup()
    {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->table} 
                 WHERE attempted_at < DATE_SUB(NOW(), INTERVAL :window SECOND)"
            );
            
            $stmt->execute([':window' => $this->attempt_window]);
        } catch (Exception $e) {
            error_log("RateLimiter cleanup error: " . $e->getMessage());
        }
    }

    /**
     * Reset all attempts for an IP (admin function)
     */
    public function resetIP($ip_address)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE ip_address = :ip");
            $stmt->execute([':ip' => $ip_address]);
            return true;
        } catch (Exception $e) {
            error_log("RateLimiter reset error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset all attempts for a username (admin function)
     */
    public function resetUser($username)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE email = :email");
            $stmt->execute([':email' => $username]);
            return true;
        } catch (Exception $e) {
            error_log("RateLimiter reset error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Helper function - use in login.php
 * 
 * Example usage:
 * 
 * if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 *     $email = $_POST['email'] ?? '';
 *     $ip = $_SERVER['REMOTE_ADDR'];
 *     
 *     $rate_check = cybercore_check_rate_limit($pdo, $ip, $email);
 *     if ($rate_check['limited']) {
 *         $flash_error = $rate_check['message'];
 *     } else {
 *         // Attempt login
 *         if ($login_successful) {
 *             cybercore_log_success($pdo, $ip, $email);
 *         } else {
 *             cybercore_log_failure($pdo, $ip, $email);
 *             $flash_error = "Invalid credentials";
 *         }
 *     }
 * }
 */

function cybercore_get_rate_limiter($pdo)
{
    static $limiter = null;
    if ($limiter === null) {
        $limiter = new RateLimiter($pdo);
    }
    return $limiter;
}

function cybercore_check_rate_limit($pdo, $ip_address, $username = null)
{
    $limiter = cybercore_get_rate_limiter($pdo);
    return $limiter->check($ip_address, $username);
}

function cybercore_log_failure($pdo, $ip_address, $username = null)
{
    $limiter = cybercore_get_rate_limiter($pdo);
    $limiter->logFailure($ip_address, $username);
}

function cybercore_log_success($pdo, $ip_address, $username = null)
{
    $limiter = cybercore_get_rate_limiter($pdo);
    $limiter->logSuccess($ip_address, $username);
}

function cybercore_reset_rate_limit_ip($pdo, $ip_address)
{
    $limiter = cybercore_get_rate_limiter($pdo);
    return $limiter->resetIP($ip_address);
}

function cybercore_reset_rate_limit_user($pdo, $username)
{
    $limiter = cybercore_get_rate_limiter($pdo);
    return $limiter->resetUser($username);
}
