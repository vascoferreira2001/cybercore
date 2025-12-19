<?php
// Configuração da base de dados - ajustar conforme o seu XAMPP
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cybercore');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site settings
define('SITE_NAME', 'CyberCore - Área de Cliente');
// Base URL do site (usado para links em emails)
define('SITE_URL', 'http://localhost/cybercore');

// SMTP / Mail settings - configure para usar envio real
// Se SMTP_HOST estiver vazio, o wrapper usará mail() como fallback.
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_SECURE', 'tls'); // 'tls' ou 'ssl' ou ''
define('MAIL_FROM', 'no-reply@localhost');
define('MAIL_FROM_NAME', 'CyberCore');
