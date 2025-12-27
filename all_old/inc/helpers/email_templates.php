<?php
/**
 * Email Templates Helper
 * Funções para carregar e processar modelos de email
 */

/**
 * Carrega um template de email da base de dados
 * @param PDO $pdo
 * @param string $templateKey
 * @return array|null Template data or null if not found
 */
function getEmailTemplate($pdo, $templateKey) {
    $stmt = $pdo->prepare('SELECT * FROM email_templates WHERE template_key = ? AND is_active = 1');
    $stmt->execute([$templateKey]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Processa variáveis no template substituindo placeholders
 * @param string $content
 * @param array $variables
 * @return string
 */
function processTemplateVariables($content, $variables) {
    foreach ($variables as $key => $value) {
        $content = str_replace('{{' . $key . '}}', $value, $content);
    }
    return $content;
}

/**
 * Envia email usando template
 * @param PDO $pdo
 * @param string $templateKey Template key (e.g., 'email_verification')
 * @param string $toEmail Recipient email
 * @param string $toName Recipient name
 * @param array $variables Template variables (e.g., ['user_name' => 'João'])
 * @return bool Success status
 */
function sendTemplatedEmail($pdo, $templateKey, $toEmail, $toName, $variables = []) {
    require_once __DIR__ . '/mailer.php';
    require_once __DIR__ . '/settings.php';
    
    // Carregar template
    $template = getEmailTemplate($pdo, $templateKey);
    if (!$template) {
        error_log("Email template not found: $templateKey");
        return false;
    }
    
    // Adicionar variáveis globais automaticamente
    $globalVars = [
        'site_name' => getSetting($pdo, 'company_name') ?: SITE_NAME,
        'current_year' => date('Y'),
        'site_url' => rtrim(SITE_URL, '/')
    ];
    $variables = array_merge($globalVars, $variables);
    
    // Processar subject e body
    $subject = processTemplateVariables($template['subject'], $variables);
    $bodyHtml = processTemplateVariables($template['body_html'], $variables);
    $bodyText = $template['body_text'] ? processTemplateVariables($template['body_text'], $variables) : null;
    
    // Enviar email
    try {
        // Enviar com ordem correta de argumentos: to, subject, html, text
        return sendMail($toEmail, $subject, $bodyHtml, $bodyText);
    } catch (Exception $e) {
        error_log("Failed to send templated email '$templateKey' to $toEmail: " . $e->getMessage());
        return false;
    }
}

/**
 * Lista todos os templates disponíveis
 * @param PDO $pdo
 * @param bool $includeInactive Include inactive templates
 * @return array
 */
function listEmailTemplates($pdo, $includeInactive = false) {
    $sql = 'SELECT * FROM email_templates';
    if (!$includeInactive) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY template_name ASC';
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Atualiza um template de email
 * @param PDO $pdo
 * @param int $templateId
 * @param array $data
 * @return bool
 */
function updateEmailTemplate($pdo, $templateId, $data) {
    $allowed = ['template_name', 'subject', 'body_html', 'body_text', 'is_active'];
    $sets = [];
    $values = [];
    
    foreach ($allowed as $field) {
        if (isset($data[$field])) {
            $sets[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    
    if (empty($sets)) {
        return false;
    }
    
    $values[] = $templateId;
    $sql = 'UPDATE email_templates SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($values);
}

/**
 * Cria novo template (apenas não-sistema)
 * @param PDO $pdo
 * @param array $data
 * @return int|false Template ID or false on failure
 */
function createEmailTemplate($pdo, $data) {
    $required = ['template_key', 'template_name', 'subject', 'body_html'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }
    
    $stmt = $pdo->prepare('
        INSERT INTO email_templates (template_key, template_name, subject, body_html, body_text, variables, is_system, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 0, ?)
    ');
    
    $success = $stmt->execute([
        $data['template_key'],
        $data['template_name'],
        $data['subject'],
        $data['body_html'],
        $data['body_text'] ?? '',
        $data['variables'] ?? '[]',
        $data['is_active'] ?? 1
    ]);
    
    return $success ? $pdo->lastInsertId() : false;
}

/**
 * Elimina template (apenas não-sistema)
 * @param PDO $pdo
 * @param int $templateId
 * @return bool
 */
function deleteEmailTemplate($pdo, $templateId) {
    $stmt = $pdo->prepare('DELETE FROM email_templates WHERE id = ? AND is_system = 0');
    return $stmt->execute([$templateId]);
}
