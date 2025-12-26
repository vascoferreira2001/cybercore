# Estrutura do Projeto CyberCore

## 📁 Estrutura de Diretórios

```
cybercore/
├── 📄 Ficheiros de Raiz
│   ├── composer.json                  # Dependências PHP (PHPMailer, etc.)
│   ├── cron.php                       # Tarefas agendadas
│   ├── dashboard.php                  # Dashboard principal (refatorado)
│   ├── domains.php / domains_edit.php # Gestão de domínios
│   ├── finance.php                    # Gestão financeira
│   ├── hosting.php                    # Gestão de hosting
│   ├── login.php / logout.php         # Autenticação
│   ├── register.php (+ step1/step2)   # Registo de utilizadores
│   ├── forgot_password.php            # Recuperação de password
│   ├── reset_password.php             # Reset de password
│   ├── verify_email.php               # Verificação de email
│   ├── registration_success.php       # Confirmação de registo
│   ├── search.php                     # Pesquisa global
│   ├── servers.php                    # Gestão de servidores
│   ├── services.php                   # Gestão de serviços
│   ├── support.php                    # Sistema de suporte/tickets
│   ├── logs.php                       # Logs do sistema
│   ├── manage_users.php               # Gestão de utilizadores
│   └── updates.php                    # Atualizações do sistema
│
├── 🔐 admin/                          # Painel administrativo
│   ├── alerts.php                     # Alertas do sistema
│   ├── contracts.php                  # Gestão de contratos
│   ├── customers.php                  # Gestão de clientes
│   ├── dashboard.php                  # Dashboard admin
│   ├── documents.php                  # Gestão de documentos
│   ├── expenses.php                   # Gestão de despesas
│   ├── knowledge-base.php             # Base de conhecimento
│   ├── licenses.php                   # Gestão de licenças
│   ├── live-chat.php                  # Chat ao vivo
│   ├── notes.php                      # Notas internas
│   ├── payment-warnings.php           # Avisos de pagamento
│   ├── payments.php                   # Gestão de pagamentos
│   ├── quotes.php                     # Orçamentos
│   ├── reports.php                    # Relatórios
│   ├── schedule.php                   # Agendamento
│   ├── services.php                   # Serviços admin
│   ├── settings.php                   # Configurações
│   ├── system-logs.php                # Logs do sistema
│   ├── tasks.php                      # Gestão de tarefas
│   ├── team.php                       # Gestão de equipa
│   ├── tickets.php                    # Tickets de suporte
│   └── updates.php                    # Atualizações admin
│
├── 🎨 assets/                         # Recursos estáticos (REORGANIZADO)
│   ├── css/
│   │   ├── auth/                      # Estilos de autenticação
│   │   │   └── auth-modern.css        # CSS moderno para login/register
│   │   ├── pages/                     # Estilos de páginas específicas
│   │   │   └── dashboard-modern.css   # CSS do dashboard profissional
│   │   └── shared/                    # Estilos partilhados
│   │       ├── design-system.css      # Sistema de design global
│   │       └── style.css              # Estilos base
│   │
│   ├── js/
│   │   ├── pages/                     # Scripts de páginas específicas
│   │   │   └── dashboard-modern.js    # JS do dashboard (sessões, AJAX)
│   │   └── shared/                    # Scripts partilhados
│   │       └── app.js                 # JavaScript global
│   │
│   └── uploads/                       # Uploads de utilizadores
│
├── 📚 docs/                           # Documentação
│   ├── EMAIL_TEMPLATES.md             # Guia de templates de email
│   ├── EMAIL_VERIFICATION.md          # Documentação de verificação
│   ├── INSTALL.md                     # Instruções de instalação
│   └── PERMISSIONS_GUIDE.md           # Guia de permissões
│
├── ⚙️ inc/                            # Includes PHP (Core do sistema)
│   ├── .htaccess                      # Proteção do diretório
│   ├── auth.php                       # Sistema de autenticação
│   ├── auth_theme.php                 # Temas de autenticação
│   ├── check_session.php              # Verificação de sessão (AJAX)
│   ├── config.php                     # Configurações gerais
│   ├── csrf.php                       # Proteção CSRF
│   ├── db.php                         # Conexão à base de dados
│   ├── db_credentials.php             # Credenciais BD (não versionado)
│   ├── debug.php                      # Ferramentas de debug
│   ├── email_templates.php            # Templates de email
│   ├── footer.php                     # Footer global
│   ├── get_dashboard_stats.php        # API stats do dashboard (AJAX)
│   ├── get_notification_count.php     # API notificações (AJAX)
│   ├── header.php                     # Header global
│   ├── mailer.php                     # Sistema de envio de emails
│   ├── maintenance.php                # Modo de manutenção
│   ├── permissions.php                # Sistema de permissões
│   ├── settings.php                   # Gestão de configurações
│   └── update_activity.php            # Atualização de atividade (AJAX)
│
├── 🔧 scripts/                        # Scripts utilitários
│   ├── migrate.php                    # Migrações de BD
│   ├── sample_users.php               # Utilizadores de teste
│   └── setup_identifier.php           # Setup de identificadores
│
├── 🗄️ sql/                            # Esquemas de base de dados
│   └── schema.sql                     # Esquema principal da BD
│
├── 📖 Documentação de Raiz
│   ├── README.md                      # Documentação principal
│   └── SETUP.md                       # Guia de configuração
│
└── ⚙️ Configuração
    ├── .env.example                   # Exemplo de variáveis de ambiente
    ├── .gitignore                     # Ficheiros ignorados pelo Git
    └── composer.json                  # Dependências PHP

```

## 🗑️ Ficheiros Eliminados

Durante a organização, foram removidos os seguintes ficheiros obsoletos:

- ❌ `dashboard-old.php` - Backup do dashboard antigo
- ❌ `test_db.php` - Ficheiro de teste de conexão BD
- ❌ `sql/legacy/` - Diretório completo com schemas antigos:
  - `2025_12_24_add_company_name_to_users.sql`
  - `changelog.sql`
  - `full_schema_2025_12_24.sql`
  - `password_resets.sql`
  - `roles_and_domains.sql`
  - `services.sql`
  - `settings.sql`
- ❌ `docs/DASHBOARD_DESIGN.md` - Documentação do dashboard antigo

## 🎯 Organização de Assets

### Antes (Desorganizado)
```
assets/
├── css/
│   ├── auth-modern.css
│   ├── dashboard-modern.css
│   ├── design-system.css
│   └── style.css
└── js/
    ├── app.js
    └── dashboard-modern.js
```

### Depois (Organizado)
```
assets/
├── css/
│   ├── auth/          # Autenticação
│   ├── pages/         # Páginas específicas
│   └── shared/        # Partilhado
└── js/
    ├── pages/         # Scripts de páginas
    └── shared/        # Scripts partilhados
```

## 📝 Mapeamento de Ficheiros

### CSS
| Ficheiro Original | Nova Localização | Uso |
|------------------|------------------|-----|
| `auth-modern.css` | `css/auth/auth-modern.css` | Login, Register, Password |
| `dashboard-modern.css` | `css/pages/dashboard-modern.css` | Dashboard principal |
| `design-system.css` | `css/shared/design-system.css` | Sistema de design global |
| `style.css` | `css/shared/style.css` | Estilos base |

### JavaScript
| Ficheiro Original | Nova Localização | Uso |
|------------------|------------------|-----|
| `dashboard-modern.js` | `js/pages/dashboard-modern.js` | Dashboard (sessões, AJAX) |
| `app.js` | `js/shared/app.js` | JavaScript global |

## 🔄 Ficheiros Atualizados

Os seguintes ficheiros foram atualizados para refletir a nova estrutura:

1. ✅ **dashboard.php** - Caminhos de CSS e JS
2. ✅ **login.php** - Caminho do CSS de autenticação
3. ✅ **register.php** - Caminho do CSS de autenticação
4. ✅ **forgot_password.php** - Caminho do CSS de autenticação
5. ✅ **registration_success.php** - Caminho do CSS partilhado
6. ✅ **verify_email.php** - Caminho do CSS partilhado
7. ✅ **inc/header.php** - Caminhos globais de CSS
8. ✅ **inc/footer.php** - Caminho global de JS

## 🚀 Próximos Passos

1. **Testar todos os caminhos** - Verificar que todos os assets carregam corretamente
2. **Cache do browser** - Limpar cache para ver mudanças
3. **Verificar admin/** - Confirmar que páginas admin ainda funcionam
4. **Documentar APIs** - Documentar endpoints AJAX criados
5. **Testes de integração** - Testar sessões e dashboard dinâmico

## 📊 Estatísticas do Projeto

- **Total de ficheiros PHP principais**: 24
- **Ficheiros admin**: 23
- **Ficheiros CSS**: 4 (organizados em 3 diretórios)
- **Ficheiros JS**: 2 (organizados em 2 diretórios)
- **Ficheiros de include**: 19
- **Scripts utilitários**: 3
- **Ficheiros eliminados**: 9

## ✨ Melhorias Implementadas

### Estrutura
- ✅ Eliminação de ficheiros obsoletos e backups
- ✅ Organização lógica de CSS por contexto (auth/pages/shared)
- ✅ Organização lógica de JS por contexto (pages/shared)
- ✅ Remoção de diretório legacy SQL

### Dashboard
- ✅ Refatoração completa com sessões PHP
- ✅ Integração de dados dinâmicos
- ✅ Auto-refresh de stats via AJAX
- ✅ Gestão de sessões em tempo real
- ✅ Notificações dinâmicas

### Segurança
- ✅ Validação de sessão automática
- ✅ Timeout de sessão (30 min)
- ✅ APIs AJAX protegidas
- ✅ Rastreamento de atividade

---

**Última atualização**: 26 de dezembro de 2025  
**Versão**: 2.0.0 (Estrutura Reorganizada)
