<?php
// Common bootstrap for CyberCore (sessions, helpers, security)

// Secure session start -----------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
	$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'domain' => '',
		'secure' => $secure,
		'httponly' => true,
		'samesite' => 'Lax',
	]);
	session_start();
}

// CSRF helpers ------------------------------------------------------------
if (!function_exists('csrf_token')) {
	function csrf_token(): string
	{
		if (empty($_SESSION['csrf_token'])) {
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		}
		return $_SESSION['csrf_token'];
	}
}

if (!function_exists('csrf_validate')) {
	function csrf_validate(?string $token): bool
	{
		return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
	}
}

// Password helpers --------------------------------------------------------
if (!function_exists('password_hash_secure')) {
	function password_hash_secure(string $password): string
	{
		$cost = (int) cybercore_env('APP_PASSWORD_COST', 12);
		return password_hash($password, PASSWORD_DEFAULT, ['cost' => $cost]);
	}
}

if (!function_exists('password_verify_secure')) {
	function password_verify_secure(string $password, string $hash): bool
	{
		return password_verify($password, $hash);
	}
}

if (!function_exists('password_needs_rehash_secure')) {
	function password_needs_rehash_secure(string $hash): bool
	{
		$cost = (int) cybercore_env('APP_PASSWORD_COST', 12);
		return password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => $cost]);
	}
}

// Basic exception logging (honors php.ini error_log) ----------------------
set_exception_handler(function (Throwable $e) {
	error_log('[EXCEPTION] ' . $e->getMessage());
	if (APP_DEBUG) {
		echo 'Erro: ' . htmlspecialchars($e->getMessage());
	}
});
