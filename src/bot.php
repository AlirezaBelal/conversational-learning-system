<?php

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use Medoo\Medoo;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

define('API_URL', "https://api.telegram.org/bot" . $_ENV['TELEGRAM_BOT_TOKEN'] . "/");
define('CHARSET', 'utf8mb4');
define('AI_API_URL', 'https://api.metisai.ir/api/session');
define('AI_API_KEY', '8b7fdeec-fe02-4484-b85b-33c8fce34005');
define('AI_SESSION_ID', 'your-session-id');

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
    $message = $update['message']['text'];

    $database->insert("messages", [
        "chat_id" => $chat_id,
        "message_text" => $message,
        "received_at" => Medoo::raw('CURRENT_TIMESTAMP')
    ]);

    handleUserState($chat_id, $message);
    handleCommand($chat_id, $message);
}

function handleUserState($chat_id, $message)
{
    global $database;

    $userState = $database->get("user_states", "state", ["chat_id" => $chat_id]);

    if ($userState === "in_chat") {
        if (strpos($message, '/stop') === 0) {
            $updateResult = $database->update("user_states", ["state" => "new_user"], ["chat_id" => $chat_id]);

            if ($updateResult->rowCount() > 0) {
                sendMessage($chat_id, "چت شما با هوش مصنوعی متوقف شد. اگر نیاز به کمک دارید، دوباره از /contact استفاده کنید.");
            } else {
                sendMessage($chat_id, "متاسفانه مشکلی در متوقف کردن چت به وجود آمده است.");
            }
        } else {
            $response = sendMessageToAI($message);
            sendMessage($chat_id, $response);
        }
        return;
    }

    if (strpos($message, '/contact') === 0) {
        $database->update("user_states", ["state" => "in_chat"], ["chat_id" => $chat_id]);
        sendMessage($chat_id, "شما وارد چت با هوش مصنوعی شدید. می‌توانید پیام خود را ارسال کنید.");
        return;
    }

    if (strpos($message, '/stop') === 0) {
        sendMessage($chat_id, "شما در حال حاضر در چت با هوش مصنوعی نیستید.");
        return;
    }
}

function sendMessageToAI($message)
{
    $data = [
        'session_id' => AI_SESSION_ID,
        'message' => $message,
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => AI_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . AI_API_KEY,
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return "متاسفانه مشکلی در ارتباط با هوش مصنوعی پیش آمد.";
    }

    $response_data = json_decode($response, true);
    curl_close($ch);

    if (isset($response_data['response'])) {
        return $response_data['response'];
    } else {
        return "متاسفانه مشکلی در دریافت پاسخ از هوش مصنوعی پیش آمد.";
    }
}

function handleCommand($chat_id, $message)
{
    global $database;

    $userState = $database->get("user_states", "state", ["chat_id" => $chat_id]);

    if ($userState === 'in_chat') {
        return;
    }

    $commands = [
        '/help' => __DIR__ . '/../commands/help.php',
        '/courses' => __DIR__ . '/../commands/courses.php',
        '/contact' => __DIR__ . '/../commands/contact.php',
        '/interview' => __DIR__ . '/../commands/interview.php',
    ];

    if (strpos($message, '/start') === 0) {
        $params = explode(' ', $message);
        if (isset($params[1])) {
            $command = $params[1];
            switch ($command) {
                case 'help':
                case 'courses':
                case 'contact':
                    require_once __DIR__ . '/../commands/' . $command . '.php';
                    break;
                case 'interview':
                    require_once __DIR__ . '/../commands/interview.php';
                    break;
                default:
                    sendMessage($chat_id, "پارامتر نامعتبر است. لطفاً از دستور /help استفاده کنید.");
                    break;
            }
        } else {
            require_once __DIR__ . '/../commands/start.php';
        }
    } else {
        if (isset($commands[$message])) {
            require_once $commands[$message];
        } else {
            sendMessage($chat_id, "دستور دریافت کردم: دستور ناشناخته. لطفاً از دستور /help استفاده کنید.");
        }
    }
}

function sendMessage($chat_id, $text)
{
    $url = API_URL . "sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true
    ];

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data)
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    if ($response === false) {
        error_log("Error sending message to Telegram API: " . curl_error($ch));
    } else {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200) {
            error_log("Telegram API response error: HTTP $http_code - $response");
        }
    }

    curl_close($ch);
}