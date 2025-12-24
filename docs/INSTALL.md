# Instalação Limpa do CyberCore

## ⚠️ IMPORTANTE: Usar APENAS `sql/schema.sql`

### Passos para Instalação de Raiz

1. **Remover base de dados existente (se necessário)**:
```bash
mysql -u USER -p -e "DROP DATABASE IF EXISTS cybercore;"
```

2. **Importar schema completo**:
```bash
mysql -u USER -p < sql/schema.sql
```

3. **Verificar tabelas criadas**:
```bash
mysql -u USER -p cybercore -e "SHOW TABLES;"
```

Deve ver estas tabelas:
- users (com colunas `role` e `company_name`)
- tickets
- domains
- logs
- invoices
- settings
- departments
- department_permissions
- client_permissions
- password_resets
- web_hosting
- email_hosting
- dedicated_servers
- vps_servers
- website_maintenance
- website_development
- social_media_management
- changelog

### ❌ NÃO IMPORTAR Ficheiros Legacy

Os ficheiros em `sql/legacy/` são **apenas para referência**:
- ❌ `roles_and_domains.sql` → causará erro #1060 (coluna `role` duplicada)
- ❌ `services.sql` → causará erro #1005 (foreign key inválida)
- ❌ `settings.sql`, `password_resets.sql`, `changelog.sql` → duplicações

**Todos já estão integrados em `sql/schema.sql`**.

### Criar Utilizadores de Teste (Opcional)

Após importar o schema:
```bash
php scripts/sample_users.php
```

Utilizadores criados:
- `gestor@example.test` / `Password123!` (Gestor)
- `cliente@example.test` / `Password123!` (Cliente)
- `suporte_cliente@example.test` / `Password123!` (Suporte ao Cliente)
- `suporte_finance@example.test` / `Password123!` (Suporte Financeira)
- `suporte_tecnica@example.test` / `Password123!` (Suporte Técnica)

### Troubleshooting

**Erro: "Table already exists"**
- Solução: fazer DROP DATABASE e reimportar limpo

**Erro: "Foreign key constraint"**
- Causa: importou ficheiros legacy fora de ordem
- Solução: DROP DATABASE e usar apenas `schema.sql`

**Erro: "Duplicate column"**
- Causa: importou `roles_and_domains.sql` após `schema.sql`
- Solução: DROP DATABASE e usar apenas `schema.sql`

### Migração de Base Existente (Avançado)

Se já tem dados em produção e precisa adicionar as novas colunas:

```sql
-- Adicionar company_name se não existir
ALTER TABLE users ADD COLUMN IF NOT EXISTS company_name VARCHAR(255) DEFAULT NULL AFTER entity_type;

-- Seeds de manutenção (se não existirem)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('maintenance_disable_login', '0'),
('maintenance_message', ''),
('maintenance_exception_roles', 'Gestor'),
('maintenance_hide_menus', '[]');

-- Seeds de permissões de cliente (se não existirem)
INSERT IGNORE INTO client_permissions (permission_key, allowed) VALUES
('disable_account_creation', 0),
('verify_email_before_login', 0),
('client_view_documents', 1),
('client_add_documents', 0);
```

---

**Resumo**: Use **APENAS** `sql/schema.sql`. Ignore tudo em `sql/legacy/`.
