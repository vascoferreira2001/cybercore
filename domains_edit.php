<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/dashboard_helper.php';

checkRole(['Cliente','Suporte ao Cliente','Suporte Técnica','Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;
$pdo = getDB();

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT d.*, u.first_name, u.last_name, u.email FROM domains d JOIN users u ON d.user_id = u.id WHERE d.id = ?');
$stmt->execute([$id]);
$d = $stmt->fetch();
if (!$d) { header('Location: domains.php'); exit; }

// Permission checks
if ($user['role'] === 'Suporte Financeira') { http_response_code(403); echo 'Acesso negado.'; exit; }
if ($user['role'] !== 'Gestor' && $d['user_id'] != $user['id'] && !in_array($user['role'], ['Suporte ao Cliente','Suporte Técnica'])) { http_response_code(403); echo 'Acesso negado.'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
  $domain = trim($_POST['domain'] ?? '');
  $registered_on = $_POST['registered_on'] ?: null;
  $expires_on = $_POST['expires_on'] ?: null;
  $status = $_POST['status'] ?? 'active';
  $pdo->prepare('UPDATE domains SET domain=?,registered_on=?,expires_on=?,status=? WHERE id=?')->execute([$domain,$registered_on,$expires_on,$status,$id]);
  $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$user['id'],'domain_edit','Domain edited: '.$domain]);
  header('Location: domains.php'); exit;
}

$content = '<div class="card">
  <h2>Editar Domínio</h2>
  <form method="post">
    <input type="hidden" name="id" value="' . $d['id'] . '">
    ' . csrf_input() . '
    <div class="form-row"><label>Domínio</label><input type="text" name="domain" value="' . htmlspecialchars($d['domain']) . '" required></div>
    <div class="form-row"><label>Data Registo</label><input type="date" name="registered_on" value="' . $d['registered_on'] . '"></div>
    <div class="form-row"><label>Data Expiração</label><input type="date" name="expires_on" value="' . $d['expires_on'] . '"></div>
    <div class="form-row"><label>Status</label><select name="status"><option ' . ($d['status']==='active'?'selected':'') . ' value="active">Active</option><option ' . ($d['status']==='expired'?'selected':'') . ' value="expired">Expired</option><option ' . ($d['status']==='pending'?'selected':'') . ' value="pending">Pending</option></select></div>
    <div class="form-row"><button class="btn">Guardar</button></div>
  </form>
</div>';

echo renderDashboardLayout('Editar Domínio', 'Editar informações do domínio', $content, 'domains');
?>
