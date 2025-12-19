<?php
// Wrapper para envio de emails. Usa PHPMailer se disponível, senão fallback para mail().
require_once __DIR__ . '/config.php';
function sendMail($to, $subject, $htmlBody, $plainBody = '') {
    // Fallback simples usando mail(); Configure SMTP no php.ini para XAMPP
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . (defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@localhost') . "\r\n";
    return mail($to, $subject, $htmlBody, $headers);
}
