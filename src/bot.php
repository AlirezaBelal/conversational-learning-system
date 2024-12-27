<?php

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use Medoo\Medoo;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

$telegramToken = $_ENV['TELEGRAM_BOT_TOKEN'];
define('API_URL', "https://api.telegram.org/bot" . $telegramToken . "/");

$database = new Medoo([
    'database_type' => 'mysql',
    'database_name' => $_ENV['DB_DATABASE'],
    'server' => $_ENV['DB_SERVER'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8mb4',
]);

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $message = $update['message']['text'];

    $database->insert("messages", [
        "chat_id" => $chat_id,
        "message_text" => $message,
        "received_at" => date("Y-m-d H:i:s")
    ]);

    switch ($message) {
        case '/start':
            require_once __DIR__ . '/../commands/start.php';
            break;

        case '/help':
            require_once __DIR__ . '/../commands/help.php';
            break;

        case '/courses':
            require_once __DIR__ . '/../commands/courses.php';
            break;

        case '/contact':
            require_once __DIR__ . '/../commands/contact.php';
            break;

        default:
            sendMessage($chat_id, "دستور دریافت کردم: دستور ناشناخته. لطفاً از دستور `/help` استفاده کنید.");
            break;
    }
}

function sendMessage($chat_id, $text)
{
    $url = API_URL . "sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown' // یا 'MarkdownV2' بسته به نیاز
    ];

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        error_log("Error sending message to Telegram API.");
    }
}

