<?php
require_once __DIR__ . '/db_credentials.php';

// Configuração da base de dados - ajustar para produção
// Para localhost (XAMPP):

// Database settings (read from env, fallback to safe defaults)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'cybercore');
define('DB_USER', getenv('DB_USER') ?: 'cybercore');
define('DB_PASS', getenv('DB_PASS') ?: '0NLVst#6ibr1h?fd');

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
