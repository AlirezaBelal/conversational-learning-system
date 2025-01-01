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

function handleCommand($chat_id, $message)
{
    $commands = [
        '/help' => __DIR__ . '/../commands/help.php',
        '/courses' => __DIR__ . '/../commands/courses.php',
        '/support' => __DIR__ . '/../commands/support.php',
        '/interview' => __DIR__ . '/../commands/interview.php',
        '/start' => __DIR__ . '/../commands/start.php',
    ];

    $parts = explode(' ', trim($message));
    $command = strtolower($parts[0]);
    $parameter = isset($parts[1]) ? strtolower($parts[1]) : null;

    if ($command === '/start' && $parameter) {
        switch ($parameter) {
            case 'support':
                require_once $commands['/support'];
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
نفهمیدم چی گفتی! 😅 برای راهنمایی بیشتر، می‌تونی دستور /help رو بزن.

اگه باگ یا فیدبکی داری، مستقیم می‌تونی از طریق لینک زیر با ما در ارتباط باشی:  
[لینک ادمین](http://t.me/maninickroshan)
");
}