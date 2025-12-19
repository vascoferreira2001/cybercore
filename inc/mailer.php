<?php
// Wrapper para envio de emails. Usa PHPMailer se disponível, senão fallback para mail().
require_once __DIR__ . '/config.php';
function sendMail($to, $subject, $htmlBody, $plainBody = '') {
    // Try to load Composer autoload
    $vendor = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($vendor)) {
        require_once $vendor;
        $mail = new PHPMailer\\PHPMailer\\PHPMailer(true);
        try {
            // Se SMTP estiver configurado, usar SMTP
            if (!empty(SMTP_HOST)) {
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->Port = SMTP_PORT;
                $mail->SMTPAuth = !empty(SMTP_USER);
                if (!empty(SMTP_USER)) {
                    $mail->Username = SMTP_USER;
                    $mail->Password = SMTP_PASS;
                }
                if (!empty(SMTP_SECURE)) $mail->SMTPSecure = SMTP_SECURE;
            }
            $from = MAIL_FROM ?: 'no-reply@localhost';
            $fromName = MAIL_FROM_NAME ?: 'CyberCore';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            $mail->AltBody = $plainBody ?: strip_tags($htmlBody);
            $mail->CharSet = 'UTF-8';
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    } else {
        // Fallback simples usando mail(); XAMPP precisa de configurar SMTP no php.ini
        $headers = "MIME-Version: 1.0\\r\\n";
        $headers .= "Content-type: text/html; charset=UTF-8\\r\\n";
        $headers .= "From: " . (defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@localhost') . "\\r\\n";
        return mail($to, $subject, $htmlBody, $headers);
    }
}
