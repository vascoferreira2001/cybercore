<?php
/**
 * Admin Panel: Domain Management
 * Admins can view all domains, see automation status, trigger manual actions
 * 
 * URL: /admin/domains-manager.php
 * Requires: Admin authentication
 */

require_once '../inc/admin_auth.php';
require_once '../inc/domains.php';

// Verify admin access
if (!isset($_SESSION['admin_id'])) {
	header('Location: ../login.php');
	exit;
}

$pdo = cybercore_pdo();

// Handle admin actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!csrf_validate($_POST['csrf_token'] ?? '')) {
		$message = 'Token de segurança inválido';
		$messageType = 'error';
	} else {
		$action = $_POST['action'] ?? '';
		$domainId = (int) ($_POST['domain_id'] ?? 0);

		if ($action === 'sync_all') {
			// Sync all domains
			$stmt = $pdo->query('SELECT id, user_id FROM domains');
			$domains = $stmt->fetchAll();
			$count = 0;
			$service = new DomainService($pdo);

			foreach ($domains as $domain) {
				if ($service->syncFromPlesk($domain['id'], $domain['user_id'])) {
					$count++;
				}
			}

			$message = "Sincronizados {$count} domínios com sucesso";
			$messageType = 'success';
		} elseif ($action === 'trigger_automation') {
			// Manually trigger automation checks
			shell_exec('php ../cron/domain-automation.php > /tmp/domain-automation.log 2>&1 &');
			$message = 'Verificações de automação disparadas (executando em background)';
			$messageType = 'success';
		} elseif ($action === 'manual_renew') {
			// Manually trigger renewal for a domain
			$userId = $pdo->query('SELECT user_id FROM domains WHERE id = ' . $domainId)->fetch()['user_id'];
			$service = new DomainService($pdo);
			if ($service->renewDomain($domainId, $userId)) {
				$message = 'Renovação solicitada com sucesso';
				$messageType = 'success';
			} else {
				$message = 'Erro ao solicitar renovação';
				$messageType = 'error';
			}
		}
	}
}

// Get all domains with stats
$stmt = $pdo->query('
	SELECT 
		d.id, d.user_id, d.domain_name, d.status, d.registered_at, d.expires_at,
		d.auto_renew, d.plesk_id, d.last_sync_at,
		u.name, u.email,
		COUNT(DISTINCT i.id) as invoice_count,
		COUNT(DISTINCT n.id) as notification_count
	FROM domains d
	LEFT JOIN users u ON d.user_id = u.id
	LEFT JOIN invoices i ON i.reference LIKE CONCAT("domain-", d.id, "%")
	LEFT JOIN notifications n ON n.reference = CONCAT("domain-", d.id)
	GROUP BY d.id
	ORDER BY d.expires_at ASC
');

$domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalDomains = count($domains);
$activeDomains = count(array_filter($domains, fn($d) => $d['status'] === 'active'));
$expiredDomains = count(array_filter($domains, fn($d) => $d['status'] === 'expired'));
$suspendedDomains = count(array_filter($domains, fn($d) => $d['status'] === 'suspended'));
$autoRenewalEnabled = count(array_filter($domains, fn($d) => $d['auto_renew']));

?>
<?php require_once '../inc/header.php'; ?>

<div class="container-fluid my-5">
	<div class="row mb-4">
		<div class="col-md-12">
			<h1>Gestão de Domínios</h1>
			<p class="text-muted">Visão geral de todos os domínios registrados no sistema</p>
		</div>
	</div>

	<?php if ($message): ?>
	<div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
		<?php echo htmlspecialchars($message); ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	</div>
	<?php endif; ?>

	<!-- Stats Cards -->
	<div class="row mb-4">
		<div class="col-md-3">
			<div class="card bg-primary text-white">
				<div class="card-body">
					<h5 class="card-title">Total de Domínios</h5>
					<h2><?php echo $totalDomains; ?></h2>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card bg-success text-white">
				<div class="card-body">
					<h5 class="card-title">Ativos</h5>
					<h2><?php echo $activeDomains; ?></h2>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card bg-warning text-dark">
				<div class="card-body">
					<h5 class="card-title">Renovação Automática</h5>
					<h2><?php echo $autoRenewalEnabled; ?></h2>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card bg-danger text-white">
				<div class="card-body">
					<h5 class="card-title">Expirados/Suspensos</h5>
					<h2><?php echo $expiredDomains + $suspendedDomains; ?></h2>
				</div>
			</div>
		</div>
	</div>

	<!-- Admin Actions -->
	<div class="row mb-4">
		<div class="col-md-12">
			<div class="card">
				<div class="card-header">
					<h5>Ações Administrativas</h5>
				</div>
				<div class="card-body">
					<form method="POST" class="row g-2">
						<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
						
						<div class="col-md-4">
							<button type="submit" name="action" value="sync_all" class="btn btn-primary w-100">
								🔄 Sincronizar Todos os Domínios
							</button>
						</div>
						<div class="col-md-4">
							<button type="submit" name="action" value="trigger_automation" class="btn btn-warning w-100">
								⚙️ Disparar Verificações de Automação
							</button>
						</div>
						<div class="col-md-4">
							<a href="../admin/dashboard.php" class="btn btn-secondary w-100">
								← Dashboard
							</a>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Domains Table -->
	<div class="row">
		<div class="col-md-12">
			<div class="card">
				<div class="card-header">
					<h5>Todos os Domínios</h5>
				</div>
				<div class="table-responsive">
					<table class="table table-hover mb-0">
						<thead>
							<tr>
								<th>Domínio</th>
								<th>Cliente</th>
								<th>Status</th>
								<th>Expira em</th>
								<th>Auto-Renew</th>
								<th>Faturas</th>
								<th>Notificações</th>
								<th>Última Sync</th>
								<th>Ações</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($domains as $domain): 
								$statusColors = [
									'active' => '#28a745',
									'expired' => '#dc3545',
									'suspended' => '#ffc107',
									'pending' => '#17a2b8',
								];
								$statusColor = $statusColors[$domain['status']] ?? '#6c757d';
								
								$expiresDate = new DateTime($domain['expires_at']);
								$today = new DateTime('now', new DateTimeZone('UTC'));
								$interval = $today->diff($expiresDate);
								$daysLeft = $interval->invert ? -$interval->days : $interval->days;
							?>
							<tr>
								<td>
									<strong><?php echo htmlspecialchars($domain['domain_name']); ?></strong>
								</td>
								<td>
									<small>
										<?php echo htmlspecialchars($domain['name']); ?><br>
										<code><?php echo htmlspecialchars($domain['email']); ?></code>
									</small>
								</td>
								<td>
									<span class="badge" style="background-color: <?php echo $statusColor; ?>;">
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
								</td>
								<td>
									<?php echo date('d/m/Y', strtotime($domain['expires_at'])); ?>
									<?php if ($interval->invert): ?>
										<br><span class="badge bg-danger">-<?php echo abs($daysLeft); ?> dias</span>
									<?php elseif ($daysLeft <= 7): ?>
										<br><span class="badge bg-danger"><?php echo $daysLeft; ?> dias</span>
									<?php elseif ($daysLeft <= 30): ?>
										<br><span class="badge bg-warning"><?php echo $daysLeft; ?> dias</span>
									<?php endif; ?>
								</td>
								<td>
									<?php echo $domain['auto_renew'] ? '<span class="badge bg-success">✓ Sim</span>' : '<span class="badge bg-secondary">✗ Não</span>'; ?>
								</td>
								<td>
									<span class="badge bg-info"><?php echo $domain['invoice_count']; ?></span>
								</td>
								<td>
									<span class="badge bg-secondary"><?php echo $domain['notification_count']; ?></span>
								</td>
								<td>
									<small><?php echo $domain['last_sync_at'] ? date('d/m H:i', strtotime($domain['last_sync_at'])) : 'Nunca'; ?></small>
								</td>
								<td>
									<div class="btn-group btn-group-sm" role="group">
										<a href="domain-detail.php?id=<?php echo $domain['id']; ?>" class="btn btn-outline-primary">
											Ver
										</a>
										
										<form method="POST" style="display:inline;">
											<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
											<input type="hidden" name="action" value="manual_renew">
											<input type="hidden" name="domain_id" value="<?php echo $domain['id']; ?>">
											<button type="submit" class="btn btn-outline-warning btn-sm" title="Renovar">
												Renovar
											</button>
										</form>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

</div>

<?php require_once '../inc/footer.php'; ?>
