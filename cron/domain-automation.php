<?php
/**
 * CyberCore – Domain Automation & Cron Jobs
 * 
 * Scheduled automation for domain renewal, expiration notices, and suspensions
 * 
 * Usage: php cron/domain-automation.php
 *   - Checks all domains for expiration
 *   - Sends renewal reminders (30, 15, 7 days)
 *   - Generates renewal invoices
 *   - Suspends domains if overdue
 *   - Logs all actions
 * 
 * Install in cron:
 *   0 2 * * * /usr/bin/php /home/user/cybercore/cron/domain-automation.php >> /var/log/domain-automation.log 2>&1
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/domains.php';
require_once __DIR__ . '/../config/database.php';

class DomainAutomation
{
	private $pdo;
	private $domainService;
	private $logPrefix = '[DOMAIN-AUTO]';

	public function __construct()
	{
		$this->pdo = cybercore_pdo();
		$this->domainService = new DomainService($this->pdo);
	}

	/**
	 * Main automation entry point
	 */
	public function run(): void
	{
		$this->log('=== Domain Automation Started ===');

		try {
			$this->checkExpiringDomains();
			$this->checkOverduePayments();
			$this->processAutoRenewals();
			$this->cleanupOldNotifications();
			$this->log('=== Domain Automation Completed Successfully ===');
		} catch (Exception $e) {
			$this->log('ERROR: ' . $e->getMessage(), 'error');
			http_response_code(500);
			exit(1);
		}
	}

	/**
	 * Check expiring domains and send notifications
	 */
	private function checkExpiringDomains(): void
	{
		$this->log('Checking expiring domains...');

		// Get all domains expiring within 30 days
		$stmt = $this->pdo->query('
			SELECT id, user_id, domain_name, expires_at
			FROM domains
			WHERE status = "active"
			AND expires_at IS NOT NULL
			AND DATE(expires_at) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
			ORDER BY expires_at ASC
		');

		$expiringDomains = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$count = count($expiringDomains);
		$this->log("Found {$count} expiring domains");

		foreach ($expiringDomains as $domain) {
			$daysLeft = $this->daysUntil($domain['expires_at']);

			// Send notifications at specific intervals
			if ($daysLeft == 30) {
				$this->sendReminderNotification($domain, 30);
			} elseif ($daysLeft == 15) {
				$this->sendReminderNotification($domain, 15);
			} elseif ($daysLeft == 7) {
				$this->sendReminderNotification($domain, 7);
			}
		}
	}

	/**
	 * Send reminder notification
	 */
	private function sendReminderNotification(array $domain, int $daysLeft): void
	{
		$domainId = $domain['id'];
		$userId = $domain['user_id'];
		$domainName = $domain['domain_name'];

		// Check if reminder was already sent for this interval
		$stmt = $this->pdo->prepare('
			SELECT id FROM notifications
			WHERE user_id = :user_id
			AND reference = :ref
			AND type = :type
			AND DATE(sent_at) = CURDATE()
			LIMIT 1
		');

		$stmt->execute([
			'user_id' => $userId,
			'ref' => 'domain-' . $domainId,
			'type' => 'renewal_reminder_' . $daysLeft,
		]);

		if ($stmt->fetch()) {
			$this->log("Reminder for domain {$domainName} ({$daysLeft} days) already sent today");
			return;
		}

		// Send notification
		$this->domainService->sendNotification(
			$userId,
			$domainId,
			'renewal_reminder_' . $daysLeft,
			[
				'domain' => $domainName,
				'days_left' => $daysLeft,
				'expires_at' => $domain['expires_at'],
			]
		);

		$this->log("Sent {$daysLeft}-day reminder for {$domainName} to user {$userId}");
	}

	/**
	 * Check for overdue renewal invoices and suspend domains
	 */
	private function checkOverduePayments(): void
	{
		$this->log('Checking overdue payments...');

		// Get domains with overdue invoices
		$stmt = $this->pdo->query('
			SELECT DISTINCT d.id, d.user_id, d.domain_name
			FROM domains d
			JOIN invoices i ON i.reference LIKE CONCAT("domain-", d.id, "%")
			WHERE d.status = "active"
			AND i.status = "overdue"
			AND DATEDIFF(NOW(), i.due_date) >= 5
		');

		$overdueDomains = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$this->log('Found ' . count($overdueDomains) . ' domains with overdue payments');

		foreach ($overdueDomains as $domain) {
			$this->suspendDomain($domain);
		}
	}

	/**
	 * Suspend domain due to unpaid invoice
	 */
	private function suspendDomain(array $domain): void
	{
		$domainId = $domain['id'];
		$domainName = $domain['domain_name'];
		$userId = $domain['user_id'];

		try {
			// Update status locally
			$stmt = $this->pdo->prepare('UPDATE domains SET status = :status WHERE id = :id');
			$stmt->execute(['status' => 'suspended', 'id' => $domainId]);

			// Try to suspend in Plesk
			$plesk = $this->pdo->query('SELECT plesk_id FROM domains WHERE id = ' . (int) $domainId)->fetch();
			if ($plesk && $plesk['plesk_id']) {
				try {
					cybercore_plesk_request('PUT', '/api/v2/domains/' . $plesk['plesk_id'], [
						'status' => 'suspended',
					]);
				} catch (Exception $e) {
					$this->log('Warning: Could not suspend in Plesk - ' . $e->getMessage(), 'warning');
				}
			}

			// Notify user
			$this->domainService->sendNotification($userId, $domainId, 'domain_suspended', [
				'domain' => $domainName,
				'reason' => 'Pagamento em atraso',
			]);

			$this->domainService->logAction($domainId, 'auto_suspend', 'success', 'Domínio suspenso devido a pagamento em atraso');
			$this->log("Suspended domain {$domainName} (user: {$userId})");
		} catch (Exception $e) {
			$this->log("Error suspending domain {$domainName}: " . $e->getMessage(), 'error');
		}
	}

	/**
	 * Process automatic renewals for domains with auto_renew enabled
	 */
	private function processAutoRenewals(): void
	{
		$this->log('Processing automatic renewals...');

		// Get domains eligible for auto-renewal
		$stmt = $this->pdo->query('
			SELECT id, user_id, domain_name, expires_at
			FROM domains
			WHERE auto_renew = 1
			AND status = "active"
			AND expires_at IS NOT NULL
			AND DATE(expires_at) <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
			AND DATE(expires_at) > CURDATE()
		');

		$renewalDomains = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$this->log('Found ' . count($renewalDomains) . ' domains eligible for auto-renewal');

		foreach ($renewalDomains as $domain) {
			$this->processAutoRenewal($domain);
		}
	}

	/**
	 * Process single auto-renewal
	 */
	private function processAutoRenewal(array $domain): void
	{
		$domainId = $domain['id'];
		$userId = $domain['user_id'];
		$domainName = $domain['domain_name'];

		try {
			// Check if renewal invoice was already generated
			$stmt = $this->pdo->prepare('
				SELECT id FROM invoices
				WHERE user_id = :user_id
				AND reference LIKE :ref
				AND status IN ("unpaid", "paid")
				AND DATE(issued_at) >= DATE_SUB(CURDATE(), INTERVAL 5 DAY)
				LIMIT 1
			');

			$stmt->execute([
				'user_id' => $userId,
				'ref' => 'domain-' . $domainId . '-auto-renewal%',
			]);

			if ($stmt->fetch()) {
				$this->log("Auto-renewal invoice already exists for {$domainName}");
				return;
			}

			// Generate renewal invoice
			$renewalPrice = 12.99; // TODO: Get from pricing table
			$invoiceId = cybercore_invoice_create($userId, [
				'number' => cybercore_invoice_generate_number($userId),
				'reference' => 'domain-' . $domainId . '-auto-renewal-' . time(),
				'description' => 'Renovação automática: ' . $domainName,
				'amount' => $renewalPrice,
				'vat_rate' => 23.0,
				'currency' => 'EUR',
				'status' => 'unpaid',
				'due_date' => date('Y-m-d', strtotime('+3 days')),
			]);

			// Send notification
			$this->domainService->sendNotification($userId, $domainId, 'renewal_invoice', [
				'domain' => $domainName,
				'invoice_id' => $invoiceId,
				'amount' => $renewalPrice,
				'auto_renewal' => true,
			]);

			$this->domainService->logAction($domainId, 'auto_renewal_invoice', 'success', 'Fatura de renovação automática gerada #' . $invoiceId);
			$this->log("Generated auto-renewal invoice for {$domainName} (user: {$userId}, invoice: {$invoiceId})");
		} catch (Exception $e) {
			$this->domainService->logAction($domainId, 'auto_renewal_invoice', 'error', $e->getMessage());
			$this->log("Error processing auto-renewal for {$domainName}: " . $e->getMessage(), 'error');
		}
	}

	/**
	 * Cleanup old notifications to keep database clean
	 */
	private function cleanupOldNotifications(): void
	{
		$this->log('Cleaning up old notifications...');

		// Delete notifications older than 90 days
		$stmt = $this->pdo->prepare('
			DELETE FROM notifications
			WHERE sent_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
		');

		$stmt->execute();
		$count = $this->pdo->query('SELECT CHANGES() as count')->fetch()['count'];
		$this->log("Deleted {$count} old notifications");
	}

	/**
	 * Helper: Calculate days until date
	 */
	private function daysUntil(string $dateString): int
	{
		$date = new DateTime($dateString);
		$today = new DateTime('now', new DateTimeZone('UTC'));
		$interval = $today->diff($date);
		return $interval->days;
	}

	/**
	 * Log message
	 */
	private function log(string $message, string $level = 'info'): void
	{
		$timestamp = date('Y-m-d H:i:s');
		$logMessage = "[{$timestamp}] [{$level}] {$this->logPrefix} {$message}";
		echo $logMessage . "\n";
		error_log($logMessage);
	}
}

// Run automation
if (php_sapi_name() === 'cli') {
	$automation = new DomainAutomation();
	$automation->run();
} else {
	http_response_code(403);
	exit('This script can only be run from CLI');
}
