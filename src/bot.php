<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/processor.php';

try {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/../config')->safeLoad();
    $environment = configurationValue('APP_ENV', 'production');
    $secret = configurationValue('TELEGRAM_WEBHOOK_SECRET');
    $status = webhookRequestStatus($_SERVER, $environment, $secret, envFlag('ALLOW_UNAUTHENTICATED_LOCAL_WEBHOOK'));
    if ($status !== 200) {
        if ($status === 405) {
            header('Allow: POST');
        }
        http_response_code($status);
        return;
    }
    $stream = fopen('php://input', 'rb');
    $content = $stream === false ? false : stream_get_contents($stream, MAX_WEBHOOK_BYTES + 1);
    if (is_resource($stream)) {
        fclose($stream);
    }
    if (!is_string($content)) {
        throw new RuntimeException('Could not read webhook request.');
    }
    try {
        $update = decodeWebhookUpdate($content);
        $message = learnerMessage($update);
    } catch (LengthException) {
        http_response_code(413);
        return;
    } catch (JsonException|InvalidArgumentException) {
        http_response_code(400);
        return;
    }
    $username = configurationValue('TELEGRAM_BOT_USERNAME', 'acuLearn_bot');
    if ($message === null || !commandTargetsBot($message['text'], $username)) {
        http_response_code(200);
        return;
    }
    define('API_URL', 'https://api.telegram.org/bot' . requiredEnv('TELEGRAM_BOT_TOKEN') . '/');
    processLearningUpdate($update, openLearningDatabase(), 'sendMessage', $username);
    http_response_code(200);
} catch (Throwable) {
    // Never expose request bodies, SQL details, configuration values or URLs.
    error_log('Webhook processing failed; check configuration, database and delivery availability.');
    http_response_code(503);
}
