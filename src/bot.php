<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/privacy.php';

use Dotenv\Dotenv;
use Medoo\Medoo;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->safeLoad();

$telegramToken = requiredEnv('TELEGRAM_BOT_TOKEN');
$dbServer = requiredEnv('DB_SERVER');
$dbName = requiredEnv('DB_DATABASE');
$dbUsername = requiredEnv('DB_USERNAME');
$dbPassword = requiredEnv('DB_PASSWORD');

$appEnv = strtolower(trim((string)($_ENV['APP_ENV'] ?? 'development')));
$webhookSecret = trim((string)($_ENV['TELEGRAM_WEBHOOK_SECRET'] ?? ''));
validateWebhookSecretConfiguration($appEnv, $webhookSecret);

if ($webhookSecret !== '') {
    $providedSecret = (string)($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '');
    if (!webhookSecretMatches($webhookSecret, $providedSecret)) {
        http_response_code(403);
        exit;
    }
}

define('API_URL', 'https://api.telegram.org/bot' . $telegramToken . '/');
define('CHARSET', 'utf8mb4');

$database = new Medoo([
    'database_type' => 'mysql',
    'database_name' => $dbName,
    'server' => $dbServer,
    'username' => $dbUsername,
    'password' => $dbPassword,
    'charset' => CHARSET,
]);

$content = file_get_contents('php://input');
if (!is_string($content) || trim($content) === '') {
    http_response_code(200);
    exit;
}

try {
    $update = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    http_response_code(400);
    exit;
}

$messagePayload = $update['message'] ?? $update['edited_message'] ?? null;
if (!is_array($messagePayload)) {
    http_response_code(200);
    exit;
}

$chatId = $messagePayload['chat']['id'] ?? null;
$text = $messagePayload['text'] ?? null;
if ($chatId === null || !is_string($text) || trim($text) === '') {
    http_response_code(200);
    exit;
}

$text = trim($text);
ensureUserExists($messagePayload);
saveIncomingMessage($chatId, $text);
handleCommand($chatId, $text);
http_response_code(200);

function handleCommand(int|string $chat_id, string $message): void
{
    [$command, $parameter] = parseBotCommand($message);
    $module = resolveBotModule($command, $parameter);

    $commandFiles = [
        'help' => __DIR__ . '/../commands/help.php',
        'courses' => __DIR__ . '/../commands/courses.php',
        'support' => __DIR__ . '/../commands/support.php',
        'interview' => __DIR__ . '/../commands/interview.php',
        'start' => __DIR__ . '/../commands/start.php',
    ];

    if ($module === null || !isset($commandFiles[$module])) {
        sendInvalidCommandMessage($chat_id);
        return;
    }

    setUserState($chat_id, $module);
    require $commandFiles[$module];
}

function sendInvalidCommandMessage(int|string $chat_id): void
{
    sendMessage($chat_id, "
نفهمیدم چی گفتی! 😅 برای راهنمایی بیشتر، دستور /help رو بزن.

اگه باگ یا فیدبکی داری، می‌تونی با [پشتیبان](https://t.me/maninickroshan) در ارتباط باشی.
");
}
