# Sistema de Verificação de Email

## Visão Geral
Todos os novos utilizadores que se registam têm de verificar o seu email antes de poderem fazer login. Esta medida aumenta a segurança e reduz contas falsas/spam.

**Sistema de Templates:** Os emails são enviados usando templates HTML profissionais geridos via **Admin → Configuração → Modelos de Email**.

## Funcionamento

### 1. Registo ([register.php](../register.php))
- Utilizador preenche formulário de registo
- Sistema gera token aleatório de 32 bytes (64 caracteres hex)
- Token válido por 24 horas
- Email de verificação enviado usando template `email_verification`
- Utilizador redirecionado para [registration_success.php](../registration_success.php) com instruções

### 2. Verificação ([verify_email.php](../verify_email.php))
- Utilizador clica no link recebido por email
- Sistema valida token e prazo de validade
- Se válido: marca `email_verified = 1` na base de dados
- Mensagem de sucesso com link para login
- Se inválido/expirado: mensagem de erro

### 3. Login ([login.php](../login.php))
- Sistema verifica se utilizador é staff (Gestor, Suporte ao Cliente, Suporte Técnica, Suporte Financeira)
- **Staff:** login permitido sem verificação (contas criadas por administradores)
- **Clientes:** login bloqueado se `email_verified = 0`
- Mensagem clara para verificar email antes de fazer login

## Sistema de Templates de Email

### Gestão de Templates
Aceda a **Admin → Configuração → Modelos de Email** para:
- ✅ Editar templates existentes (HTML e texto)
- ✅ Criar novos templates personalizados
- ✅ Testar envio de emails
- ✅ Ativar/desativar templates
- ✅ Ver variáveis disponíveis

### Templates Pré-definidos
| Template Key | Descrição | Variáveis |
|--------------|-----------|-----------|
| `email_verification` | Email de verificação de registo | `{{user_name}}`, `{{verification_link}}` |
| `password_reset` | Recuperação de password | `{{user_name}}`, `{{reset_link}}` |
| `welcome_email` | Boas-vindas após verificação | `{{user_name}}`, `{{dashboard_link}}` |

### Variáveis Globais
Automaticamente disponíveis em todos os templates:
- `{{site_name}}` - Nome da empresa/site
- `{{current_year}}` - Ano atual
- `{{site_url}}` - URL base do site

### Como Usar Templates no Código
```php
require_once __DIR__ . '/inc/email_templates.php';

// Enviar email usando template
sendTemplatedEmail($pdo, 'email_verification', $email, $name, [
    'user_name' => 'João Silva',
    'verification_link' => 'https://...'
]);
```

## Base de Dados

### Tabela `users`
```sql
email_verified TINYINT(1) DEFAULT 0
email_verification_token VARCHAR(64)
email_verification_expires DATETIME
```

### Tabela `email_templates`
```sql
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) UNIQUE,
    template_name VARCHAR(255),
    subject VARCHAR(255),
    body_html LONGTEXT,
    body_text LONGTEXT,
    variables TEXT,
    is_system TINYINT(1),
    is_active TINYINT(1),
    ...
)
```

### Schema
- Tudo está incluído em [sql/schema.sql](../sql/schema.sql)
- Não há scripts de migração separados
- Templates pré-definidos são criados automaticamente via `INSERT IGNORE`

## Configuração

### Email SMTP
Configure as definições SMTP em **Admin → Definições → Email**:
- Host SMTP
- Porta (587 para TLS, 465 para SSL)
- Username
- Password
- Encriptação (TLS/SSL)

### Teste de Email
1. Registe uma conta de teste com email real
2. Verifique se recebe o email (verificar spam/lixo)
3. Clique no link de verificação
4. Confirme que consegue fazer login

## Casos Especiais

### Token Expirado
- Prazo: 24 horas após registo
- Se expirado: utilizador tem de se registar novamente
- Mensagem de erro explica a situação

### Utilizadores Existentes
- Automaticamente marcados como verificados na migração
- Não são afetados pelo novo sistema

### Contas Staff
- Criadas por administradores em [admin/manage_users.php](../admin/manage_users.php)
- Não requerem verificação de email
- Login imediato após criação

### Utilizadores de Teste
- Script [scripts/sample_users.php](../scripts/sample_users.php) marca contas como verificadas
- Permite login imediato para testes durante desenvolvimento

## Segurança

### Token
- 32 bytes aleatórios via `random_bytes()`
- Formato hexadecimal (64 caracteres)
- Único por utilizador
- Válido apenas uma vez (removido após verificação)

### Logs
- Todas as verificações registadas em `system_logs`
- Tentativas de login bloqueadas também registadas
- Facilita auditoria e troubleshooting

### Rate Limiting
- Recomendado: implementar limite de emails por IP (futuro)
- Previne abuso do sistema de registo

## Modo de Manutenção

Quando o **Modo de Manutenção** está ativo com `disable_login = 1`:
- Registo automaticamente desativado
- Popup de manutenção mostrado em [register.php](../register.php)
- Configuração em **Admin → Definições → Manutenção**

## Ficheiros do Sistema

| Ficheiro | Alteração |
|----------|-----------|
| [sql/schema.sql](../sql/schema.sql) | Tabelas `users` e `email_templates` com dados iniciais |
| [inc/email_templates.php](../inc/email_templates.php) | Funções helper para templates |
| [register.php](../register.php) | Usa `sendTemplatedEmail()` |
| [registration_success.php](../registration_success.php) | Página de confirmação de registo |
| [verify_email.php](../verify_email.php) | Validação de token |
| [login.php](../login.php) | Verifica `email_verified` para clientes |
| [admin/email-templates.php](../admin/email-templates.php) | Interface de gestão de templates |
| [admin/settings.php](../admin/settings.php) | Configurações SMTP |
| [scripts/sample_users.php](../scripts/sample_users.php) | Marca contas de teste como verificadas |

## Notas Importantes

1. **Schema Único**: Todo o schema está em `sql/schema.sql` - não há migrações separadas
2. **SMTP Obrigatório**: Sistema não funcionará sem SMTP configurado
3. **Templates de Sistema**: Não podem ser eliminados, apenas editados
4. **Templates Personalizados**: Podem ser criados, editados e eliminados livremente
5. **Testes**: Use a funcionalidade "Testar" para enviar emails de teste
6. **Backup**: Faça backup antes de alterações importantes em templates

## Troubleshooting

### Email não chega
- Verificar configuração SMTP em Admin → Definições → Email
- Testar credenciais SMTP
- Verificar pasta de spam
- Consultar logs do servidor SMTP

### Token inválido
- Verificar se expirou (24h)
- Confirmar que link não foi cortado pelo cliente de email
- Tentar copiar/colar link completo no browser

### Login bloqueado
- Confirmar que email foi verificado
- Verificar coluna `email_verified` na base de dados
- Para staff, verificar se role está correta
- Consultar `system_logs` para detalhes

---

**Última atualização:** 2025-12-25
