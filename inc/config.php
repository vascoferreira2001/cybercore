<?php
// Configuração da base de dados - ajustar para produção
// Preferir variáveis de ambiente; permitir fallback opcional via ficheiro não versionado.

// Fallback opcional: se existir `inc/db_credentials.php`, incluir (não deve ser versionado)
$credsFile = __DIR__ . '/db_credentials.php';
if (file_exists($credsFile)) {
	require_once $credsFile;
}

// Database settings (read from env, fallback to safe defaults)
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'cybercore');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'cybercore');
// Por segurança, evitar passwords hardcoded; usar env ou o ficheiro local.
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');

// Site settings
define('SITE_NAME', getenv('SITE_NAME') ?: 'CyberCore - Área de Cliente');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/cybercore');

// SMTP / Mail settings - configure via env para produção
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'no-reply@seudominio.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'CyberCore');
