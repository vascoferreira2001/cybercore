<?php
// Configuração da base de dados - ajustar para produção
// Para localhost (XAMPP):
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cybercore');
define('DB_USER', 'root');
define('DB_PASS', '');

// Para produção (alojamento), substitua pelos valores do seu servidor:
// define('DB_HOST', 'seu_host_mysql');
// define('DB_NAME', 'seu_db_name');
// define('DB_USER', 'seu_db_user');
// define('DB_PASS', 'seu_db_pass');

// Site settings
define('SITE_NAME', 'CyberCore - Área de Cliente');
define('SITE_URL', 'http://localhost/cybercore'); // Para produção: 'https://seudominio.com'

// SMTP / Mail settings - configure para produção
define('SMTP_HOST', ''); // Ex.: 'smtp.gmail.com' ou 'mail.seudominio.com'
define('SMTP_PORT', 587);
define('SMTP_USER', ''); // Seu email SMTP
define('SMTP_PASS', ''); // Senha SMTP
define('SMTP_SECURE', 'tls'); // 'tls' ou 'ssl'
define('MAIL_FROM', 'no-reply@seudominio.com');
define('MAIL_FROM_NAME', 'CyberCore');
