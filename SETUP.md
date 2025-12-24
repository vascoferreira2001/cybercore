# 🚀 CyberCore - Guia Completo de Setup & Deployment

**Última atualização:** 24 de dezembro de 2025

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Configuração Rápida](#configuração-rápida)
3. [Instalação Completa do Servidor](#instalação-completa-do-servidor)
4. [Troubleshooting](#troubleshooting)
5. [Segurança](#segurança)
6. [Referência de Rotas](#referência-de-rotas)
7. [Permissões por Role](#permissões-por-role)

---

## 🎯 Visão Geral

CyberCore é uma **plataforma de área de cliente** para gestão de domínios, alojamento e suporte.

- **Ambiente:** Produção apenas (com BD existente no servidor)
- **Linguagem:** PHP 7.4+
- **Base de Dados:** MySQL 5.7+
- **Web Server:** Apache ou Nginx com HTTPS
- **Segurança:** CSRF tokens, Session hardening, Prepared statements, bcrypt

---

## ⚡ Configuração Rápida

### Opção 1: Ficheiro de Credenciais (Recomendado para Produção)

```bash
# 1. Criar ficheiro de credenciais
cat > inc/db_credentials.php << 'EOF'
<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cybercore');
define('DB_USER', 'cybercore');
define('DB_PASS', 'sua_password_aqui');
define('SITE_URL', 'https://seu-dominio.com');
define('SITE_NAME', 'CyberCore - Área de Cliente');
?>
EOF

# 2. Proteger o ficheiro
chmod 600 inc/db_credentials.php

# 3. Verificar
cat inc/db_credentials.php
```

**⚠️ IMPORTANTE:** `inc/db_credentials.php` **NÃO deve estar no Git**. Está no `.gitignore`.

### Opção 1.1: Bootstrap automático (recomendado)

Se preferir automatizar a criação do ficheiro de credenciais (a partir de variáveis de ambiente ou por perguntas interativas), use o script:

```bash
php scripts/bootstrap_credentials.php
```

O script irá:
- Ler `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `SITE_URL`, `SITE_NAME` do ambiente (se existirem)
- Perguntar no terminal (modo interativo) caso faltem valores
- Gerar `inc/db_credentials.php` e definir permissões `600`

### Opção 1.2: Via Composer (auto)

Se usar Composer, o projeto já executa o bootstrap automaticamente após `install` ou `update`:

```bash
composer install
```

Isto cria `inc/db_credentials.php` se ainda não existir. Para atualizar credenciais, pode correr:

```bash
composer update
```

### Opção 2: Variáveis de Ambiente

Se preferir não usar ficheiro local:

```bash
export DB_HOST=127.0.0.1
export DB_NAME=cybercore
export DB_USER=cybercore
export DB_PASS='sua_password_aqui'
export SITE_URL='https://seu-dominio.com'
export SITE_NAME='CyberCore - Área de Cliente'
export SMTP_HOST='smtp.seu-dominio.com'
export SMTP_PORT=587
export SMTP_USER='seu-email@seu-dominio.com'
export SMTP_PASS='password'
export SMTP_SECURE='tls'
export MAIL_FROM='noreply@seu-dominio.com'
export MAIL_FROM_NAME='CyberCore'
```

---

## 🔧 Instalação Completa do Servidor

### Passo 1: Criar Base de Dados e Utilizador MySQL

```bash
# Conectar como root
mysql -u root -p

# No MySQL, executar:
CREATE DATABASE IF NOT EXISTS cybercore CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS 'cybercore'@'localhost' IDENTIFIED BY 'RPd3knB&ofbh8g9_';
GRANT ALL PRIVILEGES ON cybercore.* TO 'cybercore'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Passo 2: Importar Schema

```bash
mysql -u cybercore -p'RPd3knB&ofbh8g9_' cybercore < sql/schema.sql
```

Ou (mais seguro - pede password):
```bash
mysql -u cybercore -p cybercore < sql/schema.sql
```

**✅ Tabelas criadas:**
```
users, tickets, domains, logs, invoices, settings, departments,
department_permissions, client_permissions, password_resets,
web_hosting, email_hosting, dedicated_servers, vps_servers,
website_maintenance, website_development, social_media_management, changelog
```

### Passo 3: Configurar Ficheiro de Credenciais

```bash
cp inc/db_credentials.example.php inc/db_credentials.php
nano inc/db_credentials.php
# Editar com as suas credenciais reais
chmod 600 inc/db_credentials.php
```

### Passo 4: Criar Utilizadores de Teste (Opcional)

```bash
php scripts/sample_users.php
```

**Utilizadores criados:**
| Email | Role | Password |
|-------|------|----------|
| gestor@example.test | Gestor | Password123! |
| cliente@example.test | Cliente | Password123! |
| suporte_cliente@example.test | Suporte ao Cliente | Password123! |
| suporte_finance@example.test | Suporte Financeira | Password123! |
| suporte_tecnica@example.test | Suporte Técnica | Password123! |

### Passo 5: Testar Ligação à Base de Dados

Visite: `https://seu-dominio.com/test_db.php`

**Esperado:** ✅ "All tests passed!"

Se vir erro `using password: NO`:
→ Veja [Troubleshooting](#troubleshooting) abaixo

### Passo 6: Configuração Web Server

#### Apache (com `.htaccess`)

```apache
# Proteger ficheiros sensíveis
<FilesMatch "\.php$">
    Deny from all
</FilesMatch>

<FilesMatch "^(index|login|register|dashboard|logout|forgot_password|reset_password|cron)\.php$">
    Allow from all
</FilesMatch>

<Directory "/caminho/para/admin">
    Allow from all
</Directory>

# Bloquear acesso a ficheiros sensíveis
<FilesMatch "^(\.env|db_credentials\.php|\.git|\.gitignore|composer.json)$">
    Deny from all
</FilesMatch>

# Rewrite rules (se necessário)
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
```

#### Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name cybercore.cyberworld.pt;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    root /var/www/cybercore;
    index index.php login.php;

    # Proteger ficheiros sensíveis
    location ~ ^/(\.env|db_credentials\.php|\.git) {
        deny all;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Permitir assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
    }
}
```

---

## 🐛 Troubleshooting

### Erro: "Database connection failed: SQLSTATE[HY000] [1045] Access denied for user 'cybercore'@'localhost' (using password: NO)"

**Causa:** `inc/db_credentials.php` não existe no servidor

**Solução:**
```bash
# Via SSH
ssh user@seu-servidor.com
cd /var/www/cybercore

# Criar ficheiro
cat > inc/db_credentials.php << 'EOF'
<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cybercore');
define('DB_USER', 'cybercore');
define('DB_PASS', 'RPd3knB&ofbh8g9_');
define('SITE_URL', 'https://cybercore.cyberworld.pt');
define('SITE_NAME', 'CyberCore - Área de Cliente');
?>
EOF

chmod 600 inc/db_credentials.php
```

Ou via **FTP/SFTP:**
1. Copie `inc/db_credentials.example.php` → `inc/db_credentials.php`
2. Edite com as suas credenciais
3. Upload para o servidor

---

### Erro: "Table already exists"

**Causa:** Schema já foi importado ou tabelas já existem

**Solução:**
```bash
# Remover BD e reimportar limpo
mysql -u cybercore -p cybercore -e "DROP DATABASE cybercore;"
mysql -u root -p -e "CREATE DATABASE cybercore CHARACTER SET utf8mb4;"
mysql -u cybercore -p cybercore < sql/schema.sql
```

---

### Erro: "Foreign key constraint failed"

**Causa:** Importou ficheiros de `sql/legacy/` fora de ordem

**Solução:**
```bash
# ❌ NÃO FAZER ISTO
mysql cybercore < sql/legacy/roles_and_domains.sql
mysql cybercore < sql/legacy/services.sql

# ✅ FAZER ISTO
mysql cybercore < sql/schema.sql
```

**Todos os ficheiros legacy estão integrados em `sql/schema.sql`**

---

### Erro: "Duplicate column 'role'"

**Causa:** Importou `sql/legacy/roles_and_domains.sql` após `sql/schema.sql`

**Solução:** DROP DATABASE e usar apenas `schema.sql`

---

### Página branca ou PHP não executa

**Verificações:**

1. **PHP instalado?**
   ```bash
   php -v
   which php
   ```

2. **Extensão PDO ativa?**
   ```bash
   php -m | grep -i pdo
   # Deve mostrar: PDO, pdo_mysql
   ```

3. **Permissões de ficheiros?**
   ```bash
   # Ficheiros devem estar com 644
   find /var/www/cybercore -name "*.php" -exec chmod 644 {} \;
   
   # Directórios com 755
   find /var/www/cybercore -type d -exec chmod 755 {} \;
   
   # assets/uploads com 777
   chmod 777 /var/www/cybercore/assets/uploads
   ```

4. **Verificar error logs:**
   ```bash
   tail -50 /var/log/php-fpm/error.log
   # ou
   tail -50 /var/log/apache2/error.log
   ```

---

### Login não funciona

**Verificações:**

1. **Utilizadores foram criados?**
   ```bash
   php scripts/sample_users.php
   ```

2. **BD tem dados?**
   ```bash
   mysql -u cybercore -p cybercore -e "SELECT COUNT(*) as user_count FROM users;"
   ```

3. **Sessions funcionam?**
   Verifique se `/tmp` tem espaço e permissões:
   ```bash
   df -h /tmp
   ls -la /tmp | head
   ```

---

## 🔒 Segurança

### ✅ Implementações

- **Passwords:** bcrypt com cost=12
- **CSRF:** Token em todos os formulários
- **Sessions:** HttpOnly, SameSite=Strict
- **SQL Injection:** Prepared statements (PDO)
- **XSS:** htmlspecialchars() em outputs
- **Credenciais:** Armazenadas em ficheiro não versionado

### ✅ Checklist de Produção

- [ ] HTTPS ativado (certificado SSL válido)
- [ ] `inc/db_credentials.php` existe no servidor
- [ ] `inc/db_credentials.php` não está no Git (ver `.gitignore`)
- [ ] Ficheiro tem permissões 600: `chmod 600 inc/db_credentials.php`
- [ ] `assets/uploads/` tem permissões de escrita
- [ ] Error reporting desativado em produção
- [ ] Database backups configurados
- [ ] Logs e cache limpezas agendadas

---

## 📍 Referência de Rotas

| Rota | Descrição | Autenticação |
|------|-----------|---------------|
| `login.php` | Autenticação | ❌ Pública |
| `register.php` | Registo de utilizadores | ❌ Pública |
| `forgot_password.php` | Recuperar password | ❌ Pública |
| `reset_password.php` | Reset de password | ❌ Pública |
| `dashboard.php` | Painel inicial | ✅ Autenticado |
| `support.php` | Gestão de tickets | ✅ Autenticado |
| `domains.php` | Gestão de domínios | ✅ Autenticado |
| `domains_edit.php` | Editar domínio | ✅ Autenticado |
| `finance.php` | Financeiro | ✅ Autenticado |
| `logs.php` | Histórico | ✅ Autenticado |
| `hosting.php` | Alojamento | ✅ Autenticado |
| `services.php` | Serviços | ✅ Autenticado |
| `servers.php` | Servidores | ✅ Autenticado |
| `admin/` | Painel de admin | ✅ Admin |
| `logout.php` | Logout | ✅ Autenticado |
| `test_db.php` | Teste de BD | ❌ Pública |
| `cron.php` | Tarefas automáticas | ⚠️ Com token |

---

## 👥 Permissões por Role

### Cliente
- ✅ Acesso à sua própria área
- ✅ Domínios próprios (criar, editar, eliminar)
- ✅ Suporte (criar tickets, ver seus tickets)
- ✅ Financeiro (ver faturas próprias)
- ✅ Logs (ver seus logs)
- ❌ Admin

### Suporte ao Cliente
- ✅ Ver/editar todos os domínios (sem eliminar)
- ✅ Ver tickets de suporte
- ✅ Ver logs
- ❌ Admin
- ❌ Financeiro

### Suporte Técnica
- ✅ Ver/editar todos os domínios (sem eliminar)
- ✅ Ver tickets de suporte
- ✅ Ver logs técnicos
- ❌ Admin
- ❌ Financeiro

### Suporte Financeira
- ✅ Ver/editar todas as faturas
- ✅ Ver logs financeiros
- ❌ Domínios
- ❌ Admin

### Gestor
- ✅ Acesso total a TODAS as funcionalidades
- ✅ Gestão de utilizadores (via Configurações > Equipa)
- ✅ Gestão de permissões
- ✅ Modo de Manutenção
- ✅ Todas as áreas administrativas

---

## 📚 Estrutura do Projeto

```
cybercore/
├── admin/                  # Painel de administração
│   ├── dashboard.php
│   ├── settings.php        # Modo de manutenção, permissões
│   ├── customers.php
│   └── ... (23 ficheiros)
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── app.js
│   └── uploads/            # UGC - precisa 777
├── docs/
│   ├── PERMISSIONS_GUIDE.md
│   └── INSTALL.md
├── inc/
│   ├── auth.php            # Autenticação e sessions
│   ├── config.php          # Carregamento de configurações
│   ├── db.php              # Conexão PDO
│   ├── db_credentials.php  # ❌ Não versionar
│   ├── db_credentials.example.php  # Template
│   ├── csrf.php            # Proteção CSRF
│   ├── settings.php        # Funções getSetting/setSetting
│   ├── mailer.php          # Envio de emails
│   ├── permissions.php     # Controle de acesso
│   ├── header.php          # Header HTML comum
│   └── footer.php          # Footer HTML comum
├── scripts/
│   ├── migrate.php         # Migração de BD
│   └── sample_users.php    # Criar utilizadores de teste
├── sql/
│   ├── schema.sql          # ✅ Schema completo (usar isto)
│   └── legacy/             # ❌ Apenas referência (não importar)
├── login.php
├── register.php
├── dashboard.php
├── ... (outras rotas públicas)
├── test_db.php             # Diagnóstico de BD
├── cron.php                # Tarefas automáticas
├── .gitignore
├── README.md
├── SETUP.md                # ← ESTE FICHEIRO
└── composer.json
```

---

## 🆘 Suporte

Para mais ajuda:

1. **Verifique a saída de `test_db.php`** na sua aplicação
2. **Consulte os logs:** `/var/log/php-fpm/error.log`
3. **Use MySQL diretamente:**
   ```bash
   mysql -u cybercore -p'RPd3knB&ofbh8g9_' cybercore
   SHOW TABLES;
   SELECT COUNT(*) FROM users;
   ```

---

**Última verificação:** 24 de dezembro de 2025
