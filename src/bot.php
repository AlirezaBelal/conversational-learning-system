<?php

require 'vendor/autoload.php';
require_once __DIR__ . '/utils.php';

use Dotenv\Dotenv;
use Medoo\Medoo;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

define('API_URL', "https://api.telegram.org/bot" . $_ENV['TELEGRAM_BOT_TOKEN'] . "/");
define('CHARSET', 'utf8mb4');

$database = new Medoo([
    'database_type' => 'mysql',
    'database_name' => $_ENV['DB_DATABASE'],
    'server' => $_ENV['DB_SERVER'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => CHARSET,
]);

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $message = trim($update['message']['text']);

    saveMessageToDatabase($chat_id, $message);
    ensureUserExists($chat_id);
    handleUserState($chat_id, $message);
    handleCommand($chat_id, $message);
}

function saveMessageToDatabase($chat_id, $message)
{
    global $database;

    $database->insert("messages", [
        "chat_id" => $chat_id,
        "message_text" => $message,
        "received_at" => Medoo::raw('CURRENT_TIMESTAMP'),
    ]);
}

function handleUserState($chat_id, $message)
{
    global $database;

    $userState = $database->get("user_states", "state", ["chat_id" => $chat_id]);

    if ($userState === "in_chat") {
        if (strpos($message, '/stop') === 0) {
            $database->update("user_states", ["state" => "new_user"], ["chat_id" => $chat_id]);
            sendMessage($chat_id, "چت شما با هوش مصنوعی متوقف شد. اگر نیاز به کمک دارید، دوباره از /contact استفاده کنید.");
        } else {
            $response = sendMessageToAI($message, $chat_id);
            sendMessage($chat_id, $response);
        }
        return;
    }

    if (strpos($message, '/contact') === 0) {
        $database->update("user_states", ["state" => "in_chat"], ["chat_id" => $chat_id]);
        sendMessage($chat_id, "شما وارد چت با هوش مصنوعی شدید. می‌توانید پیام خود را ارسال کنید.");
    }
}

function sendMessageToAI($message, $chat_id)
{
    $data = [
        'session_id' => $_ENV['AI_SESSION_ID'],
        'message' => $message,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $_ENV['AI_API_URL'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $_ENV['AI_API_KEY'],
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $response_data = json_decode($response, true);
    return $response_data['response'] ?? "متاسفانه مشکلی در دریافت پاسخ از هوش مصنوعی پیش آمد.";
}

function handleCommand($chat_id, $message)
{
    $commands = [
        '/help' => __DIR__ . '/../commands/help.php',
        '/courses' => __DIR__ . '/../commands/courses.php',
        '/contact' => __DIR__ . '/../commands/contact.php',
        '/interview' => __DIR__ . '/../commands/interview.php',
        '/start' => __DIR__ . '/../commands/start.php',
    ];

    $parts = explode(' ', trim($message));
    $command = strtolower($parts[0]);
    $parameter = isset($parts[1]) ? strtolower($parts[1]) : null;

    if ($command === '/start' && $parameter) {
        switch ($parameter) {
            case 'contact':
                require_once $commands['/contact'];
                break;

            case 'courses':
                require_once $commands['/courses'];
                break;

            case 'interview':
                require_once $commands['/interview'];
                break;

            default:
                sendInvalidCommandMessage($chat_id);
                break;
        }
        return;
    }

    if (isset($commands[$command])) {
        require_once $commands[$command];
    } else {
        sendInvalidCommandMessage($chat_id);
    }
}

function sendInvalidCommandMessage($chat_id)
{
    sendMessage($chat_id, "
متوجه پیام شما نشدم. لطفاً برای راهنمایی بیشتر از دستور /help استفاده کنید.

در صورتی که باگ یا فیدبکی دارید، از طریق لینک زیر با ما به صورت مستقیم در ارتباط باشید:
[لینک ادمین](http://t.me/maninickroshan)
    ");
}