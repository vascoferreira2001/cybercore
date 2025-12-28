<?php
/**
 * CyberCore – Domain Management Service
 * Integrates Plesk API, billing, notifications, and automation
 * 
 * Usage:
 *   $domainService = new DomainService($pdo);
 *   $domains = $domainService->listByUser($userId);
 *   $domain = $domainService->getById($domainId, $userId);
 *   $domainService->renewDomain($domainId, $userId);
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/plesk.php';
require_once __DIR__ . '/billing.php';

class DomainService
{
	private $pdo;

	public function __construct($pdo)
	{
		$this->pdo = $pdo;
	}

	/**
	 * List all domains for a user
	 */
	public function listByUser(int $userId): array
	{
		$stmt = $this->pdo->prepare('
			SELECT id, user_id, domain_name, status, registered_at, expires_at, 
				   auto_renew, nameserver1, nameserver2, nameserver3, nameserver4,
				   plesk_id, last_sync_at, created_at
			FROM domains
			WHERE user_id = :user_id
			ORDER BY expires_at ASC
		');
		$stmt->execute(['user_id' => $userId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

	/**
	 * Get single domain with full details
	 */
	public function getById(int $domainId, int $userId): ?array
	{
		$stmt = $this->pdo->prepare('
			SELECT id, user_id, domain_name, status, registered_at, expires_at,
				   auto_renew, nameserver1, nameserver2, nameserver3, nameserver4,
				   plesk_id, last_sync_at, created_at
			FROM domains
			WHERE id = :id AND user_id = :user_id
			LIMIT 1
		');
		$stmt->execute(['id' => $domainId, 'user_id' => $userId]);
		$domain = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$domain) {
			return null;
		}

		// Enrich with related data
		$domain['invoices'] = $this->getRelatedInvoices($domainId);
		$domain['notifications'] = $this->getNotificationHistory($domainId);
		$domain['automation_log'] = $this->getAutomationLog($domainId);

		return $domain;
	}

	/**
	 * Get related invoices for a domain
	 */
	public function getRelatedInvoices(int $domainId): array
	{
		$stmt = $this->pdo->prepare('
			SELECT id, number, description, amount, vat_amount, total, status, due_date, issued_at, paid_at
			FROM invoices
			WHERE reference LIKE :ref
			ORDER BY issued_at DESC
		');
		$stmt->execute(['ref' => 'domain-' . $domainId . '%']);
		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

	/**
	 * Get notification history for a domain
	 */
	public function getNotificationHistory(int $domainId): array
	{
		$stmt = $this->pdo->prepare('
			SELECT id, type, subject, status, sent_at
			FROM notifications
			WHERE reference = :ref
			ORDER BY sent_at DESC
			LIMIT 20
		');
		$stmt->execute(['ref' => 'domain-' . $domainId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

	/**
	 * Get automation history for a domain
	 */
	public function getAutomationLog(int $domainId): array
	{
		$stmt = $this->pdo->prepare('
			SELECT id, action, status, message, created_at
			FROM logs
			WHERE entity_type = "domain" AND entity_id = :id
			ORDER BY created_at DESC
			LIMIT 50
		');
		$stmt->execute(['id' => $domainId]);
		return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
	}

	/**
	 * Sync domain status from Plesk
	 */
	public function syncFromPlesk(int $domainId, int $userId): bool
	{
		$domain = $this->getById($domainId, $userId);
		if (!$domain || !$domain['plesk_id']) {
			return false;
		}

		try {
			$pleskDomain = cybercore_plesk_request('GET', '/api/v2/domains/' . $domain['plesk_id']);

			// Update domain record
			$stmt = $this->pdo->prepare('
				UPDATE domains
				SET status = :status, expires_at = :expires_at, 
					last_sync_at = NOW()
				WHERE id = :id
			');

			$stmt->execute([
				'status' => $pleskDomain['status'] ?? 'active',
				'expires_at' => $pleskDomain['expirationDate'] ?? $domain['expires_at'],
				'id' => $domainId,
			]);

			$this->logAction($domainId, 'sync_plesk', 'success', 'Sincronização com Plesk realizada com sucesso');
			return true;
		} catch (Exception $e) {
			$this->logAction($domainId, 'sync_plesk', 'error', 'Erro na sincronização: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Update nameservers
	 */
	public function updateNameservers(int $domainId, int $userId, array $nameservers): bool
	{
		$domain = $this->getById($domainId, $userId);
		if (!$domain) {
			return false;
		}

		try {
			// Update in Plesk
			if ($domain['plesk_id']) {
				$payload = [
					'nameservers' => array_filter($nameservers),
				];
				cybercore_plesk_request('PUT', '/api/v2/domains/' . $domain['plesk_id'], $payload);
			}

			// Update locally
			$stmt = $this->pdo->prepare('
				UPDATE domains
				SET nameserver1 = :ns1, nameserver2 = :ns2, 
					nameserver3 = :ns3, nameserver4 = :ns4
				WHERE id = :id
			');

			$stmt->execute([
				'ns1' => $nameservers[0] ?? null,
				'ns2' => $nameservers[1] ?? null,
				'ns3' => $nameservers[2] ?? null,
				'ns4' => $nameservers[3] ?? null,
				'id' => $domainId,
			]);

			$this->logAction($domainId, 'update_nameservers', 'success', 'Nameservers atualizados');
			return true;
		} catch (Exception $e) {
			$this->logAction($domainId, 'update_nameservers', 'error', $e->getMessage());
			return false;
		}
	}

	/**
	 * Toggle auto-renew
	 */
	public function toggleAutoRenew(int $domainId, int $userId, bool $enabled): bool
	{
		$domain = $this->getById($domainId, $userId);
		if (!$domain) {
			return false;
		}

		try {
			// Update in Plesk if available
			if ($domain['plesk_id']) {
				$payload = ['autoRenewal' => $enabled];
				cybercore_plesk_request('PUT', '/api/v2/domains/' . $domain['plesk_id'], $payload);
			}

			// Update locally
			$stmt = $this->pdo->prepare('UPDATE domains SET auto_renew = :auto_renew WHERE id = :id');
			$stmt->execute(['auto_renew' => (int) $enabled, 'id' => $domainId]);

			$action = $enabled ? 'Renovação automática ativada' : 'Renovação automática desativada';
			$this->logAction($domainId, 'toggle_auto_renew', 'success', $action);
			return true;
		} catch (Exception $e) {
			$this->logAction($domainId, 'toggle_auto_renew', 'error', $e->getMessage());
			return false;
		}
	}

	/**
	 * Renew domain manually
	 */
	public function renewDomain(int $domainId, int $userId): bool
	{
		$domain = $this->getById($domainId, $userId);
		if (!$domain) {
			return false;
		}

		try {
			// Get user for billing
			$user = $this->getUser($userId);

			// Create renewal invoice
			$renewalPrice = 12.99; // TODO: Get from domain plan / pricing table
			$invoiceId = cybercore_invoice_create($userId, [
				'number' => cybercore_invoice_generate_number($userId),
				'reference' => 'domain-' . $domainId . '-renewal-' . time(),
				'description' => 'Renovação de domínio: ' . $domain['domain_name'],
				'amount' => $renewalPrice,
				'vat_rate' => 23.0,
				'currency' => 'EUR',
				'status' => 'unpaid',
				'due_date' => date('Y-m-d', strtotime('+7 days')),
			]);

			// Send notification email
			$this->sendNotification($userId, $domainId, 'renewal_invoice', [
				'domain' => $domain['domain_name'],
				'invoice_id' => $invoiceId,
				'amount' => $renewalPrice,
			]);

			$this->logAction($domainId, 'renewal_requested', 'success', 'Renovação solicitada - Fatura #' . $invoiceId);
			return true;
		} catch (Exception $e) {
			$this->logAction($domainId, 'renewal_requested', 'error', $e->getMessage());
			return false;
		}
	}

	/**
	 * Send notification email (uses existing email system)
	 */
	public function sendNotification(int $userId, int $domainId, string $type, array $data): void
	{
		$domain = $this->pdo->query('SELECT domain_name FROM domains WHERE id = ' . (int) $domainId)->fetch();
		if (!$domain) {
			return;
		}

		$user = $this->getUser($userId);

		// Prepare notification data
		$notification = [
			'user_id' => $userId,
			'type' => $type,
			'reference' => 'domain-' . $domainId,
			'subject' => $this->getNotificationSubject($type, $domain['domain_name']),
			'status' => 'sent',
		];

		// Store in notifications table
		$stmt = $this->pdo->prepare('
			INSERT INTO notifications (user_id, type, reference, subject, status)
			VALUES (:user_id, :type, :reference, :subject, :status)
		');
		$stmt->execute($notification);

		// Send email via existing mail system (hooks to email_templates table)
		// This will be handled by a separate email service
		$this->queueEmail($userId, $type, $data);
	}

	/**
	 * Queue email for delivery
	 */
	private function queueEmail(int $userId, string $type, array $data): void
	{
		// Retrieve template from email_templates table
		$stmt = $this->pdo->prepare('
			SELECT template_content, subject FROM email_templates 
			WHERE template_key = :key LIMIT 1
		');
		$stmt->execute(['key' => 'domain_' . $type]);
		$template = $stmt->fetch();

		if ($template) {
			// Parse template variables
			$content = $template['template_content'];
			foreach ($data as $key => $value) {
				$content = str_replace('{{' . $key . '}}', $value, $content);
			}

			// Queue or send (depends on mail service implementation)
			// For now, just log to indicate it should be sent
			error_log('Email queued for user ' . $userId . ': ' . $type);
		}
	}

	/**
	 * Get notification subject by type
	 */
	private function getNotificationSubject(string $type, string $domainName): string
	{
		$subjects = [
			'renewal_reminder_30' => 'Renovação de domínio em 30 dias: ' . $domainName,
			'renewal_reminder_15' => 'Renovação de domínio em 15 dias: ' . $domainName,
			'renewal_reminder_7' => 'Renovação de domínio em 7 dias: ' . $domainName,
			'renewal_invoice' => 'Fatura de renovação: ' . $domainName,
			'domain_expired' => 'Domínio expirado: ' . $domainName,
			'domain_suspended' => 'Domínio suspenso: ' . $domainName,
			'renewal_completed' => 'Domínio renovado: ' . $domainName,
		];
		return $subjects[$type] ?? 'Notificação de domínio: ' . $domainName;
	}

	/**
	 * Log domain action
	 */
	public function logAction(int $domainId, string $action, string $status, string $message): void
	{
		$stmt = $this->pdo->prepare('
			INSERT INTO logs (entity_type, entity_id, action, status, message)
			VALUES (:entity_type, :entity_id, :action, :status, :message)
		');
		$stmt->execute([
			'entity_type' => 'domain',
			'entity_id' => $domainId,
			'action' => $action,
			'status' => $status,
			'message' => $message,
		]);
	}

	/**
	 * Get user info for billing/email
	 */
	private function getUser(int $userId): ?array
	{
		$stmt = $this->pdo->prepare('SELECT id, email, name FROM users WHERE id = :id LIMIT 1');
		$stmt->execute(['id' => $userId]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
}

// Convenience functions for direct use
function cybercore_domain_list(int $userId): array
{
	$service = new DomainService(cybercore_pdo());
	return $service->listByUser($userId);
}

function cybercore_domain_get(int $domainId, int $userId): ?array
{
	$service = new DomainService(cybercore_pdo());
	return $service->getById($domainId, $userId);
}

function cybercore_domain_sync(int $domainId, int $userId): bool
{
	$service = new DomainService(cybercore_pdo());
	return $service->syncFromPlesk($domainId, $userId);
}

function cybercore_domain_renew(int $domainId, int $userId): bool
{
	$service = new DomainService(cybercore_pdo());
	return $service->renewDomain($domainId, $userId);
}

function cybercore_domain_update_nameservers(int $domainId, int $userId, array $nameservers): bool
{
	$service = new DomainService(cybercore_pdo());
	return $service->updateNameservers($domainId, $userId, $nameservers);
}

function cybercore_domain_toggle_auto_renew(int $domainId, int $userId, bool $enabled): bool
{
	$service = new DomainService(cybercore_pdo());
	return $service->toggleAutoRenew($domainId, $userId, $enabled);
}
