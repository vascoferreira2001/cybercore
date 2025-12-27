# 🏗️ Arquitetura API de Hosting (cPanel/Plesk)

## 📋 Visão Geral

Sistema de gestão automática de contas de hosting através de integração com cPanel e Plesk, permitindo criação, suspensão, migração e gestão completa via painel CyberCore.

---

## 🎯 Objetivos

- ✅ Criar contas de hosting automaticamente
- ✅ Suspender/ativar contas por falta de pagamento
- ✅ Migrar entre planos
- ✅ Sincronizar dados (uso, quotas, status)
- ✅ Auto-login seguro para clientes
- ✅ Gestão multi-servidor

---

## 👥 Estrutura de Menus

### Menu Cliente
```
📊 Dashboard
├── 🌐 Alojamentos Web (view-only)
│   ├── Ver detalhes do alojamento
│   ├── Uso de recursos (espaço, bandwidth)
│   ├── Acesso rápido ao painel (cPanel/Plesk)
│   └── Renovações pendentes
├── 🌍 Domínios
├── 💰 Faturação
└── 🎧 Suporte
```

### Menu Administração
```
📊 Dashboard
├── 👥 Clientes
├── 🛠️ Gestão de Alojamentos ⭐ NOVO
│   ├── Criar Alojamento
│   ├── Listar Todos os Alojamentos
│   ├── Suspender/Ativar
│   ├── Migrar Plano
│   ├── Eliminar Conta
│   └── Sincronizar com Servidor
├── 🖥️ Servidores ⭐ NOVO
│   ├── Adicionar Servidor (cPanel/Plesk)
│   ├── Configurar API Tokens
│   ├── Monitorizar Carga
│   └── Estado dos Servidores
├── 📦 Planos de Hosting ⭐ NOVO
│   ├── Criar Plano
│   ├── Definir Recursos (espaço, bandwidth, DBs)
│   ├── Preços
│   └── Associar a Servidores
├── 💳 Pagamentos
├── 🎫 Tickets
└── ⚙️ Configurações
```

---

## 🗄️ Estrutura de Base de Dados

### Nova Tabela: `servers`
```sql
CREATE TABLE servers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL COMMENT 'Nome do servidor (ex: Server-PT-01)',
  hostname VARCHAR(255) NOT NULL COMMENT 'server.example.com',
  ip_address VARCHAR(45) COMMENT 'IP público',
  
  -- Control Panel
  control_panel ENUM('cpanel', 'plesk') NOT NULL,
  panel_version VARCHAR(50),
  api_endpoint VARCHAR(500) NOT NULL COMMENT 'https://server:2087 ou :8443',
  
  -- Autenticação
  api_token TEXT COMMENT 'Token encriptado',
  api_username VARCHAR(100) COMMENT 'Username (se usar basic auth)',
  
  -- Capacidade
  max_accounts INT DEFAULT 100 COMMENT 'Limite de contas',
  current_accounts INT DEFAULT 0 COMMENT 'Contas ativas',
  
  -- Estado
  status ENUM('active', 'maintenance', 'offline', 'full') DEFAULT 'active',
  last_check_at TIMESTAMP NULL COMMENT 'Última verificação de health',
  
  -- Localização
  datacenter VARCHAR(100) COMMENT 'Portugal, Alemanha, etc',
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_status (status),
  INDEX idx_control_panel (control_panel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Atualização: `web_hosting`
```sql
ALTER TABLE web_hosting 
ADD COLUMN server_id INT AFTER user_id,
ADD COLUMN control_panel ENUM('cpanel', 'plesk') AFTER server_id,
ADD COLUMN remote_username VARCHAR(255) COMMENT 'Username no cPanel/Plesk',
ADD COLUMN package_name VARCHAR(255) COMMENT 'Nome do plano no painel',
ADD COLUMN disk_used_mb INT DEFAULT 0,
ADD COLUMN disk_limit_mb INT DEFAULT 0,
ADD COLUMN bandwidth_used_mb INT DEFAULT 0,
ADD COLUMN bandwidth_limit_mb INT DEFAULT 0,
ADD COLUMN last_sync_at TIMESTAMP NULL COMMENT 'Última sincronização com servidor',
ADD FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE RESTRICT;
```

### Nova Tabela: `hosting_packages`
```sql
CREATE TABLE hosting_packages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL COMMENT 'Ex: Starter, Business, Pro',
  display_name VARCHAR(255) NOT NULL,
  description TEXT,
  
  -- Recursos
  disk_space_mb INT NOT NULL COMMENT '1024 = 1GB',
  bandwidth_mb INT NOT NULL COMMENT 'Mensal',
  email_accounts INT DEFAULT 0 COMMENT '0 = ilimitado',
  databases INT DEFAULT 0,
  domains INT DEFAULT 1 COMMENT 'Domínios adicionais permitidos',
  
  -- Preços
  monthly_price DECIMAL(10,2) NOT NULL,
  annual_price DECIMAL(10,2) COMMENT 'Preço anual (se aplicável)',
  
  -- Mapeamento para painéis
  cpanel_package_name VARCHAR(255) COMMENT 'Nome do package no cPanel',
  plesk_plan_name VARCHAR(255) COMMENT 'Nome do plan no Plesk',
  
  -- Estado
  is_active BOOLEAN DEFAULT TRUE,
  is_featured BOOLEAN DEFAULT FALSE,
  sort_order INT DEFAULT 0,
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🔧 Arquitetura de Código

### Estrutura de Ficheiros
```
inc/
├── hosting/
│   ├── HostingAdapter.php        # Interface abstrata
│   ├── CpanelAdapter.php         # Implementação cPanel
│   ├── PleskAdapter.php          # Implementação Plesk
│   └── ServerManager.php         # Gestão de servidores (load balancing)
│
├── api/
│   └── hosting.php               # Endpoint REST para operações
│
└── hosting_helpers.php           # Funções auxiliares

admin/
├── hosting.php                   # Gestão de alojamentos
├── servers.php                   # Gestão de servidores
└── hosting-packages.php          # Gestão de planos
```

---

## 📡 API Interna (REST Endpoint)

### Endpoint: `/inc/api/hosting.php`

#### Formato Request
```json
POST /inc/api/hosting.php
Content-Type: application/json

{
  "action": "createAccount",
  "data": {
    "user_id": 123,
    "domain": "cliente.com",
    "package_id": 5,
    "server_id": 2
  }
}
```

#### Actions Disponíveis

| Action | Descrição | Permissão Necessária |
|--------|-----------|---------------------|
| `createAccount` | Criar nova conta de hosting | `can_manage_hosting` |
| `suspendAccount` | Suspender conta | `can_manage_hosting` |
| `unsuspendAccount` | Reativar conta | `can_manage_hosting` |
| `changePackage` | Migrar plano | `can_manage_hosting` |
| `deleteAccount` | Eliminar conta | `can_manage_hosting` |
| `syncAccount` | Sincronizar dados do servidor | `can_manage_hosting` |
| `getAccountInfo` | Ver detalhes da conta | `can_view_hosting` |
| `listAccounts` | Listar todas as contas | `can_view_all_hosting` |
| `generateAutoLogin` | Token de auto-login para cliente | `can_view_own_hosting` |

#### Formato Response
```json
{
  "success": true,
  "message": "Conta criada com sucesso",
  "data": {
    "hosting_id": 456,
    "remote_username": "cliente_com",
    "server": "Server-PT-01"
  }
}
```

---

## 🔌 Adaptadores (Interface Comum)

### Interface: `HostingAdapter.php`
```php
interface HostingAdapter {
  /**
   * Criar conta de hosting
   */
  public function createAccount(
    string $domain, 
    string $username, 
    string $package, 
    string $email,
    ?string $password = null
  ): array;
  
  /**
   * Suspender conta
   */
  public function suspendAccount(string $username, string $reason = ''): array;
  
  /**
   * Reativar conta
   */
  public function unsuspendAccount(string $username): array;
  
  /**
   * Mudar plano/package
   */
  public function changePackage(string $username, string $newPackage): array;
  
  /**
   * Obter detalhes da conta (uso, quotas, etc)
   */
  public function getAccountDetails(string $username): array;
  
  /**
   * Eliminar conta permanentemente
   */
  public function deleteAccount(string $username, bool $keepDns = false): array;
  
  /**
   * Listar todas as contas do servidor
   */
  public function listAccounts(): array;
  
  /**
   * Gerar token de auto-login
   */
  public function generateAutoLoginUrl(string $username): ?string;
  
  /**
   * Verificar health do servidor
   */
  public function checkHealth(): array;
}
```

---

## 🎮 Implementação cPanel

### Funções da API cPanel
| Função | Endpoint cPanel | Descrição |
|--------|----------------|-----------|
| Criar conta | `createacct` | WHM API1 |
| Suspender | `suspendacct` | WHM API1 |
| Reativar | `unsuspendacct` | WHM API1 |
| Mudar plano | `changepackage` | WHM API1 |
| Info da conta | `accountsummary` | WHM API1 |
| Eliminar | `removeacct` | WHM API1 |
| Listar contas | `listaccts` | WHM API1 |
| Auto-login | User Session Token | UAPI |

### Autenticação
- **Método recomendado**: API Token (WHM > API Tokens)
- **Header**: `Authorization: whm root:TOKEN_AQUI`
- **Endpoint base**: `https://servidor.com:2087/json-api/`

### Exemplo Request (criar conta)
```bash
curl -H "Authorization: whm root:ABCD123TOKEN" \
  "https://server.com:2087/json-api/createacct?username=cliente_com&domain=cliente.com&plan=starter&contactemail=email@cliente.com"
```

---

## 🔷 Implementação Plesk

### Funções da API Plesk
| Função | XML Packet | Descrição |
|--------|-----------|-----------|
| Criar conta | `<webspace><add>` | Plesk XML API |
| Suspender | `<webspace><set><status>` | Suspend status |
| Reativar | `<webspace><set><status>` | Active status |
| Mudar plano | `<webspace><switch-subscription>` | Change plan |
| Info | `<webspace><get>` | Get details |
| Eliminar | `<webspace><del>` | Delete |
| Listar | `<webspace><get>` com filter | List all |

### Autenticação
- **Método**: API Key ou Basic Auth
- **Header**: `X-API-Key: YOUR_KEY` ou `Authorization: Basic base64(user:pass)`
- **Endpoint**: `https://servidor.com:8443/enterprise/control/agent.php`
- **Formato**: XML Request/Response

### Exemplo Request (criar conta)
```xml
POST https://server.com:8443/enterprise/control/agent.php
Content-Type: text/xml

<packet>
  <webspace>
    <add>
      <gen_setup>
        <name>cliente.com</name>
        <owner-login>admin</owner-login>
        <ip>192.168.1.1</ip>
      </gen_setup>
      <hosting>
        <vrt_hst>
          <ftp_login>cliente_com</ftp_login>
          <ftp_password>senha123</ftp_password>
        </vrt_hst>
      </hosting>
      <plan-name>starter</plan-name>
    </add>
  </webspace>
</packet>
```

---

## 🔐 Segurança

### Tokens API
- Guardar `api_token` na BD encriptado:
```php
$encrypted = openssl_encrypt($token, 'AES-256-CBC', $key, 0, $iv);
```
- Desencriptar apenas quando necessário
- NUNCA expor tokens em logs ou responses

### Permissões
```php
// inc/menu_config.php - adicionar novos flags
'can_manage_hosting' => true,      // Criar/editar/suspender
'can_view_all_hosting' => true,    // Ver todas as contas
'can_manage_servers' => true,      // Gerir servidores
'can_view_own_hosting' => true,    // Cliente ver seus alojamentos
```

### Rate Limiting
- Limitar chamadas à API externa (evitar ban)
- Cache de dados não-críticos (5-15 min)
- Queue para operações em massa

### Logs
Registar todas as operações:
```sql
INSERT INTO logs (user_id, action, details, ip_address) 
VALUES (?, 'hosting.create', JSON, ?);
```

---

## 📊 Fluxos de Trabalho

### 1. Criação de Conta (Admin)
```mermaid
Admin → Form "Criar Alojamento"
  ↓
Escolhe: Cliente, Domínio, Plano, Servidor
  ↓
POST /inc/api/hosting.php?action=createAccount
  ↓
Validação de permissões
  ↓
ServerManager escolhe servidor (se não especificado)
  ↓
Instancia CpanelAdapter ou PleskAdapter
  ↓
Chama API externa (createacct / webspace add)
  ↓
Sucesso? → Grava na BD web_hosting
  ↓
Atualiza servers.current_accounts
  ↓
Log da operação
  ↓
Response JSON → Frontend toast
```

### 2. Suspensão Automática (Cron)
```mermaid
Cron Job diário
  ↓
SELECT web_hosting WHERE status='unpaid'
  ↓
Para cada conta:
  ↓
POST /inc/api/hosting.php?action=suspendAccount
  ↓
Adapter suspende no servidor remoto
  ↓
Atualiza web_hosting.status = 'suspended'
  ↓
Envia email ao cliente
  ↓
Log
```

### 3. Cliente Acessa Painel
```mermaid
Cliente → "Meus Alojamentos"
  ↓
Lista alojamentos (query web_hosting WHERE user_id=X)
  ↓
Botão "Aceder ao cPanel"
  ↓
POST /inc/api/hosting.php?action=generateAutoLogin
  ↓
Adapter gera token temporário
  ↓
Redirect para https://server.com:2083/...?token=XYZ
  ↓
Auto-login no cPanel/Plesk
```

---

## 🚀 Fases de Implementação

### Fase 1: Base de Dados e Estrutura (1-2 dias)
- [ ] Criar tabela `servers`
- [ ] Criar tabela `hosting_packages`
- [ ] Alterar tabela `web_hosting` (adicionar colunas)
- [ ] Criar interface `HostingAdapter.php`
- [ ] Criar `ServerManager.php` (load balancing básico)

### Fase 2: Implementação cPanel (3-4 dias)
- [ ] Implementar `CpanelAdapter.php`
  - [ ] createAccount
  - [ ] suspendAccount / unsuspendAccount
  - [ ] changePackage
  - [ ] getAccountDetails
  - [ ] deleteAccount
  - [ ] listAccounts
  - [ ] generateAutoLoginUrl
- [ ] Testar com servidor cPanel de testes

### Fase 3: API Interna (2-3 dias)
- [ ] Criar `/inc/api/hosting.php`
- [ ] Routing por action
- [ ] Validações (CSRF, permissões)
- [ ] Logs completos
- [ ] Tratamento de erros

### Fase 4: UI Admin (2-3 dias)
- [ ] Página `/admin/servers.php` (gestão de servidores)
- [ ] Página `/admin/hosting.php` (gestão de contas)
- [ ] Página `/admin/hosting-packages.php` (gestão de planos)
- [ ] Formulários de criação/edição
- [ ] Tabelas com filtros

### Fase 5: UI Cliente (1-2 dias)
- [ ] Página `/hosting.php` (view-only)
- [ ] Exibir uso de recursos (gráficos)
- [ ] Botão de acesso rápido ao painel
- [ ] Informações de renovação

### Fase 6: Plesk (Opcional, 2-3 dias)
- [ ] Implementar `PleskAdapter.php`
- [ ] Testar com servidor Plesk

### Fase 7: Automação (2 dias)
- [ ] Cron job de sincronização (uso, quotas)
- [ ] Cron job de suspensão automática (unpaid)
- [ ] Notificações (email quando suspenso/reativado)

### Fase 8: Melhorias (contínuo)
- [ ] Load balancing inteligente (escolher servidor com menos carga)
- [ ] Migração entre servidores
- [ ] Backup automático antes de ações destrutivas
- [ ] Dashboard de monitorização de servidores
- [ ] Integração com sistema de billing (renovações automáticas)

---

## 📈 Métricas e Monitorização

### Dashboard Admin - Métricas de Hosting
```
📊 Visão Geral
├── Total de Contas: 245
├── Contas Ativas: 230
├── Contas Suspensas: 15
├── Uso Médio de Espaço: 45%
├── Servidores Online: 3/3
└── Capacidade Disponível: 155 contas
```

### Alertas
- Servidor com >90% de capacidade → notificar admin
- Conta com >90% de quota → notificar cliente
- Servidor offline → notificar admin imediatamente
- Falha na criação de conta → ticket automático

---

## 🔗 Recursos Úteis

### Documentação Oficial
- **cPanel API**: https://api.docs.cpanel.net/
- **Plesk API**: https://docs.plesk.com/en-US/obsidian/api-rpc/
- **WHM API Reference**: https://documentation.cpanel.net/display/DD/Guide+to+WHM+API+1

### Bibliotecas PHP (Opcionais)
- `gufy/cpanel-whm`: Wrapper PHP para cPanel (Composer)
- `plesk/api-php-lib`: Cliente oficial Plesk

### Testes
- cPanel Demo: https://demo.cpanel.net/
- Sandbox Plesk: Contactar suporte Plesk

---

## 💡 Notas Finais

- **Começar com cPanel** (mais comum)
- **Testar sempre em ambiente de desenvolvimento** antes de produção
- **Backup**: fazer backup de contas antes de operações destrutivas
- **Documentar tokens e credenciais** de forma segura (1Password, Vault)
- **Monitorizar logs de API** para detetar problemas precocemente

---

**Última atualização:** 27 de dezembro de 2025
