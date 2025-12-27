<?php
// Configuração da base de dados - ajustar para produção
// Preferir ficheiro local não versionado; depois variáveis de ambiente; depois defaults.

// 1. Tentar carregar ficheiro local (db_credentials.php)
$credsFile = __DIR__ . '/db_credentials.php';
if (file_exists($credsFile)) {
	require_once $credsFile;
}

// 2. Se não estiver definido, usar variáveis de ambiente
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'cybercore');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'cybercore');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');

// 3. Configurações do site
if (!defined('SITE_NAME')) define('SITE_NAME', getenv('SITE_NAME') ?: 'CyberCore - Área de Cliente');
if (!defined('SITE_URL')) define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/cybercore');

// SMTP / Mail settings - configure via env para produção
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'no-reply@seudominio.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'CyberCore');
