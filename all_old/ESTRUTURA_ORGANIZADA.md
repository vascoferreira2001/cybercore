# 🗂️ Estrutura de Ficheiros CyberCore

**Data da Reorganização:** 27/12/2024

## 📁 Estrutura Atual

```
cybercore/
├── 🌐 WEBSITE PÚBLICO (raiz - cybercore.pt)
│   ├── index.php              # Homepage
│   ├── about.php              # Sobre nós
│   ├── services.php           # Serviços (8 tipos)
│   ├── pricing.php            # Preços
│   ├── contact.php            # Contacto
│   ├── contact_submit.php     # Handler do formulário
│   ├── privacy.php            # Política de privacidade
│   ├── terms.php              # Termos e condições
│   └── 404.php                # Página de erro
│
├── 👤 ÁREA DE CLIENTE (/manager/ - cybercore.pt/manager/)
│   ├── index.php              # Dashboard principal
│   ├── login.php              # Login
│   ├── register.php           # Registo
│   ├── logout.php             # Logout
│   ├── forgot_password.php    # Recuperar password
│   ├── reset_password.php     # Reset password
│   ├── verify_email.php       # Verificação de email
│   ├── profile.php            # Perfil do utilizador
│   ├── hosting.php            # Gestão de hosting
│   ├── domains.php            # Gestão de domínios
│   ├── domains_edit.php       # Editar domínio
│   ├── servers.php            # Gestão de VPS/servidores
│   ├── services.php           # Listagem de serviços
│   ├── finance.php            # Faturas e pagamentos
│   ├── support.php            # Tickets de suporte
│   ├── logs.php               # Logs de atividade
│   └── updates.php            # Atualizações
│
├── 🔐 PAINEL ADMIN (/admin/ - cybercore.pt/admin/)
│   ├── dashboard.php          # Dashboard admin
│   ├── customers.php          # Gestão de clientes
│   ├── services.php           # Gestão de serviços
│   ├── servers.php            # Gestão de servidores
│   ├── domains.php            # Gestão de domínios
│   ├── hosting.php            # Gestão de hosting
│   ├── payments.php           # Pagamentos
│   ├── invoices.php           # Faturas
│   ├── tickets.php            # Tickets de suporte
│   ├── reports.php            # Relatórios
│   ├── settings.php           # Definições do sistema
│   └── ...                    # 30+ ficheiros admin
│
├── 🎨 ASSETS (/assets/)
│   ├── css/
│   │   ├── main.css           # Estilos principais
│   │   ├── components.css     # UI Components
│   │   ├── dashboard.css      # Dashboard styles
│   │   └── pages/
│   │       ├── services.css
│   │       ├── pricing.css
│   │       ├── about.css
│   │       └── contact.css
│   ├── js/
│   │   ├── components.js      # UI Library (Toast, Modal, etc)
│   │   └── main.js
│   ├── img/                   # Imagens
│   └── uploads/               # Uploads de utilizadores
│
├── 🔧 BACKEND (/inc/)
│   ├── config.php             # Configuração geral
│   ├── db.php                 # Conexão DB
│   ├── db_credentials.php     # Credenciais DB
│   ├── auth.php               # Autenticação
│   ├── permissions.php        # RBAC
│   ├── csrf.php               # CSRF protection
│   ├── mailer.php             # Email sender
│   ├── header.php             # Header global
│   ├── footer.php             # Footer global
│   ├── sidebar.php            # Sidebar dashboard
│   └── api/                   # API endpoints
│
├── 🏗️ ARCHITECTURE (/app/)
│   ├── Router.php             # Routing system
│   ├── Controllers/
│   │   └── Controller.php     # Base controller
│   └── Middleware/
│       ├── Authenticate.php
│       ├── CheckRole.php
│       └── VerifyCSRF.php
│
├── 🗺️ ROUTES (/routes/)
│   ├── web.php                # Rotas públicas
│   ├── client.php             # Rotas da área de cliente
│   └── admin.php              # Rotas do painel admin
│
├── 💾 DATABASE (/sql/)
│   ├── schema.sql             # Schema completo
│   └── migrations/            # Migrações
│
├── 📚 DOCS (/docs/)
│   ├── ARCHITECTURE_ANALYSIS.md
│   ├── IMPLEMENTATION_SUMMARY.md
│   ├── PERMISSIONS_GUIDE.md
│   └── ...
│
└── 🗃️ BACKUPS
    ├── _old_root/             # Ficheiros antigos da raiz
    ├── _old_website/          # Backup da pasta /website/
    └── _old_manager/          # Backup antigo /manager/
```

## 🔀 Redirects Configurados

### Website Público
- `/inicio` → `/index.php`
- `/servicos` → `/services.php`
- `/precos` → `/pricing.php`
- `/sobre` → `/about.php`
- `/contacto` → `/contact.php`

### Área de Cliente
- `/dashboard` → `/manager/`
- `/client-dashboard.php` → `/manager/`
- `/minha-conta` → `/manager/`

### Autenticação
- `/entrar` → `/manager/login.php`
- `/registar` → `/manager/register.php`
- `/recuperar-password` → `/manager/forgot_password.php`

## 🛡️ Segurança

### Ficheiros Protegidos
- `.env`
- `.htaccess`
- `.git/`
- `composer.json`
- `*.md` (documentação)
- `/sql/`
- `/inc/db_credentials.php`

### Headers de Segurança
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin

## 📦 Assets Estáticos

### Cache
- Imagens: 1 ano
- CSS/JS: 1 mês
- PDFs: 1 mês

### Compressão
- GZIP ativado para HTML, CSS, JS, JSON

## 🔗 URLs do Site

### Produção
- **Website:** https://cybercore.pt
- **Área de Cliente:** https://cybercore.pt/manager/
- **Painel Admin:** https://cybercore.pt/admin/

### Desenvolvimento
- **Website:** http://localhost:8080
- **Área de Cliente:** http://localhost:8080/manager/
- **Painel Admin:** http://localhost:8080/admin/

## 📝 Notas de Migração

### Movimentos Realizados
1. ✅ Ficheiros do website de `/website/` para raiz
2. ✅ Paths corrigidos (removido `../` dos includes)
3. ✅ Ficheiros de gestão movidos para `/manager/`
4. ✅ Dashboard principal criado em `/manager/index.php`
5. ✅ .htaccess configurado com redirects e segurança
6. ✅ Backups criados em `_old_*` folders

### Ficheiros que Permaneceram na Raiz
- `index.php` - Homepage
- `login.php` - Pode redirecionar para /manager/login.php
- `register.php` - Pode redirecionar para /manager/register.php
- `logout.php` - Handler de logout global

### Próximos Passos
1. Testar todos os links e redirects
2. Atualizar links do menu no header.php
3. Atualizar footer.php com novos links
4. Criar páginas em falta no /manager/
5. Implementar API endpoints em /inc/api/

---

**Estrutura organizada por:** GitHub Copilot  
**Data:** 27/12/2024
