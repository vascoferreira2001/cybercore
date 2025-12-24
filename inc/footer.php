  </main>
</div>
<script src="js/app.js"></script>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';
$pdo = getDB();
$version = getSetting($pdo, 'app_version', '1.0.0');
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
  $updateLink = 'updates.php';
} else {
  $updateLink = 'admin/updates.php';
}
?>
<footer style="background:#1a1a1a;color:#ccc;padding:20px;margin-top:40px;text-align:center;font-size:12px">
  <div style="max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:20px">
    <div style="flex:1;text-align:left">v<?php echo htmlspecialchars($version); ?></div>
    <div style="flex:2">©️ 2020 - <?php echo date('Y'); ?> - CyberCore é uma marca da Monteiro &amp; Ferreira - Informática e Serviços, Lda</div>
    <div style="flex:1;text-align:right"><a href="<?php echo $updateLink; ?>" style="color:#4CAF50;text-decoration:none;padding:6px 12px;border:1px solid #4CAF50;border-radius:4px;display:inline-block">⬆️ Updates</a></div>
  </div>
</footer>
</body>
</html>
