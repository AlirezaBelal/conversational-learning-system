<?php

require_once __DIR__ . '/privacy.php';

function requiredEnv(string $name): string
{
    $value = configurationValue($name);
    if ($value === '') {
        throw new RuntimeException("Missing required environment variable: {$name}");
    }
    return $value;
}

/** No retries here: the webhook returns 503 on delivery failure. */
function sendMessage(int|string $chatId, string $text): bool
{
    $ch = curl_init(API_URL . 'sendMessage');
    if ($ch === false) {
        return false;
    }
    try {
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true,
            ]),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return telegramDeliverySucceeded($status, $body);
    } catch (Throwable) {
        error_log('Telegram transport failed.');
        return false;
    } finally {
        curl_close($ch);
    }
}

function telegramDeliverySucceeded(int $status, string|bool $body): bool
{
    if ($body === false || $status < 200 || $status >= 300) {
        error_log('Telegram transport or HTTP failure.');
        return false;
    }
    $payload = json_decode($body, true);
    $success = is_array($payload) && ($payload['ok'] ?? null) === true;
    if (!$success) {
        error_log('Telegram API rejected the message or returned invalid JSON.');
    }
    return $success;
}

function openLearningDatabase(): PDO
{
    $database = new Medoo\Medoo([
        'type' => 'mysql', 'host' => requiredEnv('DB_SERVER'),
        'port' => (int)configurationValue('DB_PORT', '3306'),
        'database' => requiredEnv('DB_DATABASE'), 'username' => requiredEnv('DB_USERNAME'),
        'password' => requiredEnv('DB_PASSWORD'), 'charset' => 'utf8mb4',
        'error' => PDO::ERRMODE_EXCEPTION,
    ]);
    $database->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->pdo->exec("SET time_zone = '+00:00'");
    return $database->pdo;
}
