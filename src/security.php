<?php

const MAX_WEBHOOK_BYTES = 1048576;

function validateWebhookSecretConfiguration(string $appEnv, string $webhookSecret, bool $allowLocal = false): void
{
    if ($webhookSecret !== '' && preg_match('/^[A-Za-z0-9_-]{1,256}$/D', $webhookSecret) !== 1) {
        throw new RuntimeException('TELEGRAM_WEBHOOK_SECRET must follow Telegram secret-token syntax.');
    }
    if ($webhookSecret === '' && !(strtolower(trim($appEnv)) === 'development' && $allowLocal)) {
        throw new RuntimeException('Configure TELEGRAM_WEBHOOK_SECRET or explicitly enable local development.');
    }
}

function webhookSecretMatches(string $configuredSecret, string $providedSecret): bool
{
    return $configuredSecret !== '' && $providedSecret !== '' && hash_equals($configuredSecret, $providedSecret);
}

function webhookRequestStatus(array $server, string $environment, string $secret, bool $allowLocal): int
{
    validateWebhookSecretConfiguration($environment, $secret, $allowLocal);
    if (($server['REQUEST_METHOD'] ?? '') !== 'POST') {
        return 405;
    }
    $local = $secret === '' && $allowLocal && strtolower($environment) === 'development'
        && in_array($server['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    if (!$local && !webhookSecretMatches($secret, (string)($server['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''))) {
        return 403;
    }
    if (isset($server['CONTENT_LENGTH']) && (int)$server['CONTENT_LENGTH'] > MAX_WEBHOOK_BYTES) {
        return 413;
    }
    return 200;
}

function decodeWebhookUpdate(string $content): array
{
    if (strlen($content) > MAX_WEBHOOK_BYTES) {
        throw new LengthException('Webhook payload exceeds the size limit.');
    }
    $update = json_decode($content, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($update) || !isset($update['update_id']) || !is_int($update['update_id']) || $update['update_id'] < 0) {
        throw new InvalidArgumentException('Expected an update object with an integer update_id.');
    }
    return $update;
}

function learnerMessage(array $update): ?array
{
    $message = $update['message'] ?? $update['edited_message'] ?? null;
    if (!is_array($message) || !isset($message['text']) || !is_string($message['text']) || trim($message['text']) === '') {
        return null;
    }
    $chat = $message['chat'] ?? null;
    if (!is_array($chat) || !isset($chat['id']) || !is_int($chat['id']) || $chat['id'] === 0
        || abs($chat['id']) > 4503599627370495) {
        throw new InvalidArgumentException('Invalid Telegram chat identifier.');
    }
    return ['chat_id' => $chat['id'], 'text' => trim($message['text'])];
}
