<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/permissions.php';

requireLogin();
$user = currentUser();

$pdo = getDB();
$accessLevel = getAccessLevel($pdo, $user, 'payment_warnings');

// Se não tem acesso, redireciona
if (!$accessLevel || $accessLevel === 'no') {
    http_response_code(403);
    echo 'Acesso negado ao recurso Avisos de Pagamento.';
    exit;
}

$canManage = in_array($accessLevel, ['manage_all', 'manage_own_clients', 'manage_own_clients_no_delete', 'manage_created', 'manage_created_no_delete']);
$canDelete = in_array($accessLevel, ['manage_all', 'manage_own_clients', 'manage_created']);

?>
<?php include __DIR__ . '/../inc/header.php'; ?>
<div class="card">
  <h2>Avisos de Pagamento</h2>
  
  <?php if ($accessLevel): ?>
    <div style="margin-bottom:16px;padding:12px;background:#e3f2fd;border-radius:4px">
      <strong>Seu nível de acesso:</strong> 
      <?php 
        $levels = [
          'manage_all' => 'Gerir todos',
          'view_all' => 'Ver todos (apenas leitura)',
          'manage_own_clients' => 'Gerir dos clientes próprios',
          'manage_own_clients_no_delete' => 'Gerir clientes próprios (sem eliminar)',
          'view_own_clients' => 'Ver clientes próprios (apenas leitura)',
          'manage_created' => 'Gerir criados por você',
          'manage_created_no_delete' => 'Gerir criados (sem eliminar)'
        ];
        echo htmlspecialchars($levels[$accessLevel] ?? $accessLevel);
      ?>
    </div>
  <?php endif; ?>
  
  <?php if ($canManage): ?>
    <button class="btn" style="margin-bottom:12px">+ Novo Aviso</button>
  <?php endif; ?>
  
  <p>Gestão de Avisos de Pagamento — em desenvolvimento.</p>
  <p><small>O nível de acesso está a ser validado baseado nas permissões configuradas.</small></p>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>
<?php include __DIR__ . '/../inc/footer.php'; ?>
