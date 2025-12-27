<?php
/**
 * CyberCore - Área de Cliente
 * Dashboard principal do cliente
 */

// Esta é a área de cliente - cybercore.pt/manager/
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
requireLogin();

$user = currentUser();

include __DIR__ . '/../inc/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../inc/sidebar.php'; ?>
  
  <main class="dashboard-main">
    <div class="dashboard-header">
      <h1>Bem-vindo, <?php echo htmlspecialchars($user['name'] ?? 'Cliente'); ?>!</h1>
      <p class="muted">Gerir os seus serviços e domínios</p>
    </div>

    <div class="dashboard-stats">
      <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
          <div class="stat-value">5</div>
          <div class="stat-label">Serviços Ativos</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">🌐</div>
        <div class="stat-content">
          <div class="stat-value">12</div>
          <div class="stat-label">Domínios</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">📧</div>
        <div class="stat-content">
          <div class="stat-value">3</div>
          <div class="stat-label">Tickets Abertos</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">💳</div>
        <div class="stat-content">
          <div class="stat-value">€127.45</div>
          <div class="stat-label">Saldo Pendente</div>
        </div>
      </div>
    </div>

    <div class="dashboard-grid">
      <div class="dashboard-card">
        <div class="card-header">
          <h3>Serviços Recentes</h3>
          <a href="/manager/hosting.php" class="btn small ghost">Ver Todos</a>
        </div>
        <div class="services-list">
          <div class="service-item">
            <div class="service-icon">🌐</div>
            <div class="service-info">
              <strong>Hosting Business</strong>
              <span class="muted">empresa.pt</span>
            </div>
            <span class="badge success">Ativo</span>
          </div>
          
          <div class="service-item">
            <div class="service-icon">📧</div>
            <div class="service-info">
              <strong>Email Professional</strong>
              <span class="muted">10 caixas</span>
            </div>
            <span class="badge success">Ativo</span>
          </div>
          
          <div class="service-item">
            <div class="service-icon">🖥️</div>
            <div class="service-info">
              <strong>VPS Pro</strong>
              <span class="muted">4 vCPU / 8GB RAM</span>
            </div>
            <span class="badge success">Ativo</span>
          </div>
        </div>
      </div>

      <div class="dashboard-card">
        <div class="card-header">
          <h3>Domínios a Expirar</h3>
          <a href="/manager/domains.php" class="btn small ghost">Gerir</a>
        </div>
        <div class="domains-list">
          <div class="domain-item">
            <div class="domain-name">empresa.pt</div>
            <div class="domain-expiry warning">Expira em 45 dias</div>
            <a href="/manager/domains.php?renew=1" class="btn tiny primary">Renovar</a>
          </div>
          
          <div class="domain-item">
            <div class="domain-name">minhaloja.com</div>
            <div class="domain-expiry success">Expira em 8 meses</div>
          </div>
        </div>
      </div>

      <div class="dashboard-card">
        <div class="card-header">
          <h3>Tickets de Suporte</h3>
          <a href="/manager/support.php" class="btn small ghost">Ver Todos</a>
        </div>
        <div class="tickets-list">
          <div class="ticket-item">
            <div class="ticket-info">
              <strong>#1234 - Configuração SSL</strong>
              <span class="muted">Há 2 horas</span>
            </div>
            <span class="badge info">Aberto</span>
          </div>
          
          <div class="ticket-item">
            <div class="ticket-info">
              <strong>#1233 - Aumento de Storage</strong>
              <span class="muted">Há 1 dia</span>
            </div>
            <span class="badge success">Respondido</span>
          </div>
        </div>
        
        <a href="/manager/support.php?new=1" class="btn primary full-width" style="margin-top: 16px;">
          Novo Ticket
        </a>
      </div>

      <div class="dashboard-card">
        <div class="card-header">
          <h3>Faturas Pendentes</h3>
          <a href="/manager/finance.php" class="btn small ghost">Ver Todas</a>
        </div>
        <div class="invoices-list">
          <div class="invoice-item">
            <div class="invoice-info">
              <strong>Fatura #2024-0156</strong>
              <span class="muted">Vencimento: 31/12/2024</span>
            </div>
            <div class="invoice-amount">€49.99</div>
            <a href="/manager/finance.php?pay=156" class="btn tiny primary">Pagar</a>
          </div>
          
          <div class="invoice-item">
            <div class="invoice-info">
              <strong>Fatura #2024-0157</strong>
              <span class="muted">Vencimento: 15/01/2025</span>
            </div>
            <div class="invoice-amount">€77.46</div>
            <a href="/manager/finance.php?pay=157" class="btn tiny primary">Pagar</a>
          </div>
        </div>
      </div>
    </div>

    <div class="quick-actions">
      <h3>Ações Rápidas</h3>
      <div class="actions-grid">
        <a href="/manager/hosting.php?new=1" class="action-card">
          <div class="action-icon">➕</div>
          <strong>Novo Serviço</strong>
          <span class="muted">Contratar hosting, VPS, email</span>
        </a>
        
        <a href="/manager/domains.php?search=1" class="action-card">
          <div class="action-icon">🔍</div>
          <strong>Registar Domínio</strong>
          <span class="muted">Procurar e registar novo domínio</span>
        </a>
        
        <a href="/manager/support.php?new=1" class="action-card">
          <div class="action-icon">💬</div>
          <strong>Abrir Ticket</strong>
          <span class="muted">Suporte técnico 24/7</span>
        </a>
        
        <a href="/manager/profile.php" class="action-card">
          <div class="action-icon">⚙️</div>
          <strong>Definições</strong>
          <span class="muted">Gerir conta e perfil</span>
        </a>
      </div>
    </div>
  </main>
</div>

<link rel="stylesheet" href="/assets/css/dashboard.css">

<?php include __DIR__ . '/../inc/footer.php'; ?>
