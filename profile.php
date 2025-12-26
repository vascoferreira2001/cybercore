<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/fiscal_requests.php';

requireLogin();
checkRole(['Cliente','Suporte ao Cliente','Suporte Financeiro','Suporte Técnico','Gestor']);
$cu = currentUser();
$pdo = getDB();
$profileUrl = '/profile.php';

// Carregar dados completos do utilizador (incluindo dados fiscais)
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$cu['id']]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// Carregar histórico de solicitações fiscais (se cliente)
$fiscalRequests = [];
if ($cu['role'] === 'Cliente') {
    $fiscalRequests = getUserFiscalRequests($pdo, $cu['id']);
}
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
              <input type="text" id="fullName" name="fullName" autocomplete="name" value="<?php echo htmlspecialchars(trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''))); ?>" required>
              <div class="field-hint">Use o seu nome legal.</div>
              <div class="field-error" data-error-for="fullName"></div>
            </div>
            <div class="form-field">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" autocomplete="email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
              <div class="field-hint">Este email é usado para login e notificações.</div>
              <div class="field-error" data-error-for="email"></div>
            </div>
            <div class="form-field">
              <label for="phone">Telemóvel (opcional)</label>
              <input type="tel" id="phone" name="phone" autocomplete="tel" placeholder="+351 912 345 678" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
              <div class="field-hint">Inclua o indicativo do país (ex.: +351).</div>
              <div class="field-error" data-error-for="phone"></div>
            </div>
            <div class="form-field wide">
              <label for="address">Morada (opcional)</label>
              <input type="text" id="address" name="address" autocomplete="address-line1" placeholder="Rua, nº, andar" value="<?php echo htmlspecialchars($userData['address'] ?? ''); ?>">
              <div class="field-error" data-error-for="address"></div>
            </div>
            <div class="form-field">
              <label for="city">Cidade (opcional)</label>
              <input type="text" id="city" name="city" autocomplete="address-level2" value="<?php echo htmlspecialchars($userData['city'] ?? ''); ?>">
              <div class="field-error" data-error-for="city"></div>
            </div>
            <div class="form-field">
              <label for="postalCode">Código Postal</label>
              <input type="text" id="postalCode" name="postalCode" inputmode="numeric" placeholder="0000-000" value="<?php echo htmlspecialchars($userData['postal_code'] ?? ''); ?>" required>
              <div class="field-hint">Formato PT: NNNN-NNN</div>
              <div class="field-error" data-error-for="postalCode"></div>
            </div>
            <div class="form-field">
              <label for="country">País (opcional)</label>
              <select id="country" name="country">
                <option value="">— Selecionar —</option>
                <option value="PT" <?php echo ($userData['country'] ?? '') === 'PT' ? 'selected' : ''; ?>>Portugal</option>
                <option value="ES" <?php echo ($userData['country'] ?? '') === 'ES' ? 'selected' : ''; ?>>Espanha</option>
                <option value="FR" <?php echo ($userData['country'] ?? '') === 'FR' ? 'selected' : ''; ?>>França</option>
                <option value="DE" <?php echo ($userData['country'] ?? '') === 'DE' ? 'selected' : ''; ?>>Alemanha</option>
                <option value="GB" <?php echo ($userData['country'] ?? '') === 'GB' ? 'selected' : ''; ?>>Reino Unido</option>
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
            <?php if (in_array($cu['role'], ['Gestor', 'Suporte Financeiro'])): ?>
              <p>Dados fiscais editáveis (Gestor/Suporte Financeiro).</p>
            <?php else: ?>
              <p>Dados fiscais bloqueados após configuração inicial.</p>
              <div class="info-banner" role="note">
                <strong>Importante:</strong> Para alterar dados fiscais, contacte o suporte.
                <span class="info-detail">(Tipo de entidade, NIF e Nome da empresa não são editáveis pelo utilizador.)</span>
              </div>
            <?php endif; ?>
          </div>
          <form class="form-grid" id="formFiscal" novalidate>
            <div class="form-field">
              <label for="entityType">Tipo de entidade</label>
              <?php if (in_array($cu['role'], ['Gestor', 'Suporte Financeiro'])): ?>
                <select id="entityType" name="entityType" required>
                  <option value="Singular" <?php echo ($userData['entity_type'] ?? '') === 'Singular' ? 'selected' : ''; ?>>Pessoa Singular</option>
                  <option value="Coletiva" <?php echo ($userData['entity_type'] ?? '') === 'Coletiva' ? 'selected' : ''; ?>>Pessoa Coletiva</option>
                </select>
              <?php else: ?>
                <input type="text" id="entityType" name="entityType" readonly aria-readonly="true" value="<?php echo htmlspecialchars($userData['entity_type'] === 'Singular' ? 'Pessoa Singular' : 'Pessoa Coletiva'); ?>">
                <div class="field-lock-note">Campo bloqueado</div>
              <?php endif; ?>
            </div>
            <div class="form-field">
              <label for="companyName">Nome da empresa</label>
              <?php if (in_array($cu['role'], ['Gestor', 'Suporte Financeiro'])): ?>
                <input type="text" id="companyName" name="companyName" value="<?php echo htmlspecialchars($userData['company_name'] ?? ''); ?>">
              <?php else: ?>
                <input type="text" id="companyName" name="companyName" readonly aria-readonly="true" value="<?php echo htmlspecialchars($userData['company_name'] ?? '—'); ?>">
                <div class="field-lock-note">Campo bloqueado</div>
              <?php endif; ?>
            </div>
            <div class="form-field">
              <label for="taxId">NIF</label>
              <?php if (in_array($cu['role'], ['Gestor', 'Suporte Financeiro'])): ?>
                <input type="text" id="taxId" name="taxId" value="<?php echo htmlspecialchars($userData['nif'] ?? ''); ?>" required>
                <div class="field-hint">NIF português de 9 dígitos</div>
              <?php else: ?>
                <input type="text" id="taxId" name="taxId" readonly aria-readonly="true" value="<?php echo htmlspecialchars($userData['nif']); ?>">
                <div class="field-lock-note">Campo bloqueado</div>
              <?php endif; ?>
            </div>
            
            <?php if (in_array($cu['role'], ['Gestor', 'Suporte Financeiro'])): ?>
              <div class="form-actions">
                <button class="btn secondary" type="reset">Repor</button>
                <button class="btn primary" type="submit">Guardar Dados Fiscais</button>
              </div>
            <?php elseif ($cu['role'] === 'Cliente'): ?>
              <div class="form-field wide">
                <div class="info-banner" style="background:#e3f2fd;border:1px solid #90caf9;padding:12px;border-radius:6px;margin:12px 0">
                  <strong>ℹ️ Dados Fiscais Protegidos</strong><br>
                  <span style="font-size:13px;color:#555">Para alterar NIF, tipo de entidade ou nome da empresa, por favor submeta uma solicitação abaixo. Será revogada por um gestor em breve.</span>
                </div>
              </div>
              <div class="form-actions">
                <button class="btn danger" type="button" id="requestFiscalChangeBtn">Solicitar alteração de dados fiscais</button>
              </div>
              
              <?php if (!empty($fiscalRequests)): ?>
                <div class="form-field wide">
                  <h3 style="margin:20px 0 12px 0;font-size:15px">Histórico de Solicitações</h3>
                  <div style="max-height:300px;overflow-y:auto">
                    <?php foreach ($fiscalRequests as $req): ?>
                      <div style="padding:12px;border:1px solid #ddd;border-radius:6px;margin-bottom:8px;background:#f9f9f9">
                        <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                          <strong>NIF: <?php echo htmlspecialchars($req['new_nif']); ?></strong>
                          <span class="badge badge-<?php echo $req['status']; ?>" style="font-size:11px;padding:4px 8px;border-radius:4px;background:<?php 
                            echo $req['status'] === 'approved' ? '#4caf50' : ($req['status'] === 'rejected' ? '#f44336' : '#ff9800');
                          ?>;color:#fff">
                            <?php echo ucfirst($req['status']); ?>
                          </span>
                        </div>
                        <div style="font-size:12px;color:#666">
                          Solicitado: <?php echo date('d/m/Y H:i', strtotime($req['requested_at'])); ?><br>
                          Motivo: <?php echo htmlspecialchars(substr($req['reason'], 0, 100)); ?>...
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            <?php elseif (in_array($cu['role'], ['Gestor', 'Suporte Financeiro'])): ?>
              <div class="form-actions">
                <a href="/admin/fiscal-approvals.php" class="btn primary">Ver solicitações pendentes</a>
              </div>
            <?php endif; ?>
            
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="dashboard-footer" role="contentinfo">
      <p>CyberCore © 2025 • Segurança e performance primeiro</p>
    </footer>
  </div>

  <!-- Modal: Solicitar Alteração Fiscal -->
  <div id="fiscalChangeModal" class="modal" style="display:none;z-index:1000">
    <div class="modal-overlay" id="modalOverlay"></div>
    <div class="modal-content" style="max-width:500px">
      <div class="modal-header">
        <h2>Solicitar Alteração de Dados Fiscais</h2>
        <button class="modal-close" type="button" id="closeFiscalModal" aria-label="Fechar">&times;</button>
      </div>
      <form id="fiscalRequestForm" novalidate>
        <?php echo csrf_input(); ?>
        <input type="hidden" name="action" value="submit">
        
        <div class="form-field">
          <label for="newNIF">Novo NIF *</label>
          <input type="text" id="newNIF" name="newNIF" placeholder="Exemplo: 123456789" required>
          <div class="field-hint">Será validado automaticamente</div>
          <div class="field-error" data-error-for="newNIF"></div>
        </div>
        
        <div class="form-field">
          <label for="newEntityType">Tipo de Entidade *</label>
          <select id="newEntityType" name="newEntityType" required>
            <option value="">— Selecionar —</option>
            <option value="Singular">Pessoa Singular</option>
            <option value="Coletiva">Pessoa Coletiva</option>
          </select>
          <div class="field-error" data-error-for="newEntityType"></div>
        </div>
        
        <div class="form-field">
          <label for="newCompanyName">Nome da Empresa (se aplicável)</label>
          <input type="text" id="newCompanyName" name="newCompanyName" placeholder="Opcional">
          <div class="field-hint">Obrigatório se tipo for Pessoa Coletiva</div>
          <div class="field-error" data-error-for="newCompanyName"></div>
        </div>
        
        <div class="form-field">
          <label for="reason">Motivo da Alteração *</label>
          <textarea id="reason" name="reason" rows="4" placeholder="Explique detalhadamente o motivo desta alteração..." required></textarea>
          <div class="field-hint">Mínimo 10 caracteres</div>
          <div class="field-error" data-error-for="reason"></div>
        </div>
        
        <div class="form-actions" style="gap:10px">
          <button type="button" class="btn secondary" id="cancelFiscalBtn">Cancelar</button>
          <button type="submit" class="btn primary">Enviar Solicitação</button>
        </div>
      </form>
    </div>
  </div>

  <!-- CSS para Modal -->
  <style>
    .modal {
      position:fixed;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;z-index:1000
    }
    .modal-overlay {
      position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);cursor:pointer
    }
    .modal-content {
      position:relative;background:#fff;border-radius:12px;padding:24px;box-shadow:0 10px 40px rgba(0,0,0,0.2);max-width:600px;width:90%;max-height:90vh;overflow-y:auto
    }
    .modal-header {
      display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid #eee;padding-bottom:12px
    }
    .modal-header h2 {
      margin:0;font-size:18px;font-weight:600
    }
    .modal-close {
      background:none;border:none;font-size:28px;color:#999;cursor:pointer;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center
    }
    .modal-close:hover {
      color:#333
    }
  </style>

  <!-- JavaScript para Modal e Submissão -->
  <script>
    (function() {
      const modal = document.getElementById('fiscalChangeModal');
      const modalOverlay = document.getElementById('modalOverlay');
      const openBtn = document.getElementById('requestFiscalChangeBtn');
      const closeBtn = document.getElementById('closeFiscalModal');
      const cancelBtn = document.getElementById('cancelFiscalBtn');
      const form = document.getElementById('fiscalRequestForm');

      if (!openBtn) return;

      // Abrir modal
      openBtn.addEventListener('click', () => {
        modal.style.display = 'flex';
      });

      // Fechar modal
      const closeModal = () => {
        modal.style.display = 'none';
        form.reset();
        clearErrors();
      };

      closeBtn?.addEventListener('click', closeModal);
      cancelBtn?.addEventListener('click', closeModal);
      modalOverlay?.addEventListener('click', closeModal);

      // Limpar erros
      const clearErrors = () => {
        document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
      };

      // Submeter formulário
      form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors();

        const formData = new FormData(form);
        formData.append('action', 'submit');

        try {
          const res = await fetch('/inc/api/fiscal-requests.php', {
            method: 'POST',
            body: formData
          });

          const data = await res.json();

          if (data.success) {
            showToast(data.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1500);
          } else {
            showToast(data.message || 'Erro ao enviar solicitação', 'error');
          }
        } catch (err) {
          console.error(err);
          showToast('Erro ao enviar solicitação', 'error');
        }
      });

      // Toast simples
      function showToast(msg, type = 'info') {
        const toast = document.createElement('div');
        toast.style.cssText = `
          position:fixed;bottom:20px;right:20px;padding:12px 20px;border-radius:6px;
          background:${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
          color:#fff;font-size:14px;z-index:2000;box-shadow:0 4px 12px rgba(0,0,0,0.15)
        `;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
      }
    })();
  </script>

