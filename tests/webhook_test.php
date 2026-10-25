<?php

require_once __DIR__ . '/../src/processor.php';
require_once __DIR__ . '/../src/utils.php';

function check(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAILED: ' . $label);
    }
}
function rejects(callable $action, string $label): void
{
    try {
        $action();
    } catch (Throwable) {
        return;
    }
    throw new RuntimeException('FAILED: ' . $label);
}

$server = ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '203.0.113.1'];
rejects(fn() => webhookRequestStatus($server, 'production', '', false), 'missing production configuration');
check(webhookRequestStatus($server, 'production', 'synthetic', false) === 403, 'missing authentication');
$server['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] = 'synthetic';
check(webhookRequestStatus($server, 'production', 'synthetic', false) === 200, 'valid authentication');
$server['REQUEST_METHOD'] = 'GET';
check(webhookRequestStatus($server, 'production', 'synthetic', false) === 405, 'method');
$server['REQUEST_METHOD'] = 'POST';
$server['CONTENT_LENGTH'] = MAX_WEBHOOK_BYTES + 1;
check(webhookRequestStatus($server, 'production', 'synthetic', false) === 413, 'declared size');
check(webhookRequestStatus(['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '127.0.0.1'], 'development', '', true) === 200, 'explicit loopback development');
check(webhookRequestStatus(['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '203.0.113.1'], 'development', '', true) === 403, 'development cannot authenticate public traffic');
foreach (['', '{', 'null', '[]', '{"update_id":"1"}', '{"update_id":-1}'] as $body) {
    rejects(fn() => decodeWebhookUpdate($body), 'malformed payload');
}
rejects(fn() => decodeWebhookUpdate(str_repeat('x', MAX_WEBHOOK_BYTES + 1)), 'actual size');
check(learnerMessage(decodeWebhookUpdate('{"update_id":1,"callback_query":{}}')) === null, 'unsupported update');
rejects(fn() => learnerMessage(['message' => ['chat' => ['id' => []], 'text' => '/start']]), 'malformed chat');
rejects(fn() => learnerMessage(['message' => ['chat' => ['id' => 7], 'text' => str_repeat('x', 16385)]]), 'oversized text cannot poison raw storage');
check(learnerMessage(['edited_message' => ['chat' => ['id' => 7], 'text' => '/help']])['chat_id'] === 7, 'edited message');
check(learnerMessage(['message' => ['chat' => ['id' => 7, 'type' => 'private'], 'text' => '/help']])['chat_id'] === 7, 'private chat accepted');
foreach (['group', 'supergroup', 'channel'] as $type) {
    check(learnerMessage(['message' => ['chat' => ['id' => -1001234, 'type' => $type], 'text' => '/help']]) === null, 'nonprivate message ignored');
    check(learnerMessage(['edited_message' => ['chat' => ['id' => -1001234, 'type' => $type], 'text' => '/help']]) === null, 'nonprivate edit ignored');
}
rejects(fn() => learnerMessage(['message' => ['chat' => ['id' => 7, 'type' => []], 'text' => '/help']]), 'malformed chat type');
check(learnerMessage(['message' => ['chat' => ['id' => 7], 'text' => '/help']])['chat_id'] === 7, 'legacy missing chat type stays compatible');
check(!commandTargetsBot('/help@other_bot', 'acuLearn_bot'), 'foreign bot ignored');
check(commandTargetsBot('/help@ACULEARN_BOT', 'acuLearn_bot'), 'own suffix case insensitive');
foreach (['start', 'help', 'courses', 'interview', 'support'] as $module) {
    check(strlen(commandReply($module)) > 50, 'offline Persian reply');
}
check(telegramDeliverySucceeded(200, '{"ok":true}'), 'acknowledged Telegram delivery');
foreach ([[200, '{"ok":false}'], [200, 'invalid'], [429, '{"ok":true}'], [0, false]] as [$status, $body]) {
    check(!telegramDeliverySucceeded($status, $body), 'unacknowledged delivery');
}
fwrite(STDOUT, "Webhook, offline replies and delivery tests passed.\n");
