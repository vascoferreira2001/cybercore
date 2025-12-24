<?php
// Wrapper para envio de emails via SMTP nativo (sem dependências)
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

    // Se não tiver configuração SMTP, usar mail() como fallback
    if (empty($smtpHost)) {
        $encodedFromName = mb_encode_mimeheader($fromName, 'UTF-8', 'Q');
        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'Q');

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "From: {$encodedFromName} <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $ok = mail($to, $encodedSubject, $htmlBody, $headers);
        if (!$ok) {
            error_log('Envio de email falhou para ' . $to . ' via mail()');
        }
        return $ok;
    }

    // Enviar via SMTP nativo
    try {
        return sendViaSMTP($smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpSecure, $from, $fromName, $to, $subject, $htmlBody, $plainBody);
    } catch (Exception $e) {
        error_log('SMTP Error: ' . $e->getMessage());
        return false;
    }
}

function sendViaSMTP($host, $port, $user, $pass, $secure, $from, $fromName, $to, $subject, $htmlBody, $plainBody = '') {
    $timeout = 30;
    $newline = "\r\n";
    
    // Conectar ao servidor SMTP
    $errno = 0;
    $errstr = '';
    
    if ($secure === 'ssl') {
        $host = 'ssl://' . $host;
    }
    
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    
    if (!$socket) {
        throw new Exception("Não foi possível conectar ao servidor SMTP: $errstr ($errno)");
    }
    
    stream_set_timeout($socket, $timeout);
    
    // Ler resposta inicial
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) !== '220') {
        fclose($socket);
        throw new Exception("Resposta inválida do servidor SMTP: $response");
    }
    
    // EHLO
    fputs($socket, "EHLO " . gethostname() . $newline);
    $response = fgets($socket, 515);
    
    // Ler todas as linhas de resposta EHLO
    while (substr($response, 3, 1) === '-') {
        $response = fgets($socket, 515);
    }
    
    // STARTTLS se necessário
    if ($secure === 'tls') {
        fputs($socket, "STARTTLS" . $newline);
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            throw new Exception("STARTTLS falhou: $response");
        }
        
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            throw new Exception("Falha ao ativar encriptação TLS");
        }
        
        // EHLO novamente após STARTTLS
        fputs($socket, "EHLO " . gethostname() . $newline);
        $response = fgets($socket, 515);
        while (substr($response, 3, 1) === '-') {
            $response = fgets($socket, 515);
        }
    }
    
    // Autenticação
    if (!empty($user) && !empty($pass)) {
        fputs($socket, "AUTH LOGIN" . $newline);
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            throw new Exception("AUTH LOGIN falhou: $response");
        }
        
        fputs($socket, base64_encode($user) . $newline);
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) !== '334') {
            fclose($socket);
            throw new Exception("Utilizador inválido: $response");
        }
        
        fputs($socket, base64_encode($pass) . $newline);
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) !== '235') {
            fclose($socket);
            throw new Exception("Password inválida: $response");
        }
    }
    
    // MAIL FROM
    fputs($socket, "MAIL FROM: <{$from}>" . $newline);
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        throw new Exception("MAIL FROM falhou: $response");
    }
    
    // RCPT TO
    fputs($socket, "RCPT TO: <{$to}>" . $newline);
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        throw new Exception("RCPT TO falhou: $response");
    }
    
    // DATA
    fputs($socket, "DATA" . $newline);
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) !== '354') {
        fclose($socket);
        throw new Exception("DATA falhou: $response");
    }
    
    // Cabeçalhos e corpo (codificados para UTF-8)
    $encodedFromName = mb_encode_mimeheader($fromName, 'UTF-8', 'Q');
    $headers = "From: {$encodedFromName} <{$from}>" . $newline;
    $headers .= "To: <{$to}>" . $newline;
    $headers .= "Subject: " . mb_encode_mimeheader($subject, 'UTF-8', 'Q') . $newline;
    $headers .= "MIME-Version: 1.0" . $newline;
    $headers .= "Content-Type: text/html; charset=UTF-8" . $newline;
    $headers .= "Content-Transfer-Encoding: 8bit" . $newline;
    $headers .= "Date: " . date('r') . $newline;
    
    $message = $headers . $newline . $htmlBody . $newline . "." . $newline;
    
    fputs($socket, $message);
    $response = fgets($socket, 515);
    if (substr($response, 0, 3) !== '250') {
        fclose($socket);
        throw new Exception("Envio de mensagem falhou: $response");
    }
    
    // QUIT
    fputs($socket, "QUIT" . $newline);
    fclose($socket);
    
    return true;
}
