<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/permissions.php';

requireLogin();
$user = currentUser();

$pdo = getDB();
$accessLevel = getAccessLevel($pdo, $user, 'expenses');

// Se não tem acesso, redireciona
if (!$accessLevel || $accessLevel === 'no') {
    http_response_code(403);
    echo 'Acesso negado ao recurso Despesas.';
    exit;
}

$canManageAll = $user['role'] === 'Gestor' || $accessLevel === 'all';
$canManageOwn = in_array($accessLevel, ['own', 'manage_own']);

?>
<?php include __DIR__ . '/../inc/header.php'; ?>
<div class="card">
  <h2>Despesas</h2>
  
  <?php if ($accessLevel): ?>
    <div style="margin-bottom:16px;padding:12px;background:#e3f2fd;border-radius:4px">
      <strong>Seu nível de acesso:</strong> 
      <?php 
        $levels = [
          'all' => 'Todas as despesas',
          'own' => 'Apenas despesas próprias',
          'manage_own' => 'Gerir despesas próprias'
        ];
        echo htmlspecialchars($levels[$accessLevel] ?? $accessLevel);
      ?>
    </div>
  <?php endif; ?>
  
  <?php if ($canManageAll || $canManageOwn): ?>
    <button class="btn" style="margin-bottom:12px">+ Nova Despesa</button>
  <?php endif; ?>
  
  <p>Gestão de Despesas — em desenvolvimento.</p>
  <p><small>O nível de acesso está a ser validado baseado nas permissões configuradas.</small></p>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>
<?php include __DIR__ . '/../inc/footer.php'; ?>
