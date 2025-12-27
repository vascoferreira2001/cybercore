# 📧 Sistema de Modelos de Email

Sistema profissional de templates HTML para emails automáticos do CyberCore.

## 🎯 Características

- ✅ Templates HTML responsivos e profissionais
- ✅ Interface admin para gestão visual
- ✅ Sistema de variáveis dinâmicas (`{{variavel}}`)
- ✅ Templates de sistema protegidos
- ✅ Funcionalidade de teste de envio
- ✅ Suporte para HTML + texto simples
- ✅ Variáveis globais automáticas

## 📁 Localização

**Interface Admin:** [Admin → Configuração → Modelos de Email](../admin/email-templates.php)

**Código:** [inc/email_templates.php](../inc/email_templates.php)

## 🔧 Templates Pré-definidos

### 1. Email de Verificação (`email_verification`)
**Usado em:** Registo de novos utilizadores

**Variáveis:**
- `{{user_name}}` - Nome do utilizador
- `{{verification_link}}` - Link de verificação único

**Exemplo de uso:**
```php
sendTemplatedEmail($pdo, 'email_verification', $email, $name, [
    'user_name' => 'João Silva',
    'verification_link' => SITE_URL . 'verify_email.php?token=' . $token
]);
```

### 2. Recuperação de Password (`password_reset`)
**Usado em:** Pedidos de reset de password

**Variáveis:**
- `{{user_name}}` - Nome do utilizador
- `{{reset_link}}` - Link para redefinir password

**Exemplo de uso:**
```php
sendTemplatedEmail($pdo, 'password_reset', $email, $name, [
    'user_name' => 'Maria Santos',
    'reset_link' => SITE_URL . 'reset_password.php?token=' . $token
]);
```

### 3. Email de Boas-Vindas (`welcome_email`)
**Usado em:** Após verificação bem-sucedida (opcional)

**Variáveis:**
- `{{user_name}}` - Nome do utilizador
- `{{dashboard_link}}` - Link para o dashboard

**Exemplo de uso:**
```php
sendTemplatedEmail($pdo, 'welcome_email', $email, $name, [
    'user_name' => 'Pedro Costa',
    'dashboard_link' => SITE_URL . 'dashboard.php'
]);
```

## 🌐 Variáveis Globais

Estas variáveis são **automaticamente** adicionadas a todos os emails:

| Variável | Valor | Exemplo |
|----------|-------|---------|
| `{{site_name}}` | Nome da empresa/site | "CyberCore" |
| `{{current_year}}` | Ano atual | "2025" |
| `{{site_url}}` | URL base do site | "https://cybercore.pt/" |

## 💻 API de Programação

### Enviar Email com Template

```php
require_once __DIR__ . '/inc/email_templates.php';

$success = sendTemplatedEmail(
    $pdo,                      // PDO connection
    'email_verification',      // Template key
    'user@example.com',        // Recipient email
    'Nome do Utilizador',      // Recipient name
    [                          // Variables array
        'user_name' => 'João',
        'verification_link' => 'https://...'
    ]
);

if ($success) {
    echo "Email enviado!";
} else {
    echo "Erro ao enviar email.";
}
```

### Carregar Template

```php
$template = getEmailTemplate($pdo, 'email_verification');

if ($template) {
    echo $template['subject'];
    echo $template['body_html'];
}
```

### Listar Todos os Templates

```php
$templates = listEmailTemplates($pdo);

foreach ($templates as $template) {
    echo $template['template_name'];
}
```

### Criar Template Personalizado

```php
$templateId = createEmailTemplate($pdo, [
    'template_key' => 'invoice_reminder',
    'template_name' => 'Lembrete de Fatura',
    'subject' => 'Fatura Pendente - {{invoice_number}}',
    'body_html' => '<html>...</html>',
    'body_text' => 'Versão texto...',
    'variables' => '["invoice_number", "amount", "due_date"]',
    'is_active' => 1
]);
```

### Atualizar Template

```php
$success = updateEmailTemplate($pdo, $templateId, [
    'subject' => 'Novo assunto',
    'body_html' => '<html>...</html>',
    'is_active' => 1
]);
```

### Eliminar Template

```php
// Apenas templates não-sistema podem ser eliminados
$success = deleteEmailTemplate($pdo, $templateId);
```

## 🎨 Criar Templates HTML

### Estrutura Recomendada

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f4f4">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:20px 0">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff">
                    <!-- Header -->
                    <tr>
                        <td style="padding:40px 30px;background:#007bff">
                            <h1 style="margin:0;color:#fff">{{site_name}}</h1>
                        </td>
                    </tr>
                    
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px 30px">
                            <h2>Olá, {{user_name}}!</h2>
                            <p>Seu conteúdo aqui...</p>
                            
                            <!-- Botão CTA -->
                            <table width="100%" style="margin:30px 0">
                                <tr>
                                    <td align="center">
                                        <a href="{{action_link}}" 
                                           style="display:inline-block;padding:15px 40px;background:#007bff;color:#fff;text-decoration:none;border-radius:5px">
                                            Clique Aqui
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px;text-align:center;background:#f8f9fa">
                            <p style="margin:0;color:#999;font-size:12px">
                                © {{current_year}} {{site_name}}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
```

### Boas Práticas

1. **Use tabelas para layout** - Melhor suporte em clientes de email
2. **Inline CSS** - Muitos clientes ignoram `<style>` tags
3. **Largura máxima 600px** - Ótimo para desktop e mobile
4. **Cores seguras** - Evite gradientes complexos
5. **Botões como links** - Use `<a>` com padding, não `<button>`
6. **Teste em múltiplos clientes** - Gmail, Outlook, Apple Mail, etc.

## 🔒 Segurança

### Templates de Sistema
- Marcados com `is_system = 1`
- Não podem ser eliminados via interface
- Podem ser editados (conteúdo HTML apenas)
- Chave (`template_key`) é imutável

### Templates Personalizados
- Criados com `is_system = 0`
- Podem ser totalmente geridos (criar/editar/eliminar)
- Apenas Gestor tem acesso

### Validação
- Chaves únicas (`template_key`)
- Apenas letras minúsculas, números e underscore
- Subject e body_html são obrigatórios

## 🧪 Testar Templates

### Via Interface Admin
1. Aceder a **Admin → Configuração → Modelos de Email**
2. Clicar em "Testar" no template desejado
3. Inserir email de destino
4. Verificar inbox (e spam)

### Via Código
```php
// Enviar teste para desenvolvimento
if (ENVIRONMENT === 'development') {
    sendTemplatedEmail($pdo, 'email_verification', 'dev@exemplo.com', 'Dev', [
        'user_name' => 'Desenvolvedor Teste',
        'verification_link' => SITE_URL . 'verify_email.php?token=TEST123'
    ]);
}
```

## 📊 Base de Dados

### Estrutura da Tabela

```sql
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) UNIQUE NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body_html LONGTEXT NOT NULL,
    body_text LONGTEXT,
    variables TEXT COMMENT 'JSON array',
    is_system TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Índices
- `template_key` - Busca rápida por chave

### Dados Iniciais
Templates de sistema são criados via `INSERT IGNORE` no [schema.sql](../sql/schema.sql)

## 🆘 Troubleshooting

### Email não chega
1. Verificar configurações SMTP em **Admin → Definições → Email**
2. Verificar se template está ativo (`is_active = 1`)
3. Consultar logs do sistema
4. Verificar pasta de spam

### Template não aparece
1. Confirmar que `is_active = 1`
2. Verificar se chave (`template_key`) está correta
3. Limpar cache do browser

### Variáveis não substituídas
1. Verificar sintaxe: `{{variavel}}` (duplas chavetas)
2. Confirmar que variável foi passada no array
3. Verificar nome exato (case-sensitive)

### HTML quebrado
1. Validar HTML (W3C Validator)
2. Usar tabelas em vez de divs
3. Inline CSS
4. Testar em múltiplos clientes de email

## 🚀 Casos de Uso Avançados

### Email de Fatura

```php
createEmailTemplate($pdo, [
    'template_key' => 'invoice_notification',
    'template_name' => 'Notificação de Fatura',
    'subject' => 'Nova fatura #{{invoice_number}} - {{site_name}}',
    'body_html' => '...HTML com tabela de items...',
    'variables' => '["invoice_number", "amount", "due_date", "items_table"]'
]);

// Enviar
sendTemplatedEmail($pdo, 'invoice_notification', $client->email, $client->name, [
    'invoice_number' => 'INV-2025-001',
    'amount' => '99,99€',
    'due_date' => '31/12/2025',
    'items_table' => '<table>...</table>'
]);
```

### Email de Suporte

```php
sendTemplatedEmail($pdo, 'ticket_reply', $user->email, $user->name, [
    'ticket_number' => '#12345',
    'support_agent' => 'João Silva',
    'reply_message' => 'Sua resposta...',
    'ticket_link' => SITE_URL . 'support.php?id=12345'
]);
```

### Newsletter

```php
// Criar template para newsletter mensal
createEmailTemplate($pdo, [
    'template_key' => 'monthly_newsletter',
    'template_name' => 'Newsletter Mensal',
    'subject' => '📬 Newsletter {{month}} - {{site_name}}',
    'body_html' => '...design de newsletter...',
    'variables' => '["month", "highlights", "cta_link"]'
]);
```

---

**Documentação completa:** [docs/EMAIL_VERIFICATION.md](EMAIL_VERIFICATION.md)

**Última atualização:** 25 de dezembro de 2025
