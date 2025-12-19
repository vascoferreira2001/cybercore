<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';
requireRole(['Cliente','Suporte ao Cliente','Suporte Técnica','Gestor']);
$pdo = getDB();
$user = currentUser();
$userId = $user['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($subject && $message) {
        $ins = $pdo->prepare('INSERT INTO tickets (user_id,subject,message) VALUES (?,?,?)');
        $ins->execute([$userId,$subject,$message]);
        $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$userId,'ticket','Ticket created: '.$subject]);
        header('Location: support.php'); exit;
    }
}
$tickets = $pdo->prepare('SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC');
$tickets->execute([$userId]);
$tickets = $tickets->fetchAll();
?>
<?php include __DIR__ . '/inc/header.php'; ?>
<div class="card">
  <h2>Suporte - Tickets</h2>
  <form method="post">
    <?php echo csrf_input(); ?>
    <div class="form-row"><label>Assunto</label><input type="text" name="subject" required></div>
    <div class="form-row"><label>Mensagem</label><textarea name="message" rows="5" required></textarea></div>
    <div class="form-row"><button class="btn">Abrir Ticket</button></div>
  </form>
</div>
<div class="card">
  <h3>Meus Tickets</h3>
  <?php if(empty($tickets)): ?>Nenhum ticket encontrado.<?php else: ?>
    <ul>
      <?php foreach($tickets as $t): ?>
        <li><strong><?php echo htmlspecialchars($t['subject']); ?></strong> — <?php echo htmlspecialchars($t['status']); ?> <span class="small">(<?php echo $t['created_at']; ?>)</span></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
