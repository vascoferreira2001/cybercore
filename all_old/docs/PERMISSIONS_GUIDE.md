# Sistema de Permissões - Guia de Utilização

> ⚠️ Obsoleto: este guia descreve o modelo antigo baseado em departamentos. O modelo atual é role-based em `inc/menu_config.php` + `inc/permissions.php`. Para a referência oficial, use [docs/PERMISSIONS.md](PERMISSIONS.md).

## Overview

O sistema de permissões controla o acesso granular a recursos e funcionalidades baseado nas permissões configuradas em **Configuração > Funções da Equipa**.

Cada departamento (Suporte ao Cliente, Suporte Técnico, Suporte Financeiro, Gestor) tem permissões específicas que controlam:
- Que páginas/recursos podem aceder
- Que ações podem realizar (visualizar, editar, gerir, eliminar)
- Que dados podem ver (próprios, clientes específicos, todos)

---

## Funções Disponíveis (em `inc/permissions.php`)

### 1. `getUserPermissions($pdo, $user)`
Obtém **todas as permissões** do utilizador baseado no seu departamento.

**Retorna:** Array com todas as permissões
```php
$permissions = getUserPermissions($pdo, $user);
// Resultado: ['customers' => 'all', 'tickets' => 'assigned', 'expenses' => 'own', ...]
```

---

### 2. `hasPermission($pdo, $user, $permissionKey, $expectedValue = null)`
Verifica se um utilizador tem uma permissão **específica**.

**Parâmetros:**
- `$permissionKey`: Chave da permissão (ex: 'customers', 'tickets')
- `$expectedValue`: Valor esperado (ex: 'yes', 'all'). Se vazio, apenas verifica se existe.

**Exemplos:**
```php
// Verificar se tem acesso a clientes
if (hasPermission($pdo, $user, 'customers')) {
    echo "Tem acesso";
}

// Verificar se pode gerir todos
if (hasPermission($pdo, $user, 'customers', 'all|manage_all')) {
    // Permissão especificada, um dos valores
}

// Verificar se tem valor específico
if (hasPermission($pdo, $user, 'licenses', 'all')) {
    echo "Pode gerir licenças de todos";
}
```

---

### 3. `canAccessResource($pdo, $user, $resource, $level = 'view')`
Verifica se um utilizador pode **aceder a um recurso** num determinado nível.

**Parâmetros:**
- `$resource`: Nome do recurso ('customers', 'tickets', 'expenses', etc.)
- `$level`: Nível de acesso ('view', 'manage', 'all')

**Exemplos:**
```php
// Pode visualizar clientes?
if (canAccessResource($pdo, $user, 'customers', 'view')) {
    // Mostrar lista de clientes
}

// Pode gerir tickets?
if (canAccessResource($pdo, $user, 'tickets', 'manage')) {
    // Mostrar botões de edição/eliminação
}

// Acesso total?
if (canAccessResource($pdo, $user, 'services', 'all')) {
    // Acesso completo
}
```

---

### 4. `getAccessLevel($pdo, $user, $resource)`
Obtém o **nível de acesso exato** para um recurso.

**Retorna:** String com o valor da permissão (ex: 'all', 'own', 'assigned', etc.) ou null

**Exemplo:**
```php
$level = getAccessLevel($pdo, $user, 'customers');
// Resultado: 'all' ou 'manage_own_clients' ou 'own' ou null

switch ($level) {
    case 'all':
        $query = "SELECT * FROM customers";
        break;
    case 'manage_own_clients':
        $query = "SELECT * FROM customers WHERE assigned_to = ?";
        $params = [$user['id']];
        break;
    case 'own':
        $query = "SELECT * FROM customers WHERE user_id = ?";
        $params = [$user['id']];
        break;
    default:
        $query = null;
}
```

---

### 5. `deniesPermission($pdo, $user, $permissionKey)`
Verifica se um utilizador **NÃO tem** permissão.

**Exemplo:**
```php
if (deniesPermission($pdo, $user, 'expenses')) {
    http_response_code(403);
    echo "Acesso negado.";
    exit;
}
```

---

### 6. `requirePermission($pdo, $user, $resource, $level = 'view', $message = 'Acesso negado.')`
Valida acesso e **termina a execução se negado** (usa no topo das páginas).

**Exemplo:**
```php
// No topo de admin/customers.php
requirePermission($pdo, $user, 'customers', 'view');
// Se não tiver acesso, para e mostra erro 403
```

---

### 7. `requireAnyPermission($pdo, $user, $resources, $message = 'Acesso negado.')`
Requer **pelo menos um** dos recursos especificados.

**Exemplo:**
```php
// Precisa de acesso a tickets OU clientes
requireAnyPermission($pdo, $user, ['tickets', 'customers']);
```

---

### 8. `requireAllPermissions($pdo, $user, $resources, $message = 'Acesso negado.')`
Requer **todos** os recursos especificados.

**Exemplo:**
```php
// Precisa de acesso a clientes E tickets
requireAllPermissions($pdo, $user, ['customers', 'tickets']);
```

---

## Permissões Disponíveis

### Administração
- `admin_settings` - Pode gerir configurações
- `admin_add_members` - Pode adicionar membros
- `admin_enable_members` - Pode ativar/desativar
- `admin_delete_members` - Pode eliminar membros

### Membros da Equipa
- `members_visibility` - Esconder lista / Mostrar / etc
- `members_contact` - Pode ver contactos
- `members_social` - Pode ver redes sociais
- `members_update` - Pode atualizar: 'no', 'all', 'specific'
- `members_notes` - Pode gerir notas

### Comunicação
- `messaging` - Pode enviar mensagens: 'no', 'all', 'specific'

### Recursos Principais
- `customers` - Acesso a clientes: 'no', 'all', 'own', 'readonly', 'specific'
- `tickets` - Acesso a tickets: 'no', 'all', 'assigned', 'categories'
- `services` - Acesso a serviços/domínios
- `quotes` - Acesso a orçamentos
- `contracts` - Acesso a contratos
- `proposals` - Acesso a propostas
- `expenses` - Acesso a despesas: 'no', 'all', 'own'
- `payment_warnings` - Avisos de pagamento com múltiplas opções
- `licenses` - Gerir licenças
- `work_schedule` - Painel horário
- `alerts` - Gerir avisos
- `knowledge_base` - Banco de conhecimentos

---

## Exemplo Completo de Implementação

### No topo de uma página (ex: admin/customers.php)

```php
<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/permissions.php';

requireLogin();
$user = currentUser();
$pdo = getDB();

// Validar acesso ao recurso
$accessLevel = getAccessLevel($pdo, $user, 'customers');

if (!$accessLevel || $accessLevel === 'no') {
    http_response_code(403);
    echo 'Acesso negado ao recurso Clientes.';
    exit;
}

// Determinar permissões específicas
$canManage = in_array($accessLevel, ['all', 'manage_all', 'manage_own_clients']);
$canViewAll = in_array($accessLevel, ['all', 'view_all']);
$viewOwnOnly = in_array($accessLevel, ['own', 'manage_own_clients']);

?>
<?php include __DIR__ . '/../inc/header.php'; ?>
<div class="card">
  <h2>Clientes</h2>
  
  <!-- Mostrar botão só se pode gerir -->
  <?php if ($canManage): ?>
    <button class="btn">+ Novo Cliente</button>
  <?php endif; ?>
  
  <!-- Carregar dados conforme nível -->
  <?php
    if ($viewOwnOnly) {
      $query = "SELECT * FROM customers WHERE assigned_to = ?";
      $params = [$user['id']];
    } else {
      $query = "SELECT * FROM customers";
      $params = [];
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
  ?>
  
  <table>
    <!-- ... listar clientes ... -->
  </table>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>
```

---

## Notas Importantes

1. **Gestor tem sempre permissão total** - O role 'Gestor' ignora todas as validações
2. **Sempre incluir permissions.php** - `require_once __DIR__ . '/../inc/permissions.php';`
3. **Usar no topo da página** - Validar antes de qualquer lógica
4. **Seguir padrão de nomenclatura** - Usar nomes consistentes para os recursos
5. **Cache de permissões** - Para melhor performance em páginas com múltiplas verificações, guarde `$permissions = getUserPermissions($pdo, $user);` numa variável

---

## Próximos Passos

As funções estão implementadas em:
- `admin/dashboard.php` - Mostra métricas conforme permissões
- `admin/customers.php` - Valida acesso e mostra nível
- `admin/tickets.php` - Valida e controla visualização
- `admin/expenses.php` - Controla por nível
- `admin/payment-warnings.php` - Múltiplas opções de acesso

Pode aplicar o mesmo padrão nas restantes páginas!
