<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/processor.php';
require_once __DIR__ . '/../src/utils.php';

$dsn = getenv('TEST_DATABASE_DSN');
if (!$dsn) {
    if (getenv('REQUIRE_DATABASE_TEST') === 'true') {
        throw new RuntimeException('An isolated TEST_DATABASE_DSN is required.');
    }
    fwrite(STDOUT, "MySQL integration tests skipped: isolated test database not configured.\n");
    return;
}
if (!preg_match('/dbname=learning_gateway_test(?:;|$)/', $dsn)) {
    throw new RuntimeException('Integration tests only allow learning_gateway_test.');
}
$pdo = new PDO($dsn, getenv('TEST_DATABASE_USER'), getenv('TEST_DATABASE_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("SET time_zone = '+00:00'");
if (getenv('TEST_DATABASE_PORT')) {
    $_ENV['DB_SERVER'] = '127.0.0.1';
    $_ENV['DB_PORT'] = getenv('TEST_DATABASE_PORT');
    $_ENV['DB_DATABASE'] = 'learning_gateway_test';
    $_ENV['DB_USERNAME'] = getenv('TEST_DATABASE_USER');
    $_ENV['DB_PASSWORD'] = getenv('TEST_DATABASE_PASSWORD');
    $medooPdo = openLearningDatabase();
    if ($medooPdo->query('SELECT 1')->fetchColumn() != 1) {
        throw new RuntimeException('Medoo bootstrap failed.');
    }
}
$schema = file_get_contents(__DIR__ . '/../bot_database.sql');
$schema = preg_replace('/CREATE DATABASE.*?;\s*USE .*?;/s', '', $schema);
$pdo->exec($schema);
$pdo->exec($schema); // Fresh setup is safely repeatable.
function dbCheck(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAILED: ' . $label);
    }
}
$update = ['update_id' => 99000001, 'message' => ['chat' => ['id' => 99000001], 'text' => '/start courses', 'from' => ['first_name' => 'Synthetic']]];
$sent = 0;
$deliver = function ($chat, $text) use (&$sent): bool {
    dbCheck($chat === 99000001 && str_contains($text, 'دوره'), 'routed Persian response');
    ++$sent;
    return true;
};
try {
    dbCheck(processLearningUpdate($update, $pdo, $deliver, 'acuLearn_bot') === 'processed', 'first delivery');
    dbCheck(processLearningUpdate($update, $pdo, $deliver, 'acuLearn_bot') === 'duplicate', 'duplicate receipt');
    dbCheck($sent === 1, 'duplicate never sends again');
    dbCheck($pdo->query('SELECT COUNT(*) FROM messages WHERE chat_id=99000001')->fetchColumn() == 1, 'single persisted message');
    dbCheck($pdo->query('SELECT state FROM user_states WHERE chat_id=99000001')->fetchColumn() === 'courses', 'committed learner state');
    dbCheck($pdo->query('SELECT first_name FROM users WHERE chat_id=99000001')->fetchColumn() === null, 'unneeded profile not stored');
    dbCheck($pdo->query('SELECT message_text FROM messages WHERE chat_id=99000001')->fetchColumn() === '[redacted:bytes=14]', 'raw text minimized');
    $update['update_id']++;
    $update['message']['text'] = '/help';
    try {
        processLearningUpdate($update, $pdo, fn() => false, 'acuLearn_bot');
        throw new LogicException('Delivery failure was ignored.');
    } catch (RuntimeException $error) {
        dbCheck(!$error instanceof LogicException, 'delivery failure propagates');
    }
    dbCheck($pdo->query('SELECT COUNT(*) FROM update_receipts WHERE update_id=99000002')->fetchColumn() == 0, 'failed delivery receipt rolled back');
    dbCheck($pdo->query('SELECT state FROM user_states WHERE chat_id=99000001')->fetchColumn() === 'courses', 'failed delivery state rolled back');
    dbCheck(processLearningUpdate($update, $pdo, fn() => true, 'acuLearn_bot') === 'processed', 'redelivery recovers');
    $pdo->exec("UPDATE messages SET received_at='2000-01-01' WHERE chat_id=99000001");
    $pdo->exec("UPDATE update_receipts SET processed_at='2000-01-01' WHERE update_id IN (99000001,99000002)");
    $counts = purgeLearnerMessages($pdo, 30);
    dbCheck($counts['messages'] === 2 && $counts['update_receipts'] === 2, 'bounded retention removes both row types');
    // Database failure must happen before delivery and must not claim success.
    $pdo->exec('RENAME TABLE messages TO messages_unavailable');
    $before = $sent;
    try {
        processLearningUpdate($update, $pdo, $deliver, 'acuLearn_bot');
        throw new LogicException('Persistence failure was ignored.');
    } catch (PDOException) {
        dbCheck($sent === $before, 'database failure sends no message');
    } finally {
        $pdo->exec('RENAME TABLE messages_unavailable TO messages');
    }
    fwrite(STDOUT, "MySQL processing, rollback, redelivery and retention tests passed.\n");
} finally {
    $pdo->exec('DELETE FROM users WHERE chat_id=99000001');
    $pdo->exec('DELETE FROM update_receipts WHERE update_id IN (99000001,99000002)');
}
