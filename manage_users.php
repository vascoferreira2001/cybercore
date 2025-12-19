<?php
$require_csrf = false;
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
requireLogin();
requireRole('Gestor');
$pdo = getDB();
$me = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
  $uid = intval($_POST['user_id'] ?? 0);
  $newRole = $_POST['role'] ?? '';
    $allowed = ['Cliente','Suporte','Contabilista','Gestor'];
    if ($uid <= 0 || !in_array($newRole, $allowed)) {
        $error = 'Dados inválidos.';
    } else {
        // Prevent Gestor from removing their own Gestor role
        if ($uid == $me['id'] && $newRole !== 'Gestor') {
            $error = 'Não pode remover o seu próprio papel Gestor.';
        } else {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $uid]);
            $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$me['id'],'role_change','Changed role of user '.$uid.' to '.$newRole]);
            header('Location: manage_users.php'); exit;
        }
    }
}

$users = $pdo->query('SELECT id,first_name,last_name,email,role,created_at FROM users ORDER BY id')->fetchAll();
?>
<?php include __DIR__ . '/inc/header.php'; ?>
<div class="card">
  <h2>Gestão de Utilizadores</h2>
  <?php if(!empty($error)): ?><div class="card" style="background:#ffefef;color:#900"><?php echo $error; ?></div><?php endif; ?>
  <table style="width:100%;border-collapse:collapse"><thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Role</th><th>Criar</th></tr></thead><tbody>
    <?php foreach($users as $u): ?>
      <tr style="border-top:1px solid #eee"><td><?php echo $u['id']; ?></td>
      <td><?php echo htmlspecialchars($u['first_name'].' '.$u['last_name']); ?></td>
      <td><?php echo htmlspecialchars($u['email']); ?></td>
      <td><?php echo htmlspecialchars($u['role']); ?></td>
      <td>
        <form method="post" style="display:inline">
          <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
          <select name="role">
            <option <?php echo $u['role']==='Cliente'?'selected':''; ?>>Cliente</option>
            <option <?php echo $u['role']==='Suporte'?'selected':''; ?>>Suporte</option>
            <option <?php echo $u['role']==='Contabilista'?'selected':''; ?>>Contabilista</option>
            <option <?php echo $u['role']==='Gestor'?'selected':''; ?>>Gestor</option>
          </select>
          <button class="btn">Guardar</button>
        </form>
      </td></tr>
    <?php endforeach; ?>
  </tbody></table>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>
