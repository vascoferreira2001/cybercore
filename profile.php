<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/csrf.php';
requireLogin();
$cu = currentUser();
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Perfil do Utilizador - CyberCore</title>
  <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/shared/design-system.css">
  <link rel="stylesheet" href="assets/css/shared/style.css">
  <link rel="stylesheet" href="assets/css/pages/profile.css">
</head>
<body class="profile-body">
  <div class="profile-shell">
    <header class="profile-header">
      <div class="header-left">
        <div class="logo-mark" aria-hidden="true"></div>
        <div>
          <p class="eyebrow">Área de Cliente</p>
          <h1 class="page-title">Perfil do Utilizador</h1>
          <p class="page-subtitle">Gestão de dados pessoais e fiscais</p>
        </div>
      </div>
      <div class="header-right">
        <button class="btn primary" id="saveAllBtn" aria-label="Guardar todas as alterações">Guardar Tudo</button>
      </div>
    </header>

    <nav class="tabs" role="tablist" aria-label="Secções de Perfil">
      <button class="tab active" role="tab" aria-selected="true" aria-controls="panel-personal" id="tab-personal">Informação Pessoal</button>
      <button class="tab" role="tab" aria-selected="false" aria-controls="panel-fiscal" id="tab-fiscal">Informação Fiscal</button>
    </nav>

    <section class="panel" id="panel-personal" role="tabpanel" aria-labelledby="tab-personal">
      <div class="card">
        <div class="card-header">
          <h2>Informação Pessoal</h2>
          <p>Atualize o seu nome, email, telemóvel e morada.</p>
        </div>
        <form class="form-grid" id="formPersonal" novalidate>
          <div class="form-field">
            <label for="fullName">Nome completo</label>
            <input type="text" id="fullName" name="fullName" autocomplete="name" required>
            <div class="field-hint">Use o seu nome legal.</div>
            <div class="field-error" data-error-for="fullName"></div>
          </div>
          <div class="form-field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" autocomplete="email" required>
            <div class="field-hint">Este email é usado para login e notificações.</div>
            <div class="field-error" data-error-for="email"></div>
          </div>
          <div class="form-field">
            <label for="phone">Telemóvel (opcional)</label>
            <input type="tel" id="phone" name="phone" autocomplete="tel" placeholder="+351 912 345 678">
            <div class="field-hint">Inclua o indicativo do país (ex.: +351).</div>
            <div class="field-error" data-error-for="phone"></div>
          </div>
          <div class="form-field wide">
            <label for="address">Morada (opcional)</label>
            <input type="text" id="address" name="address" autocomplete="address-line1" placeholder="Rua, nº, andar">
            <div class="field-error" data-error-for="address"></div>
          </div>
          <div class="form-field">
            <label for="city">Cidade (opcional)</label>
            <input type="text" id="city" name="city" autocomplete="address-level2">
            <div class="field-error" data-error-for="city"></div>
          </div>
          <div class="form-field">
            <label for="postalCode">Código Postal</label>
            <input type="text" id="postalCode" name="postalCode" inputmode="numeric" placeholder="0000-000" required>
            <div class="field-hint">Formato PT: NNNN-NNN</div>
            <div class="field-error" data-error-for="postalCode"></div>
          </div>
          <div class="form-field">
            <label for="country">País (opcional)</label>
            <select id="country" name="country">
              <option value="">— Selecionar —</option>
              <option value="PT">Portugal</option>
              <option value="ES">Espanha</option>
              <option value="FR">França</option>
              <option value="DE">Alemanha</option>
              <option value="GB">Reino Unido</option>
            </select>
          </div>
          <div class="form-actions">
            <button class="btn secondary" type="reset">Repor</button>
            <button class="btn primary" type="submit">Guardar Alterações</button>
          </div>
        </form>
      </div>
    </section>

    <section class="panel hidden" id="panel-fiscal" role="tabpanel" aria-labelledby="tab-fiscal">
      <div class="card">
        <div class="card-header">
          <h2>Informação Fiscal</h2>
          <p>Dados fiscais bloqueados após configuração inicial.</p>
          <div class="info-banner" role="note">
            <strong>Importante:</strong> Para alterar dados fiscais, contacte o suporte.
            <span class="info-detail">(Tipo de entidade, NIF e Nome da empresa não são editáveis pelo utilizador.)</span>
          </div>
        </div>
        <form class="form-grid" id="formFiscal" novalidate>
          <div class="form-field">
            <label for="entityType">Tipo de entidade</label>
            <input type="text" id="entityType" name="entityType" readonly aria-readonly="true">
            <div class="field-lock-note">Campo bloqueado</div>
          </div>
          <div class="form-field">
            <label for="companyName">Nome da empresa</label>
            <input type="text" id="companyName" name="companyName" readonly aria-readonly="true">
            <div class="field-lock-note">Campo bloqueado</div>
          </div>
          <div class="form-field">
            <label for="taxId">NIF</label>
            <input type="text" id="taxId" name="taxId" readonly aria-readonly="true">
            <div class="field-lock-note">Campo bloqueado</div>
          </div>
          <div class="form-actions">
            <button class="btn danger" type="button" id="requestFiscalChangeBtn">Solicitar alteração de dados fiscais</button>
          </div>
        </form>
      </div>
    </section>

    <footer class="profile-footer" role="contentinfo">
      <p>CyberCore © 2025 • Segurança e performance primeiro</p>
    </footer>
  </div>

  <script src="assets/js/pages/profile.js"></script>
</body>
</html>
