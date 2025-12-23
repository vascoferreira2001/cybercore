<?php
require_once __DIR__ . '/../inc/auth.php';
requireLogin();
$user = currentUser();
if (!in_array($user['role'], ['Gestor','Suporte ao Cliente','Suporte Técnica','Suporte Financeira'])) {
    http_response_code(403);
    echo 'Acesso negado.';
    exit;
}
?>
<?php include __DIR__ . '/../inc/header.php'; ?>
<div class="card">
  <h2>Tarefas</h2>
  <p>Gestão de tarefas — em desenvolvimento.</p>
</div>
<?php include __DIR__ . '/../inc/footer.php'; ?>
