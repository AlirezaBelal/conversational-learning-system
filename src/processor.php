<?php

require_once __DIR__ . '/router.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/privacy.php';

/** Commit the receipt and learner writes only after an acknowledged delivery. */
function processLearningUpdate(array $update, PDO $pdo, callable $deliver, string $username): string
{
    if (!isset($update['update_id']) || !is_int($update['update_id']) || $update['update_id'] < 0) {
        throw new InvalidArgumentException('Invalid update identifier.');
    }
    $message = learnerMessage($update);
    if ($message === null || !commandTargetsBot($message['text'], $username)) {
        return 'ignored';
    }
    [$command, $parameter] = parseBotCommand($message['text']);
    $module = resolveBotModule($command, $parameter);
    $reply = commandReply($module);
    $pdo->beginTransaction();
    try {
        try {
            $receipt = $pdo->prepare('INSERT INTO update_receipts (update_id) VALUES (?)');
            $receipt->execute([$update['update_id']]);
        } catch (PDOException $error) {
            if (($error->errorInfo[1] ?? null) !== 1062) {
                throw $error;
            }
            $pdo->rollBack();
            return 'duplicate';
        }
        $user = $pdo->prepare('INSERT INTO users (chat_id) VALUES (?) ON DUPLICATE KEY UPDATE chat_id=VALUES(chat_id)');
        $user->execute([$message['chat_id']]);
        if ($module !== null) {
            $state = $pdo->prepare('INSERT INTO user_states (chat_id, state) VALUES (?, ?) ON DUPLICATE KEY UPDATE state=VALUES(state)');
            $state->execute([$message['chat_id'], $module]);
        }
        $stored = $pdo->prepare('INSERT INTO messages (chat_id, message_text) VALUES (?, ?)');
        $stored->execute([$message['chat_id'], messageStorageValue($message['text'])]);
        if ($deliver($message['chat_id'], $reply) !== true) {
            throw new RuntimeException('Telegram delivery was not acknowledged.');
        }
        $pdo->commit();
        return 'processed';
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function purgeLearnerMessages(PDO $pdo, int $days): array
{
    if ($days < 1 || $days > 3650) {
        throw new InvalidArgumentException('Invalid retention period.');
    }
    $cutoff = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify("-{$days} days")->format('Y-m-d H:i:s');
    $pdo->beginTransaction();
    try {
        $counts = [];
        foreach (['messages' => 'received_at', 'update_receipts' => 'processed_at'] as $table => $column) {
            $statement = $pdo->prepare("DELETE FROM {$table} WHERE {$column} < ?");
            $statement->execute([$cutoff]);
            $counts[$table] = $statement->rowCount();
        }
        $pdo->commit();
        return $counts;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}
