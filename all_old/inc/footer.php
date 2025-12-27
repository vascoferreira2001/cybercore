<?php if (defined('DASHBOARD_LAYOUT') && DASHBOARD_LAYOUT === true): ?>
  </main>
</div>
<?php else: ?>
  <footer class="site-footer">
    <div class="container">
      <div class="footer-inner">
        <div>
          <strong>CyberCore</strong><br>
          <span class="muted">Infraestrutura para sites e aplicações</span>
        </div>
        <div class="footer-links">
          <a href="/hosting.php">Alojamento</a>
          <a href="/servers.php">Servidores</a>
          <a href="/domains.php">Domínios</a>
          <a href="/pricing.php">Preços</a>
          <a href="/support.php">Ajuda</a>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© <?php echo date('Y'); ?> CyberCore</span>
        <span><a href="/privacy.php">Privacidade</a> · <a href="/terms.php">Termos</a></span>
      </div>
    </div>
  </footer>
<?php endif; ?>
<script src="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>assets/js/app.js"></script>
<?php if (!defined('DASHBOARD_LAYOUT') || DASHBOARD_LAYOUT !== true): ?>
<script src="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>assets/js/website.js"></script>
<?php endif; ?>

<?php
// Footer - versão simplificada
$version = '1.0.0';
$updateLink = '/updates.php';
?>
<footer style="background:#1a1a1a;color:#ccc;padding:20px;margin-top:40px;text-align:center;font-size:12px">
  <div style="max-width:1200px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:1;text-align:left;min-width:60px">v<?php echo htmlspecialchars($version); ?></div>
    <div style="flex:2;min-width:200px">©️ 2020 - <?php echo date('Y'); ?> - CyberCore é uma marca da Monteiro &amp; Ferreira - Informática e Serviços, Lda</div>
    <div style="flex:1;text-align:right;min-width:60px"><a href="<?php echo htmlspecialchars($updateLink); ?>" style="color:#4CAF50;text-decoration:none;padding:6px 12px;border:1px solid #4CAF50;border-radius:4px;display:inline-block">⬆️ Updates</a></div>
  </div>
</footer>
</body>
</html>
