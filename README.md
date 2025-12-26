# CyberCore - Área de Cliente

**Plataforma de gestão de domínios, alojamento e suporte.**

---

## 🚀 Início Rápido

### Para Desenvolvedores

1. **Clonar e instalar:**
   ```bash
   git clone <repo>
   cd cybercore
   ```

2. **Criar ficheiro de credenciais:**
   ```bash
   cp inc/db_credentials.example.php inc/db_credentials.php
   # Editar com suas credenciais
   ```

3. **Importar base de dados:**
   ```bash
   mysql -u seu_user -p seu_db < sql/schema.sql
   ```

4. **Criar utilizadores de teste:**
   ```bash
   php scripts/sample_users.php
   ```

### Para Administradores / Deploy em Produção

**→ Leia [SETUP.md](SETUP.md) para instruções completas**

Contém:
- ✅ Setup do servidor
- ✅ Configuração de credenciais
- ✅ Troubleshooting
- ✅ Segurança
- ✅ Referência de rotas
- ✅ Permissões por role

---

## 📋 Estrutura

```
├── admin/           # Painel de administração
├── assets/          # CSS, JS, uploads
├── inc/             # Lógica reutilizável (auth, db, etc.)
├── scripts/         # Utilitários (migrate, sample_users)
├── sql/             # Schema (usar APENAS schema.sql)
├── docs/            # Documentação adicional
└── [*.php]          # Rotas públicas (login, register, dashboard, etc.)
```

---

## 🔑 Credenciais

**Método 1: Ficheiro local (recomendado)**
```bash
cp inc/db_credentials.example.php inc/db_credentials.php
```

**Método 2: Variáveis de ambiente**
```bash
export DB_HOST=127.0.0.1
export DB_NAME=cybercore
export DB_USER=cybercore
export DB_PASS='sua_password'
```

**⚠️ Importante:** `inc/db_credentials.php` está no `.gitignore` e NUNCA deve ser commitado.

---

## 👥 Utilizadores de Teste

Depois de importar `sql/schema.sql`:

```bash
php scripts/sample_users.php
```

Cria 5 utilizadores com password `Password123!`:
- `gestor@example.test` (Gestor)
- `cliente@example.test` (Cliente)
- `suporte_cliente@example.test` (Suporte ao Cliente)
- `suporte_financeiro@example.test` (Suporte Financeiro)
- `suporte_tecnico@example.test` (Suporte Técnico)

---

## 🔒 Segurança

- ✅ Passwords com bcrypt
- ✅ CSRF tokens em formulários
- ✅ Sessions com HttpOnly + SameSite=Strict
- ✅ Prepared statements (SQL Injection)
- ✅ Credenciais em ficheiro não versionado

---

## 📚 Documentação

| Ficheiro | Para |
|----------|------|
| [SETUP.md](SETUP.md) | Setup completo, troubleshooting, deploy |
| [docs/INSTALL.md](docs/INSTALL.md) | Instalação de raiz da BD |
| [docs/PERMISSIONS_GUIDE.md](docs/PERMISSIONS_GUIDE.md) | Guia detalhado de permissões |

---

## 🆘 Problema: "using password: NO"

→ **Leia [SETUP.md#troubleshooting](SETUP.md#-troubleshooting)**

Resumo:
```bash
# Criar ficheiro no servidor
ssh seu_servidor
cat > inc/db_credentials.php << 'EOF'
<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'cybercore');
define('DB_USER', 'cybercore');
define('DB_PASS', 'sua_password');
define('SITE_URL', 'https://seu-dominio.com');
define('SITE_NAME', 'CyberCore - Área de Cliente');
?>
EOF
chmod 600 inc/db_credentials.php
```

---

**Última atualização:** 24 de dezembro de 2025
