<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/dashboard_helper.php';

checkRole(['Cliente','Suporte ao Cliente','Suporte Técnico','Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;
$pdo = getDB();

// Permissions rules (default):
// - Gestor: full access to all domains
// - Suporte ao Cliente / Suporte Técnico: view all, create/edit but not delete
// - Suporte Financeiro: no domain management by default
// - Cliente: manage own domains only (CRUD on own)

$action = $_GET['action'] ?? '';
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  // Create domain - financial support cannot create domains by default
  if ($user['role'] === 'Suporte Financeiro') { http_response_code(403); echo 'Acesso negado.'; exit; }
  // Validate CSRF
  require_once __DIR__ . '/inc/csrf.php'; csrf_validate();
    $domain = trim($_POST['domain'] ?? '');
    $registered_on = $_POST['registered_on'] ?: null;
    $expires_on = $_POST['expires_on'] ?: null;
    $status = $_POST['status'] ?? 'active';
    // owner selection only for Gestor
    $owner_id = ($user['role'] === 'Gestor' && !empty($_POST['user_id'])) ? intval($_POST['user_id']) : $user['id'];
    if ($domain) {
        $ins = $pdo->prepare('INSERT INTO domains (user_id,domain,registered_on,expires_on,status) VALUES (?,?,?,?,?)');
        $ins->execute([$owner_id,$domain,$registered_on,$expires_on,$status]);
        $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$user['id'],'domain_create','Domain created: '.$domain]);
        header('Location: domains.php'); exit;
    }
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare('SELECT * FROM domains WHERE id = ?'); $stmt->execute([$id]); $d = $stmt->fetch();
    if (!$d) { header('Location: domains.php'); exit; }
    // Permission: Gestor can edit all; Suporte ao Cliente / Suporte Técnico can edit; Cliente only own
    if ($user['role'] === 'Suporte Financeiro') { http_response_code(403); echo 'Acesso negado.'; exit; }
    if ($user['role'] !== 'Gestor' && $d['user_id'] != $user['id'] && !in_array($user['role'], ['Suporte ao Cliente','Suporte Técnico'])) { http_response_code(403); echo 'Acesso negado.'; exit; }
    $domain = trim($_POST['domain'] ?? '');
    $registered_on = $_POST['registered_on'] ?: null;
    $expires_on = $_POST['expires_on'] ?: null;
    $status = $_POST['status'] ?? 'active';
    $pdo->prepare('UPDATE domains SET domain=?,registered_on=?,expires_on=?,status=? WHERE id=?')->execute([$domain,$registered_on,$expires_on,$status,$id]);
    $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$user['id'],'domain_edit','Domain edited: '.$domain]);
    header('Location: domains.php'); exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate CSRF for destructive action
  require_once __DIR__ . '/inc/csrf.php'; csrf_validate();
  $id = intval($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM domains WHERE id = ?'); $stmt->execute([$id]); $d = $stmt->fetch();
    if (!$d) { header('Location: domains.php'); exit; }
    // Only Gestor or owner can delete
    if ($user['role'] !== 'Gestor' && $d['user_id'] != $user['id']) { http_response_code(403); echo 'Acesso negado.'; exit; }
    $pdo->prepare('DELETE FROM domains WHERE id = ?')->execute([$id]);
    $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$user['id'],'domain_delete','Domain deleted: '.$d['domain']]);
    header('Location: domains.php'); exit;
}

// Listing
if (in_array($user['role'], ['Gestor','Suporte ao Cliente','Suporte Técnico'])) {
    $stmt = $pdo->query('SELECT d.*, u.email AS owner_email, u.first_name, u.last_name FROM domains d JOIN users u ON d.user_id = u.id ORDER BY d.created_at DESC');
    $domains = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT d.*, u.email AS owner_email, u.first_name, u.last_name FROM domains d JOIN users u ON d.user_id = u.id WHERE d.user_id = ? ORDER BY d.created_at DESC');
    $stmt->execute([$user['id']]); $domains = $stmt->fetchAll();
}

// Fetch users for owner selection (Gestor)
$usersList = [];
if ($user['role'] === 'Gestor') {
    $usersList = $pdo->query('SELECT id,first_name,last_name,email FROM users ORDER BY id')->fetchAll();
}

require_once __DIR__ . '/inc/csrf.php';

$content = '<div class="card">
  <h2>Domínios</h2>';
if($user['role'] !== 'Suporte Financeiro'):
  $content .= '<details>
    <summary>Adicionar novo domínio</summary>
    <form method="post" action="domains.php?action=create">
      ' . csrf_input() . '';
  if($user['role'] === 'Gestor'): 
    $content .= '<div class="form-row"><label>Proprietário</label><select name="user_id">';
    foreach($usersList as $u): 
      $content .= '<option value="' . $u['id'] . '">' . htmlspecialchars($u['first_name'].' '.$u['last_name'].' ('.$u['email'].')') . '</option>';
    endforeach;
    $content .= '</select></div>';
  endif;
  $content .= '<div class="form-row"><label>Domínio</label><input type="text" name="domain" required></div>
      <div class="form-row"><label>Data Registo</label><input type="date" name="registered_on"></div>
      <div class="form-row"><label>Data Expiração</label><input type="date" name="expires_on"></div>
      <div class="form-row"><label>Status</label><select name="status"><option value="active">Active</option><option value="expired">Expired</option><option value="pending">Pending</option></select></div>
      <div class="form-row"><button class="btn">Criar Domínio</button></div>
    </form>
  </details>';
endif;
  
$content .= '<h3>Lista de Domínios</h3>';
if(empty($domains)): 
  $content .= '<p>Nenhum domínio encontrado.</p>';
else: 
  $content .= '<table style="width:100%;border-collapse:collapse"><thead><tr><th>Domínio</th><th>Proprietário</th><th>Expira</th><th>Status</th><th>Ações</th></tr></thead><tbody>';
  foreach($domains as $d): 
    $content .= '<tr style="border-top:1px solid #eee"><td>' . htmlspecialchars($d['domain']) . '</td>';
    $content .= '<td>' . htmlspecialchars($d['first_name'].' '.$d['last_name'].' ('.$d['owner_email'].')') . '</td>';
    $content .= '<td>' . $d['expires_on'] . '</td>';
    $content .= '<td>' . $d['status'] . '</td>';
    $content .= '<td>';
    if($user['role'] === 'Gestor' || $d['user_id'] == $user['id'] || in_array($user['role'], ['Suporte ao Cliente','Suporte Técnico'])): 
      $content .= '<a href="domains_edit.php?id=' . $d['id'] . '">Editar</a>';
    endif;
    if($user['role'] === 'Gestor' || $d['user_id'] == $user['id']): 
      $content .= '&nbsp;|&nbsp;
      <form method="post" action="domains.php?action=delete" style="display:inline" onsubmit="return confirm(\'Eliminar domínio?\');">
        ' . csrf_input() . '
        <input type="hidden" name="id" value="' . $d['id'] . '">
        <button class="btn" style="background:#c33">Eliminar</button>
      </form>';
    endif;
    $content .= '</td></tr>';
  endforeach;
  $content .= '</tbody></table>';
endif;
$content .= '</div>';

echo renderDashboardLayout('Domínios', 'Gestão de domínios', $content, 'domains');
?>
