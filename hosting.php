<?php
require_once __DIR__ . '/inc/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
?>
<?php include __DIR__ . '/inc/header.php'; ?>
<div class="card">
  <h2>Alojamento</h2>
  <p>Página placeholder para planos de alojamento e gestão.</p>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
