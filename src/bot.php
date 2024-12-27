<?php

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use Medoo\Medoo;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

// Define constants
define('API_URL', "https://api.telegram.org/bot" . $_ENV['TELEGRAM_BOT_TOKEN'] . "/");
define('CHARSET', 'utf8mb4');

// Initialize database connection
$database = new Medoo([
    'database_type' => 'mysql',
    'database_name' => $_ENV['DB_DATABASE'],
    'server' => $_ENV['DB_SERVER'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => CHARSET,
]);

// Parse incoming data
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Handle incoming message
if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $message = $update['message']['text'];

    // Insert received message into database
    $database->insert("messages", [
        "chat_id" => $chat_id,
        "message_text" => $message,
        "received_at" => Medoo::raw('CURRENT_TIMESTAMP')
    ]);

    // Command handling
    handleCommand($chat_id, $message);
}

// Function to handle commands
function handleCommand($chat_id, $message)
{
    $commands = [
        '/help' => __DIR__ . '/../commands/help.php',
        '/courses' => __DIR__ . '/../commands/courses.php',
        '/contact' => __DIR__ . '/../commands/contact.php',
        '/interview' => __DIR__ . '/../commands/interview.php',
    ];

    // Check if it's a /start command
    if (strpos($message, '/start') === 0) {
        $params = explode(' ', $message);
        if (isset($params[1])) {
            $command = $params[1];
            // Switch based on the command
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
        // Handle other commands
        if (isset($commands[$message])) {
            require_once $commands[$message];
        } else {
            sendMessage($chat_id, "دستور دریافت کردم: دستور ناشناخته. لطفاً از دستور /help استفاده کنید.");
        }
    }
}

// Function to send a message to Telegram API
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
        CURLOPT_POSTFIELDS => http_build_query($data) // Ensuring correct encoding for POST data
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    // Check for cURL error
    if ($response === false) {
        error_log("Error sending message to Telegram API: " . curl_error($ch));
    } else {
        // Optionally log the response for debugging
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code != 200) {
            error_log("Telegram API response error: HTTP $http_code - $response");
        }
    }

    curl_close($ch);
}