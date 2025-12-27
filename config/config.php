<?php
// Secure configuration bootstrap for CyberCore (PHP 8, Plesk compatible)
// - Loads environment variables (supports local .env)
// - Sets error handling defaults per environment
// - Declares config constants used across the app

// Simple env helper (avoids external libs) ---------------------------------
if (!function_exists('cybercore_env')) {
	function cybercore_env(string $key, $default = null)
	{
		$value = getenv($key);
		return ($value === false || $value === null) ? $default : $value;
	}
}

// Lightweight .env loader (only key=value lines, ignores comments) --------
if (!defined('CYBERCORE_ENV_LOADED')) {
	$envPath = __DIR__ . '/../.env';
	if (is_file($envPath)) {
		$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			if (str_starts_with(trim($line), '#')) continue;
			[$k, $v] = array_pad(explode('=', $line, 2), 2, null);
			if ($k !== null && $v !== null && getenv($k) === false) putenv($k . '=' . $v);
		}
	}
	define('CYBERCORE_ENV_LOADED', true);
}

// Environment --------------------------------------------------------------
define('CYBERCORE_ENV', cybercore_env('APP_ENV', 'development'));
define('APP_DEBUG', cybercore_env('APP_DEBUG', CYBERCORE_ENV !== 'production'));
define('CYBERCORE_NAME', cybercore_env('APP_NAME', 'CyberCore – Alojamento Web & Soluções Digitais'));
define('BASE_URL', cybercore_env('APP_URL', '/'));

// Database -----------------------------------------------------------------
define('DB_HOST', cybercore_env('DB_HOST', 'localhost'));
define('DB_NAME', cybercore_env('DB_NAME', 'cybercore'));
define('DB_USER', cybercore_env('DB_USER', 'cybercore'));
define('DB_PASS', cybercore_env('DB_PASS', 'changeme'));
define('DB_PORT', (int) cybercore_env('DB_PORT', 3306));

// Mail / misc --------------------------------------------------------------
define('MAIL_FROM', cybercore_env('MAIL_FROM', 'no-reply@cybercore.test'));
define('MAIL_NAME', cybercore_env('MAIL_NAME', 'CyberCore'));

// Error handling defaults --------------------------------------------------
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
if (!ini_get('error_log')) {
	// In hosting/Plesk, this falls back to the default domain error log
	ini_set('error_log', __DIR__ . '/../logs/php-error.log');
}

// Timezone (override via APP_TZ) -----------------------------------------
date_default_timezone_set(cybercore_env('APP_TZ', 'Europe/Lisbon'));
