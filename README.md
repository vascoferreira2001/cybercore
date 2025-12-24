# CyberCore - Área de Cliente

## Visão Geral

CyberCore é uma plataforma de área de cliente para gestão de domínios, alojamento e suporte. Destina-se a correr **apenas em produção**, ligada a uma base de dados já existente no servidor.

## Configuração (Produção)

### Variáveis de Ambiente

Configure as seguintes variáveis de ambiente no servidor:

```bash
# Base de Dados
export DB_HOST=127.0.0.1
export DB_NAME=cybercore
export DB_USER=cybercore
export DB_PASS='sua_password'

# Site
export SITE_URL='https://seu-dominio'
export SITE_NAME='CyberCore - Área de Cliente'

# Email (opcional)
export SMTP_HOST='smtp.seu-dominio.com'
export SMTP_PORT=587
export SMTP_USER='seu-email@seu-dominio.com'
export SMTP_PASS='password'
export SMTP_SECURE='tls'
export MAIL_FROM='noreply@seu-dominio.com'
export MAIL_FROM_NAME='CyberCore'
```

### Alternativa: Ficheiro Local (não versionado)

Se preferir usar um ficheiro em vez de variáveis de ambiente, coloque `inc/db_credentials.php` no servidor (não será commitado ao repositório):

```php
<?php
define('DB_HOST', 'seu-host');
define('DB_NAME', 'sua-base');
define('DB_USER', 'seu-utilizador');
define('DB_PASS', 'sua-password');
?>
```

## Rotas Principais

| Rota | Descrição |
|------|-----------|
| `login.php` | Autenticação |
| `register.php` | Registo de novos utilizadores |
| `dashboard.php` | Painel inicial (após autenticação) |
| `support.php` | Gestão de tickets de suporte |
| `domains.php` | Gestão de domínios |
| `finance.php` | Aviso de pagamentos / Financeiro |
| `logs.php` | Histórico de atividades |
| `manage_users.php` | Descontinuado (redirige para Configurações > Equipa) |

## Permissões por Role

### Cliente
- Acesso à sua própria área
- Domínios próprios (criar, editar, eliminar)
- Suporte (criar tickets, ver seus tickets)
- Financeiro (ver faturas próprias)
- Logs (ver seus logs)

### Suporte ao Cliente
- Ver/editar todos os domínios (sem eliminar)
- Ver tickets de suporte
- Ver logs

### Suporte Técnica
- Ver/editar todos os domínios (sem eliminar)
- Ver tickets de suporte
- Ver logs

### Suporte Financeira
- Ver/editar todas as faturas
- Ver logs financeiros
- Sem acesso a domínios

### Gestor
- Acesso total a todas as funcionalidades
- Gestão de utilizadores descontinuada — usar Configurações > Equipa e Funções
- Não pode remover seu próprio role Gestor

## Segurança

### Implementações
- **Sessões endurecidas**: cookies com `HttpOnly`, `SameSite=Strict` e `Secure` (em HTTPS)
- **CSRF**: tokens gerados por sessão; todos os formulários críticos usam POST + validação
- **Rate Limiting**: limite básico de 5 tentativas de login em 10 minutos
- **Prepared Statements**: toda a interação com BD usa prepared statements
- **Password Hashing**: `password_hash()` com algoritmo bcrypt
- **Session Regeneration**: ID de sessão regenerado após login bem-sucedido

### Checklist de Implantação
- [ ] HTTPS ativado no servidor
- [ ] Variáveis de ambiente ou ficheiro `inc/db_credentials.php` configurado
- [ ] Base de dados com tabelas: `users`, `domains`, `tickets`, `invoices`, `logs`, `password_resets`
- [ ] SMTP configurado (se usar envio de emails)
- [ ] Logs do servidor (`error_log`) monitorados

## Resilência

- **Dashboard**: se alguma tabela não existir, o painel apresenta 0 e regista o erro sem cair (HTTP 500)
- **Migrações**: ficheiro `migrate.php` é idempotente (não falha em re-execuções); **não é necessário em produção**

## Desenvolvimento

### Instalação / Migração

⚠️ **IMPORTANTE**: Use APENAS `sql/schema.sql`. Não importe ficheiros de `sql/legacy/`.

Instalação de raiz:

```bash
# 1. Remover base existente (se necessário)
mysql -u USER -p -e "DROP DATABASE IF EXISTS cybercore;"

# 2. Importar schema completo
mysql -u USER -p < sql/schema.sql
```

Ver instruções detalhadas: [INSTALL.md](INSTALL.md)

Migração local (dev, opcional):

```bash
php migrate.php
```

`schema.sql` inclui todas as tabelas e seeds (settings, manutenção, permissões, serviços, changelog).

### Criar Utilizadores de Teste

```bash
php sample_users.php
```

Cria utilizadores de teste com password `Password123!`:
- `cliente@example.test` (Cliente)
- `suporte_cliente@example.test` (Suporte ao Cliente)
- `suporte_finance@example.test` (Suporte Financeira)
- `suporte_tecnica@example.test` (Suporte Técnica)
- `gestor@example.test` (Gestor)

## Estrutura de Ficheiros

```
.
├── inc/
│   ├── auth.php          # Autenticação e sessões
│   ├── config.php        # Configuração (env/ficheiro)
│   ├── csrf.php          # Tokens CSRF
│   ├── db.php            # Conexão PDO
│   ├── header.php        # Cabeçalho/navegação
│   ├── footer.php        # Rodapé
│   └── mailer.php        # Envio de emails
├── sql/
│   ├── schema.sql        # ESQUEMA CONSOLIDADO (usar este)
│   └── legacy/           # Scripts antigos (referência, não usar)
├── js/
│   └── app.js            # Validação cliente simples
├── css/
│   └── style.css         # Estilos básicos
├── login.php
├── register.php
├── dashboard.php
├── support.php
├── domains.php
├── domains_edit.php
├── finance.php
├── logs.php
├── manage_users.php (descontinuado)
├── forgot_password.php
├── reset_password.php
├── logout.php
├── migrate.php           # Migração (dev only)
├── sample_users.php      # Criar users de teste (dev only)
└── README.md
```

## Email

Por padrão, usa `mail()` do PHP. Para produção, configure SMTP via variáveis de ambiente (veja secção Configuração acima).

Quando `SMTP_HOST` está vazio, usa `mail()`. Caso contrário, aguarda implementação de PHPMailer com SMTP.

## Logs

- Atividades são registadas na tabela `logs` (user_id, type, message, created_at)
- Erros de sistema em `error_log` do PHP

## Suporte

Para questões de segurança, confira `inc/auth.php`, `inc/csrf.php` e as permissões em cada página.

Para relatórios de bugs ou melhorias, contacte o administrador.
