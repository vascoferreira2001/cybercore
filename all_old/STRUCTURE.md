# Estrutura do Projeto CyberCore

## 📁 Organização de Pastas

```
cybercore/
├── /                           # Raiz - páginas públicas
│   ├── login.php              # Login
│   ├── logout.php             # Logout
│   ├── register-step1.php      # Registro - Passo 1
│   ├── register-step2.php      # Registro - Passo 2
│   ├── registration_success.php # Sucesso de Registro
│   ├── forgot_password.php      # Recuperação de Password
│   ├── reset_password.php       # Reset de Password
│   ├── verify_email.php         # Verificação de Email
│   │
│   ├── profile.php              # Perfil do Utilizador
│   ├── dashboard.php            # Dashboard (redireciona por role)
│   │
│   ├── finance.php              # Faturação
│   ├── services.php             # Serviços
│   ├── domains.php              # Domínios
│   ├── support.php              # Suporte
│   ├── logs.php                 # Logs
│   ├── updates.php              # Atualizações
│   ├── no_access.php            # Acesso Negado
│
├── /admin/                      # Painel Administrativo
│   ├── dashboard.php            # Redireciona para admin-dashboard.php
│   ├── admin-dashboard.php      # Dashboard único para todos admin
│   ├── fiscal-approvals.php     # Aprovações de Dados Fiscais
│   ├── customers.php            # Gestão de Clientes
│   ├── manage_users.php         # Gestão de Utilizadores
│   ├── payments.php             # Pagamentos
│   ├── services.php             # Gestão de Serviços
│   ├── settings.php             # Configurações
│   ├── reports.php              # Relatórios
│   ├── team.php                 # Equipa
│   ├── tickets.php              # Tickets
│   ├── expenses.php             # Despesas
│   ├── updates.php              # Atualizações
│   └── payment-warnings.php     # Avisos de Pagamento
│
├── /dashboard/                  # Dashboards específicos
│   └── client-dashboard.php     # Dashboard para clientes
│
├── /inc/                        # Includes & Functions
│   ├── auth.php                # Autenticação & Autorização
│   ├── db.php                  # Conexão Database
│   ├── config.php              # Configuração
│   ├── csrf.php                # CSRF Protection
│   ├── dashboard_helper.php    # Layout Helper
│   ├── menu_config.php         # Menu Configuration
│   ├── permissions.php         # Permissões
│   ├── settings.php            # Configurações da App
│   │
│   ├── /api/                   # API Endpoints
│   │   └── fiscal-requests.php # API - Fiscal Change Requests
│   │
│   ├── /helpers/               # Helper Functions
│   │   ├── fiscal_requests.php    # Fiscal workflow logic
│   │   ├── fiscal_update.php      # Fiscal backend operations
│   │   ├── mailer.php             # Email sending
│   │   ├── email_templates.php    # Email templates
│   │   ├── maintenance.php        # Maintenance mode
│   │   └── debug.php              # Debug utilities
│
├── /sql/                        # Database Schema
│   ├── schema.sql              # Main schema
│   └── /legacy/                # Legacy migrations
│       ├── password_resets.sql
│       └── ...
│
├── /scripts/                    # Utility Scripts
│   ├── migrate.php             # Database migration
│   ├── sample_users.php        # Sample data fixtures
│   └── setup_identifier.php    # Setup identifiers
│
├── /assets/                     # Static Assets
│   ├── /css/                   # Stylesheets
│   │   ├── style.css
│   │   ├── design-system.css
│   │   └── auth-modern.css
│   ├── /js/                    # JavaScript
│   │   ├── app.js
│   │   └── /pages/
│   │       ├── dashboard-modern.js
│   │       ├── profile.js
│   │       └── ...
│   └── /uploads/               # User uploads
│
├── /docs/                       # Documentation
│   ├── INSTALL.md              # Installation
│   ├── EMAIL_TEMPLATES.md      # Email templates guide
│   ├── EMAIL_VERIFICATION.md   # Email verification flow
│   ├── PERMISSIONS_GUIDE.md    # Permissions reference
│   ├── ROLE_BASED_ACCESS.md    # RBAC documentation
│   └── FISCAL_DATA_MANAGEMENT.md # Fiscal data workflow
│
├── README.md                   # Project overview
├── SETUP.md                    # Setup instructions
└── composer.json               # PHP dependencies
```

## 🔑 Ficheiros Core

### Autenticação & Segurança
- `inc/auth.php` - Login, roles, permissions
- `inc/csrf.php` - CSRF token handling
- `inc/dashboard_helper.php` - Dashboard layout
- `inc/permissions.php` - Permission system

### Banco de Dados
- `inc/db.php` - Database connection
- `inc/config.php` - Configuration
- `sql/schema.sql` - Full schema

### Funcionalidades Principais
- `inc/helpers/fiscal_requests.php` - Fiscal change workflow
- `inc/helpers/fiscal_update.php` - Fiscal backend
- `inc/helpers/mailer.php` - Email sending
- `inc/helpers/email_templates.php` - Email templates
- `inc/api/fiscal-requests.php` - Fiscal API

## 📋 Roles & Access

### Cliente
- Acesso: Dashboard pessoal, Perfil, Serviços, Faturação, Domínios, Suporte
- Dashboard: `/dashboard/client-dashboard.php`
- Vê apenas seus dados

### Administração (Gestor, Suporte ao Cliente, Suporte Financeiro, Suporte Técnico)
- Acesso: Admin dashboard, aprovações, gestão de recursos
- Dashboard: `/admin/admin-dashboard.php`
- Vê dados globais (todos os clientes)
- Menu filtrado por role

## 🔄 Fluxos Principais

### Login
1. `login.php` → Autenticação
2. `inc/auth.php::redirectToDashboard()` → Router por role
3. **Cliente** → `/dashboard/client-dashboard.php`
4. **Admin** → `/admin/admin-dashboard.php`

### Fiscal (Alteração de Dados)
1. Cliente acessa `profile.php` → Fiscal tab
2. Submete alteração via `inc/api/fiscal-requests.php`
3. Cria registo em `fiscal_change_requests`
4. Admin acessa `admin/fiscal-approvals.php`
5. Aprova/Rejeita via `inc/helpers/fiscal_requests.php`
6. Dados atualizados em `users` table

## 📝 Ficheiros Removidos (Limpeza)

- `db_connection.php` (duplicado)
- `register.php` (duplicado)
- `sidebar.php` (renderDashboardLayout já tem)
- `cron.php` (não configurado)
- `manage_users.php` (raiz, admin tem)
- `domains_edit.php`, `search.php`, `servers.php`, `hosting.php` (sem uso)
- Ficheiros `inc/` obsoletos (header, footer, check_session, profile_data, etc.)
- Placeholders de admin (alerts, contracts, documents, etc.)
- Docs duplicadas (ESTRUTURA, FIX_SUMMARY, FISCAL_QUICK_START)
