<?php
// Wrapper para envio de emails. Usa configurações guardadas ou constantes e faz fallback para mail().
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';

function sendMail($to, $subject, $htmlBody, $plainBody = '') {
    $from = defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@localhost';
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'CyberCore';
    $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : '';
    $smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 587;
    $smtpUser = defined('SMTP_USER') ? SMTP_USER : '';
    $smtpPass = defined('SMTP_PASS') ? SMTP_PASS : '';
    $smtpSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';

    // Tentar sobrepor com valores da base de dados
    try {
        $pdo = getDB();
        $from = getSetting($pdo, 'smtp_from', $from);
        $fromName = getSetting($pdo, 'smtp_from_name', $fromName);
        $smtpHost = getSetting($pdo, 'smtp_host', $smtpHost);
        $smtpPort = getSetting($pdo, 'smtp_port', $smtpPort);
        $smtpUser = getSetting($pdo, 'smtp_user', $smtpUser);
        $smtpPass = getSetting($pdo, 'smtp_pass', $smtpPass);
        $smtpSecure = getSetting($pdo, 'smtp_secure', $smtpSecure);
    } catch (Throwable $e) {
        error_log('Mailer settings fallback: ' . $e->getMessage());
    }

    // Fallback simples usando mail(); Configure SMTP no servidor (sendmail/postfix/php.ini)
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";

    // Nota: Sem PHPMailer usamos mail(); SMTP host/port serão usados apenas se configurados no sistema/php.ini
    $bodyToSend = $htmlBody;
    if (empty($plainBody)) {
        $plainBody = strip_tags($htmlBody);
    }

    $ok = mail($to, $subject, $bodyToSend, $headers);
    if (!$ok) {
        error_log('Envio de email falhou para ' . $to . ' via mail()');
    }
    return $ok;
}
