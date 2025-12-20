<?php
// Configuração via variáveis de ambiente (ou .env no root). Mais seguro para produção.
// Se existir um ficheiro .env no root do projecto, carregamos as variáveis.
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
	$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || strpos($line, '#') === 0) continue;
		if (strpos($line, '=') === false) continue;
		list($name, $value) = explode('=', $line, 2);
		$name = trim($name);
		$value = trim($value);
		if (preg_match('/^([\"\'])(.*)\\1$/', $value, $m)) $value = $m[2];
		putenv("$name=$value");
		$_ENV[$name] = $value;
		$_SERVER[$name] = $value;
	}
}

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
