<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';
require_once __DIR__ . '/../inc/permissions.php';
require_once __DIR__ . '/../inc/fiscal_requests.php';
require_once __DIR__ . '/../inc/csrf.php';

checkRole(['Gestor', 'Suporte Financeiro']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

requirePermission('can_manage_fiscal_approvals', $user);

$pdo = getDB();

// Obter solicitações pendentes
$pending = getPendingFiscalRequests($pdo);

ob_start();
?>

<div class="card">
  <h2>Aprovações de Dados Fiscais</h2>
  <p style="color:#666;margin-bottom:20px">Gerir solicitações de alteração de dados fiscais de clientes</p>

  <?php if (empty($pending)): ?>
    <div style="padding:20px;background:#f5f5f5;border-radius:6px;text-align:center;color:#999">
      <p>✓ Não há solicitações pendentes no momento</p>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto">
      <table class="table" style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:#f9f9f9;border-bottom:2px solid #ddd">
            <th style="padding:12px;text-align:left;font-weight:600">Cliente</th>
            <th style="padding:12px;text-align:left;font-weight:600">Dados Atuais</th>
            <th style="padding:12px;text-align:left;font-weight:600">Dados Solicitados</th>
            <th style="padding:12px;text-align:left;font-weight:600">Motivo</th>
            <th style="padding:12px;text-align:left;font-weight:600">Data</th>
            <th style="padding:12px;text-align:center;font-weight:600">Ações</th>
          </tr>
        </thead>
        <tbody id="requestsBody">
          <?php foreach ($pending as $request): ?>
            <tr class="request-row">
              <td style="padding:12px">
                <strong><?php echo htmlspecialchars(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?></strong><br>
                <small style="color:#999"><?php echo htmlspecialchars($request['email'] ?? ''); ?></small>
              </td>
              <td style="padding:12px;font-size:13px">
                <strong>NIF:</strong> <?php echo htmlspecialchars($request['old_nif']); ?><br>
                <strong>Tipo:</strong> <?php echo htmlspecialchars($request['old_entity_type']); ?><br>
                <?php if ($request['old_company_name']): ?>
                  <strong>Empresa:</strong> <?php echo htmlspecialchars($request['old_company_name']); ?>
                <?php endif; ?>
              </td>
              <td style="padding:12px;font-size:13px;background:#fffacd;border-radius:4px">
                <strong>NIF:</strong> <?php echo htmlspecialchars($request['new_nif']); ?><br>
                <strong>Tipo:</strong> <?php echo htmlspecialchars($request['new_entity_type']); ?><br>
                <?php if ($request['new_company_name']): ?>
                  <strong>Empresa:</strong> <?php echo htmlspecialchars($request['new_company_name']); ?>
                <?php endif; ?>
              </td>
              <td style="padding:12px;font-size:13px;color:#555;max-width:200px">
                <?php echo htmlspecialchars(substr($request['reason'], 0, 100)); ?>
                <?php if (strlen($request['reason']) > 100): ?>...<?php endif; ?>
              </td>
              <td style="padding:12px;font-size:13px;color:#999">
                <?php echo date('d/m/Y H:i', strtotime($request['requested_at'])); ?>
              </td>
              <td style="padding:12px;text-align:center">
                <button 
                  class="btn btn-sm primary" 
                  data-request-id="<?php echo $request['id']; ?>" 
                  onclick="approveRequest(this)">
                  Aprovar
                </button>
                <button 
                  class="btn btn-sm danger" 
                  data-request-id="<?php echo $request['id']; ?>" 
                  onclick="rejectRequest(this)">
                  Rejeitar
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Modal para rejeição com motivo -->
<div id="rejectModal" class="modal" style="display:none;z-index:1000">
  <div class="modal-overlay" id="rejectOverlay"></div>
  <div class="modal-content" style="max-width:500px">
    <div class="modal-header">
      <h2>Rejeitar Solicitação</h2>
      <button class="modal-close" type="button" id="closeRejectModal" aria-label="Fechar">&times;</button>
    </div>
    <form id="rejectForm" novalidate>
      <input type="hidden" id="rejectRequestId" name="requestId">
      
      <div class="form-field">
        <label for="rejectReason">Motivo da Rejeição *</label>
        <textarea 
          id="rejectReason" 
          name="reason" 
          rows="4" 
          placeholder="Explique o motivo da rejeição..." 
          required
          style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-family:inherit"></textarea>
        <div class="field-hint">Mínimo 10 caracteres</div>
      </div>
      
      <div class="form-actions" style="gap:10px">
        <button type="button" class="btn secondary" id="cancelRejectBtn">Cancelar</button>
        <button type="submit" class="btn danger">Rejeitar</button>
      </div>
    </form>
  </div>
</div>

<style>
  .request-row {
    border-bottom:1px solid #eee
  }
  .request-row:hover {
    background:#f9f9f9
  }
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
  .btn-sm {
    padding:6px 12px;font-size:12px
  }
  .form-actions {
    display:flex;gap:10px;margin-top:20px
  }
  .form-actions .btn {
    flex:1
  }
</style>

<script>
  const rejectModal = document.getElementById('rejectModal');
  const rejectOverlay = document.getElementById('rejectOverlay');
  const rejectForm = document.getElementById('rejectForm');
  const closeRejectBtn = document.getElementById('closeRejectModal');
  const cancelRejectBtn = document.getElementById('cancelRejectBtn');
  const rejectRequestIdInput = document.getElementById('rejectRequestId');

  // Abrir modal de rejeição
  window.rejectRequest = function(btn) {
    const requestId = btn.getAttribute('data-request-id');
    rejectRequestIdInput.value = requestId;
    rejectModal.style.display = 'flex';
  };

  // Fechar modal
  const closeModal = () => {
    rejectModal.style.display = 'none';
    rejectForm.reset();
  };

  closeRejectBtn?.addEventListener('click', closeModal);
  cancelRejectBtn?.addEventListener('click', closeModal);
  rejectOverlay?.addEventListener('click', closeModal);

  // Aprovar solicitação
  window.approveRequest = async function(btn) {
    if (!confirm('Tem a certeza que deseja aprovar esta alteração fiscal?')) return;

    const requestId = btn.getAttribute('data-request-id');
    const formData = new FormData();
    formData.append('action', 'approve');
    formData.append('requestId', requestId);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

    try {
      const res = await fetch('/inc/api/fiscal-requests.php', {
        method: 'POST',
        body: formData
      });

      const data = await res.json();

      if (data.success) {
        showToast('Alteração fiscal aprovada com sucesso!', 'success');
        setTimeout(() => location.reload(), 1500);
      } else {
        showToast(data.message || 'Erro ao aprovar solicitação', 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('Erro ao aprovar solicitação', 'error');
    }
  };

  // Submeter rejeição
  rejectForm?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const requestId = rejectRequestIdInput.value;
    const reason = document.getElementById('rejectReason').value;

    if (reason.trim().length < 10) {
      showToast('O motivo deve ter pelo menos 10 caracteres', 'error');
      return;
    }

    const formData = new FormData();
    formData.append('action', 'reject');
    formData.append('requestId', requestId);
    formData.append('reason', reason);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]')?.value || '');

    try {
      const res = await fetch('/inc/api/fiscal-requests.php', {
        method: 'POST',
        body: formData
      });

      const data = await res.json();

      if (data.success) {
        showToast('Solicitação rejeitada', 'success');
        closeModal();
        setTimeout(() => location.reload(), 1500);
      } else {
        showToast(data.message || 'Erro ao rejeitar solicitação', 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('Erro ao rejeitar solicitação', 'error');
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
</script>

<?php
$content = ob_get_clean();
echo renderDashboardLayout('Aprovações Fiscais', 'Gerir alterações de dados fiscais', $content, 'fiscal-approvals');
?>
