<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';
require_once __DIR__ . '/../inc/permissions.php';

checkRole(['Gestor','Suporte ao Cliente']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$pdo = getDB();
$accessLevel = getAccessLevel($pdo, $user, 'customers');

// Se não tem acesso, redireciona
if (!$accessLevel || $accessLevel === 'no') {
    http_response_code(403);
    echo 'Acesso negado ao recurso Clientes.';
    exit;
}

// Determinar que dados mostrar baseado no nível de acesso
$canManage = in_array($accessLevel, ['all', 'manage_all', 'manage_own_clients', 'manage_created']);
$canViewAll = in_array($accessLevel, ['all', 'view_all', 'manage_all']);
$viewOwnOnly = in_array($accessLevel, ['own', 'manage_own_clients']);

$levels = [
    'all' => 'Acesso total',
    'manage_all' => 'Gerir todos',
    'view_all' => 'Ver todos (apenas leitura)',
    'manage_own_clients' => 'Gerir clientes próprios',
    'own' => 'Ver clientes próprios',
    'readonly' => 'Apenas leitura',
    'specific' => 'Grupos específicos'
];

ob_start();
?>
<div class="card">
  <h2>Clientes</h2>
  
  <?php if ($accessLevel): ?>
    <div style="margin-bottom:16px;padding:12px;background:#e3f2fd;border-radius:4px">
      <strong>Seu nível de acesso:</strong> 
      <?php echo htmlspecialchars($levels[$accessLevel] ?? $accessLevel); ?>
    </div>
  <?php endif; ?>
  
  <?php if ($canManage): ?>
    <button class="btn" style="margin-bottom:12px">+ Novo Cliente</button>
  <?php endif; ?>
  
  <p>Gestão de clientes — em desenvolvimento.</p>
  <p><small>Nota: O nível de acesso está a ser validado baseado nas permissões configuradas em Configuração > Funções da Equipa.</small></p>
</div>
<?php
$content = ob_get_clean();
echo renderDashboardLayout('Clientes', 'Gestão de clientes', $content, 'customers');
?>
