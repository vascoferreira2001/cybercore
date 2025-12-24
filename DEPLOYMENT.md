# Instalação CyberCore em Produção

## Informações da Base de Dados

```
Host: localhost
Database: cybercore
User: cybercore
Password: RPd3knB&ofbh8g9_
URL: https://cybercore.cyberworld.pt
```

## 1. Criar Base de Dados e Utilizador (se não existirem)

Executar no MySQL como `root`:

```sql
CREATE DATABASE IF NOT EXISTS cybercore CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS 'cybercore'@'localhost' IDENTIFIED BY 'RPd3knB&ofbh8g9_';
GRANT ALL PRIVILEGES ON cybercore.* TO 'cybercore'@'localhost';
FLUSH PRIVILEGES;
```

## 2. Importar Schema

```bash
mysql -u cybercore -p'RPd3knB&ofbh8g9_' cybercore < sql/schema.sql
```

Ou sem password direta (mais seguro):

```bash
mysql -u cybercore -p cybercore < sql/schema.sql
# Depois digita a password quando pedir
```

## 3. Ficheiro de Configuração

✅ Já configurado em: `inc/db_credentials.php`

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cybercore');
define('DB_USER', 'cybercore');
define('DB_PASS', 'RPd3knB&ofbh8g9_');
define('SITE_URL', 'https://cybercore.cyberworld.pt');
define('SITE_NAME', 'CyberCore - Área de Cliente');
?>
```

## 4. Criar Utilizadores de Teste (Opcional)

Se tiver PHP instalado no servidor:

```bash
php scripts/sample_users.php
```

Utilizadores criados:
- `gestor@example.test` (Gestor)
- `cliente@example.test` (Cliente)
- `suporte_cliente@example.test` (Suporte ao Cliente)
- `suporte_finance@example.test` (Suporte Financeira)
- `suporte_tecnica@example.test` (Suporte Técnica)

Password: `Password123!`

## 5. Verificar Instalação

Aceder a: `https://cybercore.cyberworld.pt/login.php`

Se conseguir carregar a página, tudo está OK.

## 6. Configuração Web Server (Apache/Nginx)

Certifique-se que:
- ✅ HTTPS está ativado (certificado SSL válido)
- ✅ `inc/db_credentials.php` não é acessível via web
- ✅ `assets/uploads/` tem permissões de escrita

### Apache (.htaccess)

Criar `.htaccess` na raiz:

```apache
<FilesMatch "\.php$">
    Deny from all
</FilesMatch>

<FilesMatch "^index\.php$|^login\.php$|^register\.php$|^dashboard\.php$|^.*\.php$">
    Allow from all
</FilesMatch>

# Proteger ficheiros sensíveis
<FilesMatch "^(\.env|db_credentials\.php|\.git|\.gitignore)$">
    Deny from all
</FilesMatch>

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
```

### Nginx

Adicionar ao bloco `server`:

```nginx
# Proteger ficheiros sensíveis
location ~ /\. {
    deny all;
}

location ~ (\.env|db_credentials\.php|\.git|sql/) {
    deny all;
}

# Permitir assets
location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## 7. Segurança

- ✅ `inc/db_credentials.php` está em `.gitignore` (não é versionado)
- ✅ Password não é exposta ao frontend (PHP server-side)
- ✅ CSRF tokens ativados
- ✅ Sessions endurecidas (HttpOnly, SameSite)
- ✅ Prepared statements em todas as queries

## Documentação

- [README.md](../README.md) — Overview geral
- [docs/INSTALL.md](../docs/INSTALL.md) — Instruções detalhadas
- [docs/PERMISSIONS_GUIDE.md](../docs/PERMISSIONS_GUIDE.md) — Permissões por role
