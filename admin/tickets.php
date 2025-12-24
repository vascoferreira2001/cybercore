<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/permissions.php';

requireLogin();
$user = currentUser();

$pdo = getDB();
$accessLevel = getAccessLevel($pdo, $user, 'tickets');

// Se não tem acesso, redireciona
if (!$accessLevel || $accessLevel === 'no') {
    http_response_code(403);
    echo 'Acesso negado ao recurso Tickets.';
    exit;
}

$canManage = $user['role'] === 'Gestor' || in_array($accessLevel, ['manage_all', 'manage_created']);
$viewAssignedOnly = $accessLevel === 'assigned';
$viewCategoriesOnly = $accessLevel === 'categories';

?>
<?php include __DIR__ . '/../inc/header.php'; ?>
<div class="card">
  <h2>Tickets do Sistema de Suporte</h2>
  
  <?php if ($accessLevel): ?>
    <div style="margin-bottom:16px;padding:12px;background:#e3f2fd;border-radius:4px">
      <strong>Seu nível de acesso:</strong> 
      <?php 
        $levels = [
          'all' => 'Todos os tickets',
          'assigned' => 'Apenas tickets atribuídos',
          'categories' => 'Categorias específicas',
          'manage_all' => 'Gerir todos',
          'manage_created' => 'Gerir criados por você'
        ];
        echo htmlspecialchars($levels[$accessLevel] ?? $accessLevel);
      ?>
    </div>
  <?php endif; ?>
  
  <?php if ($canManage): ?>
    <button class="btn" style="margin-bottom:12px">+ Novo Ticket</button>
  <?php endif; ?>
  
  <p>Sistema de Tickets — em desenvolvimento.</p>
  <p><small>O nível de acesso está a ser validado baseado nas permissões configuradas.</small></p>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>
<?php include __DIR__ . '/../inc/footer.php'; ?>
