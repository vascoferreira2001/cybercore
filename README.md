CyberCore - Área de Cliente (Local XAMPP)

Importante: este é um scaffold para correr em XAMPP (localhost). Siga os passos abaixo para testar localmente.

CyberCore - Área de Cliente (Local XAMPP)

Importante: este é um scaffold para correr em XAMPP (localhost). Siga os passos abaixo para testar localmente.

1. Coloque a pasta `cybercore` em `htdocs` do XAMPP (ex.: /Applications/XAMPP/xamppfiles/htdocs/cybercore).
2. Abra `phpMyAdmin` e crie uma base de dados chamada `cybercore` (ou altere `inc/config.php`).
3. Importe `sql/schema.sql` no phpMyAdmin.
4. Configure `inc/config.php` se necessário (credenciais DB).
5. Inicie Apache e MySQL no XAMPP.
6. Aceda a `http://localhost/cybercore/login.php`.

Notas:
- Esta versão contém páginas básicas: registo, login, dashboard, tickets, logs e placeholders para financeiro/domínios.
- Para envio de emails, configure o servidor SMTP do PHP (php.ini) ou utilize uma biblioteca externa.

Instalação de dependências (recomendada):

1. Instale o Composer (se ainda não tiver): https://getcomposer.org/
2. Na pasta do projeto execute:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/cybercore
composer install
```

Importar base de dados:

```bash
# Criar base e importar esquema
mysql -u root -p < sql/schema.sql
mysql -u root -p cybercore < sql/password_resets.sql
```

Configuração SMTP para envio de emails (opcional):

1. Edite `inc/config.php` e preencha `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` e `SMTP_SECURE`.
2. Ajuste `MAIL_FROM` e `MAIL_FROM_NAME` conforme necessário.
3. Se preferir usar o `mail()` do PHP, deixe `SMTP_HOST` vazio e configure o `SMTP`/`sendmail` no `php.ini` do XAMPP.

Testes rápidos:

1. Registe um utilizador em `http://localhost/cybercore/register.php`.
2. Peça redefinição de senha em `http://localhost/cybercore/forgot_password.php`.
3. Verifique a tabela `logs` e `password_resets` no phpMyAdmin para confirmar o fluxo.

Gestão de roles (painel Gestor):

1. Crie os utilizadores de teste (ou registe manualmente). Pode usar `sample_users.php`.
2. Entre com um utilizador Gestor (ex.: gestor@example.test / Password123!).
3. Aceda a `http://localhost/cybercore/manage_users.php` para listar utilizadores e alterar roles.
4. Não altere o seu próprio role para evitar perder privilégios.

Matriz de permissões resumida:
- Cliente: acesso normal à sua área (domínios próprios, suporte, financeiro limitado, logs próprios).
- Suporte: acesso a tickets, ver/editar domínios; sem gestão financeira completa.
- Contabilista: acesso a financeiro e logs (visualização), sem criação/edição de domínios.
- Gestor: acesso total e painel de gestão de utilizadores.

Comandos SQL importantes:

- Se já tiver a tabela `users` com a coluna `role`, actualize o enum com o comando abaixo para incluir os novos cargos:

```sql
ALTER TABLE users MODIFY COLUMN role ENUM('Cliente','Suporte ao Cliente','Suporte Financeira','Suporte Técnica','Gestor') NOT NULL DEFAULT 'Cliente';
```

CSRF (proteção contra falsificação de formulários):

- Este scaffold agora inclui um pequeno helper em `inc/csrf.php` que gera um token por sessão e valida-o em pedidos POST.
- Todos os formulários críticos (`register.php`, `login.php`, `forgot_password.php`, `reset_password.php`, `support.php`, `domains.php`, `domains_edit.php`, `manage_users.php`) já têm um campo oculto com o token e validação no servidor.
- Se receber `Invalid CSRF token`, verifique se as sessões estão a funcionar correctamente no XAMPP (php.ini) e que está a usar o mesmo host/porta (cookies de sessão dependem do domínio).

Teste rápido após alterações:

1. Execute as alterações SQL (se necessário):

```bash
mysql -u root -p < /Applications/XAMPP/xamppfiles/htdocs/cybercore/sql/schema.sql
mysql -u root -p cybercore < /Applications/XAMPP/xamppfiles/htdocs/cybercore/sql/roles_and_domains.sql
mysql -u root -p cybercore < /Applications/XAMPP/xamppfiles/htdocs/cybercore/sql/password_resets.sql
```

2. Instale dependências:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/cybercore
composer install
```

3. Criar utilizadores de teste:

```bash
php /Applications/XAMPP/xamppfiles/htdocs/cybercore/sample_users.php
```



