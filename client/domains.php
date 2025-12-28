<?php
/**
 * Client Area: Domain Management Page
 * Lists all domains for logged-in client with status and actions
 * 
 * URL: /client/domains.php
 * Requires: Authentication (see inc/check_session.php)
 */

require_once '../inc/check_session.php';
require_once '../inc/domains.php';

// Verify user is logged in and is a client
if (!isset($_SESSION['user_id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'client') {
	header('Location: ../login.php');
	exit;
}

$userId = $_SESSION['user_id'];
$pdo = cybercore_pdo();

// Get user info
$user = $pdo->query('SELECT id, name, email FROM users WHERE id = ' . (int) $userId)->fetch();

// Get all domains for user
$domains = cybercore_domain_list($userId);

// Handle POST actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!csrf_validate($_POST['csrf_token'] ?? '')) {
		$message = 'Token de segurança inválido';
		$messageType = 'error';
	} else {
		$action = $_POST['action'] ?? '';
		$domainId = (int) ($_POST['domain_id'] ?? 0);

		if ($action === 'sync') {
			// Sync single domain from Plesk
			$domain = $pdo->query('SELECT id FROM domains WHERE id = ' . $domainId . ' AND user_id = ' . $userId)->fetch();
			if ($domain) {
				$service = new DomainService($pdo);
				if ($service->syncFromPlesk($domainId, $userId)) {
					$message = 'Domínio sincronizado com sucesso';
					$messageType = 'success';
					// Reload domains
					$domains = cybercore_domain_list($userId);
				} else {
					$message = 'Erro ao sincronizar domínio';
					$messageType = 'error';
				}
			}
		} elseif ($action === 'renew') {
			// Initiate renewal
			$domain = $pdo->query('SELECT id, domain_name FROM domains WHERE id = ' . $domainId . ' AND user_id = ' . $userId)->fetch();
			if ($domain) {
				$service = new DomainService($pdo);
				if ($service->renewDomain($domainId, $userId)) {
					$message = 'Renovação solicitada. Uma fatura foi gerada.';
					$messageType = 'success';
					$domains = cybercore_domain_list($userId);
				} else {
					$message = 'Erro ao solicitar renovação';
					$messageType = 'error';
				}
			}
		} elseif ($action === 'toggle_auto_renew') {
			// Toggle auto-renew
			$domain = $pdo->query('SELECT id FROM domains WHERE id = ' . $domainId . ' AND user_id = ' . $userId)->fetch();
			if ($domain) {
				$enabled = (int) ($_POST['enabled'] ?? 0);
				$service = new DomainService($pdo);
				if ($service->toggleAutoRenew($domainId, $userId, (bool) $enabled)) {
					$message = $enabled ? 'Renovação automática ativada' : 'Renovação automática desativada';
					$messageType = 'success';
					$domains = cybercore_domain_list($userId);
				} else {
					$message = 'Erro ao alternar renovação automática';
					$messageType = 'error';
				}
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
			<h1>Meus Domínios</h1>
			<p class="text-muted">Gerencie seus domínios registrados e renovações</p>
		</div>
	</div>

	<?php if ($message): ?>
	<div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
		<?php echo htmlspecialchars($message); ?>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
	</div>
	<?php endif; ?>

	<?php if (empty($domains)): ?>
	<div class="alert alert-info">
		<strong>Nenhum domínio registrado</strong><br>
		Você ainda não tem domínios. <a href="../services.php">Registre um novo domínio</a>
	</div>
	<?php else: ?>

	<div class="table-responsive">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>Domínio</th>
					<th>Status</th>
					<th>Expira em</th>
					<th>Dias Restantes</th>
					<th>Renovação</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($domains as $domain): 
					$expiresDate = new DateTime($domain['expires_at']);
					$today = new DateTime('now', new DateTimeZone('UTC'));
					$interval = $today->diff($expiresDate);
					$daysLeft = $interval->invert ? -$interval->days : $interval->days;
					$isExpired = $interval->invert;
					
					// Status badge color
					$statusColor = $statusColors[$domain['status']] ?? '#6c757d';
				?>
				<tr>
					<td>
						<strong><?php echo htmlspecialchars($domain['domain_name']); ?></strong>
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
					</td>
					<td>
						<?php if ($isExpired): ?>
							<span class="badge bg-danger">Expirado há <?php echo abs($daysLeft); ?> dias</span>
						<?php elseif ($daysLeft <= 7): ?>
							<span class="badge bg-danger"><?php echo $daysLeft; ?> dias</span>
						<?php elseif ($daysLeft <= 30): ?>
							<span class="badge bg-warning"><?php echo $daysLeft; ?> dias</span>
						<?php else: ?>
							<span class="text-muted"><?php echo $daysLeft; ?> dias</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($domain['auto_renew']): ?>
							<span class="badge bg-success">Automática</span>
						<?php else: ?>
							<span class="badge bg-secondary">Manual</span>
						<?php endif; ?>
					</td>
					<td>
						<div class="btn-group btn-group-sm" role="group">
							<a href="domain-detail.php?id=<?php echo $domain['id']; ?>" class="btn btn-outline-primary">
								Detalhes
							</a>
							
							<form method="POST" style="display:inline;">
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
								<input type="hidden" name="action" value="sync">
								<input type="hidden" name="domain_id" value="<?php echo $domain['id']; ?>">
								<button type="submit" class="btn btn-outline-secondary btn-sm" title="Sincronizar com Plesk">
									🔄
								</button>
							</form>

							<form method="POST" style="display:inline;">
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
								<input type="hidden" name="action" value="renew">
								<input type="hidden" name="domain_id" value="<?php echo $domain['id']; ?>">
								<button type="submit" class="btn btn-outline-success btn-sm" title="Renovar domínio">
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

	<div class="mt-4 p-3 bg-light rounded">
		<h5>💡 Dicas</h5>
		<ul>
			<li>Clique em <strong>Detalhes</strong> para ver informações completas do domínio e gerir nameservers</li>
			<li>Use o botão <strong>🔄</strong> para sincronizar o status com Plesk</li>
			<li>Ative <strong>Renovação Automática</strong> na página de detalhes para renovações automáticas</li>
			<li>Domínios com menos de 7 dias aparecem em vermelho</li>
		</ul>
	</div>

	<?php endif; ?>
</div>

<?php require_once '../inc/footer.php'; ?>
