<?php
/**
 * Client Area: Domain Details Page
 * Shows full domain information, nameservers, invoices, notifications, and automation history
 * 
 * URL: /client/domain-detail.php?id=123
 * Requires: Authentication + domain ownership verification
 */

require_once '../inc/check_session.php';
require_once '../inc/domains.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
	header('Location: ../login.php');
	exit;
}

$userId = $_SESSION['user_id'];
$domainId = (int) ($_GET['id'] ?? 0);
$pdo = cybercore_pdo();

// Get domain (verify ownership)
$domain = cybercore_domain_get($domainId, $userId);
if (!$domain) {
	header('HTTP/1.1 404 Not Found');
	exit('Domínio não encontrado');
}

// Handle POST actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!csrf_validate($_POST['csrf_token'] ?? '')) {
		$message = 'Token de segurança inválido';
		$messageType = 'error';
	} else {
		$action = $_POST['action'] ?? '';

		if ($action === 'update_nameservers') {
			$nameservers = [
				$_POST['ns1'] ?? '',
				$_POST['ns2'] ?? '',
				$_POST['ns3'] ?? '',
				$_POST['ns4'] ?? '',
			];

			$service = new DomainService($pdo);
			if ($service->updateNameservers($domainId, $userId, $nameservers)) {
				$message = 'Nameservers atualizados com sucesso';
				$messageType = 'success';
				$domain = cybercore_domain_get($domainId, $userId);
			} else {
				$message = 'Erro ao atualizar nameservers';
				$messageType = 'error';
			}
		} elseif ($action === 'toggle_auto_renew') {
			$enabled = (int) ($_POST['enabled'] ?? 0);
			$service = new DomainService($pdo);
			if ($service->toggleAutoRenew($domainId, $userId, (bool) $enabled)) {
				$message = $enabled ? 'Renovação automática ativada' : 'Renovação automática desativada';
				$messageType = 'success';
				$domain = cybercore_domain_get($domainId, $userId);
			} else {
				$message = 'Erro ao alternar renovação automática';
				$messageType = 'error';
			}
		}
	}
}

// Status colors
$statusColors = [
	'active' => '#28a745',
	'expired' => '#dc3545',
	'suspended' => '#ffc107',
	'pending' => '#17a2b8',
];

?>
<?php require_once '../inc/header.php'; ?>

<div class="container my-5">
	<div class="row mb-4">
		<div class="col-md-8">
			<h1><?php echo htmlspecialchars($domain['domain_name']); ?></h1>
			<span class="badge" style="background-color: <?php echo $statusColors[$domain['status']] ?? '#6c757d'; ?>; padding: 0.5rem 1rem; font-size: 1rem;">
				<?php 
					$statusLabels = [
						'active' => 'Ativo',
						'expired' => 'Expirado',
						'suspended' => 'Suspenso',
						'pending' => 'Pendente'
					];
					echo $statusLabels[$domain['status']] ?? 'Desconhecido';
				?>
			</span>
		</div>
		<div class="col-md-4 text-end">
			<a href="domains.php" class="btn btn-secondary">← Voltar</a>
		</div>
	</div>

	<?php if ($message): ?>
	<div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
		<?php echo htmlspecialchars($message); ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	</div>
	<?php endif; ?>

	<!-- Domain Information -->
	<div class="row mb-4">
		<div class="col-md-6">
			<div class="card">
				<div class="card-header">
					<h5>Informações Gerais</h5>
				</div>
				<div class="card-body">
					<table class="table table-borderless">
						<tr>
							<th>Status:</th>
							<td><?php echo htmlspecialchars($domain['status']); ?></td>
						</tr>
						<tr>
							<th>Registrado em:</th>
							<td><?php echo date('d/m/Y', strtotime($domain['registered_at'])); ?></td>
						</tr>
						<tr>
							<th>Expira em:</th>
							<td>
								<strong><?php echo date('d/m/Y', strtotime($domain['expires_at'])); ?></strong>
								<?php 
									$today = new DateTime('now', new DateTimeZone('UTC'));
									$expiresDate = new DateTime($domain['expires_at']);
									$interval = $today->diff($expiresDate);
									$daysLeft = $interval->invert ? -$interval->days : $interval->days;
								?>
								<br>
								<?php if ($interval->invert): ?>
									<span class="badge bg-danger">Expirado há <?php echo abs($daysLeft); ?> dias</span>
								<?php else: ?>
									<span class="badge bg-info"><?php echo $daysLeft; ?> dias restantes</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th>Sincronizado:</th>
							<td><?php echo $domain['last_sync_at'] ? date('d/m/Y H:i', strtotime($domain['last_sync_at'])) : 'Nunca'; ?></td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<div class="col-md-6">
			<div class="card">
				<div class="card-header">
					<h5>Renovação Automática</h5>
				</div>
				<div class="card-body">
					<form method="POST">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
						<input type="hidden" name="action" value="toggle_auto_renew">
						
						<div class="form-check form-switch mb-3">
							<input class="form-check-input" type="checkbox" id="autoRenew" name="enabled" value="1" 
								<?php echo $domain['auto_renew'] ? 'checked' : ''; ?> onchange="this.form.submit();">
							<label class="form-check-label" for="autoRenew">
								Ativar renovação automática
							</label>
						</div>

						<?php if ($domain['auto_renew']): ?>
						<div class="alert alert-success" style="font-size: 0.9rem;">
							✓ Este domínio será renovado automaticamente 30 dias antes da expiração.<br>
							Uma fatura será gerada automaticamente.
						</div>
						<?php else: ?>
						<div class="alert alert-warning" style="font-size: 0.9rem;">
							⚠ Renovação manual. Você receberá lembretes antes da expiração.<br>
							Você precisará solicitar renovação manualmente.
						</div>
						<?php endif; ?>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Nameservers -->
	<div class="row mb-4">
		<div class="col-md-12">
			<div class="card">
				<div class="card-header">
					<h5>Nameservers</h5>
				</div>
				<div class="card-body">
					<form method="POST">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
						<input type="hidden" name="action" value="update_nameservers">
						
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="ns1" class="form-label">Nameserver 1</label>
								<input type="text" class="form-control" id="ns1" name="ns1" placeholder="ns1.example.com"
									value="<?php echo htmlspecialchars($domain['nameserver1'] ?? ''); ?>">
							</div>
							<div class="col-md-6 mb-3">
								<label for="ns2" class="form-label">Nameserver 2</label>
								<input type="text" class="form-control" id="ns2" name="ns2" placeholder="ns2.example.com"
									value="<?php echo htmlspecialchars($domain['nameserver2'] ?? ''); ?>">
							</div>
							<div class="col-md-6 mb-3">
								<label for="ns3" class="form-label">Nameserver 3 (Opcional)</label>
								<input type="text" class="form-control" id="ns3" name="ns3" placeholder="ns3.example.com"
									value="<?php echo htmlspecialchars($domain['nameserver3'] ?? ''); ?>">
							</div>
							<div class="col-md-6 mb-3">
								<label for="ns4" class="form-label">Nameserver 4 (Opcional)</label>
								<input type="text" class="form-control" id="ns4" name="ns4" placeholder="ns4.example.com"
									value="<?php echo htmlspecialchars($domain['nameserver4'] ?? ''); ?>">
							</div>
						</div>

						<button type="submit" class="btn btn-primary">Atualizar Nameservers</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Invoices -->
	<?php if (!empty($domain['invoices'])): ?>
	<div class="row mb-4">
		<div class="col-md-12">
			<div class="card">
				<div class="card-header">
					<h5>Faturas Relacionadas</h5>
				</div>
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead>
							<tr>
								<th>Número</th>
								<th>Descrição</th>
								<th>Valor</th>
								<th>Estado</th>
								<th>Vencimento</th>
								<th>Ações</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($domain['invoices'] as $invoice): ?>
							<tr>
								<td><strong><?php echo htmlspecialchars($invoice['number']); ?></strong></td>
								<td><?php echo htmlspecialchars($invoice['description']); ?></td>
								<td>€<?php echo number_format($invoice['total'], 2, ',', '.'); ?></td>
								<td>
									<?php 
										$statusBadges = [
											'unpaid' => 'warning',
											'paid' => 'success',
											'overdue' => 'danger',
											'canceled' => 'secondary',
											'draft' => 'info'
										];
										$badgeClass = $statusBadges[$invoice['status']] ?? 'secondary';
										$statusLabels = [
											'unpaid' => 'Por pagar',
											'paid' => 'Paga',
											'overdue' => 'Vencida',
											'canceled' => 'Cancelada',
											'draft' => 'Rascunho'
										];
									?>
									<span class="badge bg-<?php echo $badgeClass; ?>">
										<?php echo $statusLabels[$invoice['status']] ?? 'Desconhecido'; ?>
									</span>
								</td>
								<td><?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></td>
								<td>
									<a href="../invoices.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-primary">
										Ver
									</a>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Notifications History -->
	<?php if (!empty($domain['notifications'])): ?>
	<div class="row mb-4">
		<div class="col-md-12">
			<div class="card">
				<div class="card-header">
					<h5>Histórico de Notificações</h5>
				</div>
				<div class="table-responsive">
					<table class="table table-sm mb-0">
						<thead>
							<tr>
								<th>Tipo</th>
								<th>Assunto</th>
								<th>Estado</th>
								<th>Data</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($domain['notifications'] as $notif): ?>
							<tr>
								<td><span class="badge bg-info"><?php echo htmlspecialchars($notif['type']); ?></span></td>
								<td><?php echo htmlspecialchars($notif['subject']); ?></td>
								<td><?php echo $notif['status'] === 'sent' ? '✓ Enviada' : htmlspecialchars($notif['status']); ?></td>
								<td><?php echo date('d/m/Y H:i', strtotime($notif['sent_at'])); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Automation Log -->
	<?php if (!empty($domain['automation_log'])): ?>
	<div class="row mb-4">
		<div class="col-md-12">
			<div class="card">
				<div class="card-header">
					<h5>Histórico de Automações</h5>
				</div>
				<div class="table-responsive">
					<table class="table table-sm mb-0">
						<thead>
							<tr>
								<th>Ação</th>
								<th>Estado</th>
								<th>Mensagem</th>
								<th>Data</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($domain['automation_log'] as $log): ?>
							<tr>
								<td><code><?php echo htmlspecialchars($log['action']); ?></code></td>
								<td>
									<?php if ($log['status'] === 'success'): ?>
										<span class="badge bg-success">✓ Sucesso</span>
									<?php elseif ($log['status'] === 'error'): ?>
										<span class="badge bg-danger">✕ Erro</span>
									<?php else: ?>
										<span class="badge bg-warning"><?php echo htmlspecialchars($log['status']); ?></span>
									<?php endif; ?>
								</td>
								<td><?php echo htmlspecialchars($log['message']); ?></td>
								<td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

</div>

<?php require_once '../inc/footer.php'; ?>
