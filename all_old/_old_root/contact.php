<?php include __DIR__ . '/includes/header.php'; ?>
<section class="page-header">
  <div class="container">
    <h1>Contacto</h1>
    <p class="muted">Conte-nos sobre o seu projeto — respondemos rapidamente.</p>
  </div>
</section>
<section class="container">
  <form class="contact-form" method="post" action="/contact_submit.php" novalidate>
    <input type="text" name="website" value="" style="display:none" aria-hidden="true" tabindex="-1">
    <div class="form-row">
      <div class="form-field">
        <label>Nome *</label>
        <input type="text" name="name" required>
      </div>
      <div class="form-field">
        <label>Email *</label>
        <input type="email" name="email" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-field">
        <label>Assunto *</label>
        <input type="text" name="subject" required>
      </div>
      <div class="form-field">
        <label>Telefone</label>
        <input type="text" name="phone">
      </div>
    </div>
    <div class="form-field">
      <label>Mensagem *</label>
      <textarea name="message" rows="6" required></textarea>
    </div>
    <div class="form-actions">
      <button class="btn primary" type="submit">Enviar</button>
      <a class="btn outline" href="/">Voltar</a>
    </div>
  </form>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
