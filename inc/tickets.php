<?php
// Support tickets helpers
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function cybercore_ticket_statuses(): array
{
    return ['open', 'customer-replied', 'answered', 'pending', 'closed'];
}

function cybercore_ticket_priorities(): array
{
    return ['low', 'normal', 'high', 'urgent'];
}

function cybercore_ticket_create(int $userId, array $data): int
{
    $status = 'open';
    $priority = $data['priority'] ?? 'normal';

    if (!in_array($priority, cybercore_ticket_priorities(), true)) {
        throw new InvalidArgumentException('Prioridade inválida.');
    }

    $pdo = cybercore_pdo();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO tickets (user_id, subject, priority, status, department) VALUES (:user_id, :subject, :priority, :status, :department)');
        $stmt->execute([
            'user_id' => $userId,
            'subject' => $data['subject'],
            'priority' => $priority,
            'status' => $status,
            'department' => $data['department'] ?? null,
        ]);
        $ticketId = (int) $pdo->lastInsertId();

        $stmtMsg = $pdo->prepare('INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) VALUES (:ticket_id, :user_id, :is_admin, :message)');
        $stmtMsg->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'is_admin' => 0,
            'message' => $data['message'],
        ]);

        $pdo->commit();
        return $ticketId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function cybercore_ticket_reply(int $ticketId, int $userId = null, string $message, bool $isAdmin = false): bool
{
    if (trim($message) === '') {
        throw new InvalidArgumentException('Mensagem não pode estar vazia.');
    }

    $pdo = cybercore_pdo();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message) VALUES (:ticket_id, :user_id, :is_admin, :message)');
        $stmt->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'is_admin' => $isAdmin ? 1 : 0,
            'message' => $message,
        ]);

        $newStatus = $isAdmin ? 'answered' : 'customer-replied';
        $stmtStatus = $pdo->prepare('UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmtStatus->execute([
            'status' => $newStatus,
            'id' => $ticketId,
        ]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function cybercore_ticket_update_status(int $ticketId, string $status): bool
{
    if (!in_array($status, cybercore_ticket_statuses(), true)) {
        throw new InvalidArgumentException('Estado inválido.');
    }
    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('UPDATE tickets SET status = :status, updated_at = NOW() WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $ticketId]);
    return $stmt->rowCount() > 0;
}

function cybercore_ticket_get(int $userId, int $ticketId, bool $asAdmin = false): ?array
{
    $pdo = cybercore_pdo();
    if ($asAdmin) {
        $stmt = $pdo->prepare('SELECT * FROM tickets WHERE id = :id');
        $stmt->execute(['id' => $ticketId]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM tickets WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $ticketId, 'user_id' => $userId]);
    }
    $ticket = $stmt->fetch();
    if (!$ticket) return null;

    $msgStmt = $pdo->prepare('SELECT * FROM ticket_messages WHERE ticket_id = :ticket_id ORDER BY created_at ASC');
    $msgStmt->execute(['ticket_id' => $ticketId]);
    $ticket['messages'] = $msgStmt->fetchAll();
    return $ticket;
}

function cybercore_ticket_list(int $userId, bool $asAdmin = false): array
{
    $pdo = cybercore_pdo();
    if ($asAdmin) {
        $stmt = $pdo->query('SELECT * FROM tickets ORDER BY updated_at DESC');
        return $stmt->fetchAll();
    }
    $stmt = $pdo->prepare('SELECT * FROM tickets WHERE user_id = :user_id ORDER BY updated_at DESC');
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function cybercore_ticket_notify(string $to, string $subject, string $body): void
{
    // Placeholder for email notifications; replace with real mailer
    @mail($to, $subject, $body);
}
