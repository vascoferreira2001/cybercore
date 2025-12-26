<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';

checkRole(['Cliente','Suporte ao Cliente','Suporte Financeiro','Suporte Técnica','Gestor']);
$cu = currentUser();
$pdo = getDB();
$profileUrl = '/profile.php';
$clientId = 'CYC#' . str_pad($cu['id'], 5, '0', STR_PAD_LEFT);
$userDisplayName = trim($cu['first_name'] . ' ' . $cu['last_name']) ?: $cu['email'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
  <title>Perfil - CyberCore</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/pages/dashboard-modern.css">
  <link rel="stylesheet" href="/assets/css/pages/profile.css">
</head>
<body>

  <!-- ========== SIDEBAR ========== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
          <rect width="32" height="32" rx="8" fill="url(#gradient1)"/>
          <path d="M16 8L22 12V20L16 24L10 20V12L16 8Z" stroke="white" stroke-width="2" fill="none"/>
          <defs>
            <linearGradient id="gradient1" x1="0" y1="0" x2="32" y2="32">
              <stop offset="0%" stop-color="#007dff"/>
              <stop offset="100%" stop-color="#0052cc"/>
            </linearGradient>
          </defs>
        </svg>
        <span class="logo-text">CyberCore</span>
      </div>
      <button class="sidebar-toggle" id="sidebarToggle">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
    </div>

    <nav class="sidebar-nav">
      <a href="/dashboard.php" class="nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="7" height="7"></rect>
          <rect x="14" y="3" width="7" height="7"></rect>
          <rect x="14" y="14" width="7" height="7"></rect>
          <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span>Dashboard</span>
      </a>

      <a href="/services.php" class="nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        </svg>
        <span>Serviços</span>
      </a>

      <a href="/finance.php" class="nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="2" y="5" width="20" height="14" rx="2"></rect>
          <line x1="2" y1="10" x2="22" y2="10"></line>
        </svg>
        <span>Faturação</span>
      </a>

      <a href="/support.php" class="nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        <span>Suporte</span>
      </a>

      <a href="/domains.php" class="nav-item">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="2" y1="12" x2="22" y2="12"></line>
          <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
        </svg>
        <span>Domínios</span>
      </a>
    </nav>

    <div class="sidebar-footer">
      <a href="/logout.php" class="nav-item logout-btn">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
          <polyline points="16 17 21 12 16 7"></polyline>
          <line x1="21" y1="12" x2="9" y2="12"></line>
        </svg>
        <span>Sair</span>
      </a>
    </div>
  </aside>

  <!-- ========== MAIN CONTENT ========== -->
  <div class="main-wrapper">
    <!-- Top Navigation Bar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        <div class="search-box">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
          <input type="text" placeholder="Pesquisar...">
        </div>
      </div>

      <div class="topbar-right">
        <a class="user-menu" href="<?php echo htmlspecialchars($profileUrl); ?>" aria-label="Abrir perfil do utilizador">
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($userDisplayName); ?></span>
            <span class="user-id"><?php echo htmlspecialchars($clientId); ?></span>
          </div>
          <div class="user-avatar" title="<?php echo htmlspecialchars($cu['email']); ?>">
            <?php echo strtoupper(substr($cu['first_name'], 0, 1)); ?>
          </div>
        </a>
      </div>
    </header>

    <!-- Profile Content -->
    <main class="dashboard-content">
      <div class="dashboard-header">
        <div>
          <h1 class="page-title">Perfil</h1>
          <p class="page-subtitle">Gestão de dados pessoais e fiscais</p>
        </div>
        <button class="btn primary" id="saveAllBtn" aria-label="Guardar todas as alterações">Guardar Tudo</button>
      </div>

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
    </main>

    <!-- Footer -->
    <footer class="dashboard-footer" role="contentinfo">
      <p>CyberCore © 2025 • Segurança e performance primeiro</p>
    </footer>
  </div>

  <script src="/assets/js/pages/profile.js"></script>
</body>
</html>
