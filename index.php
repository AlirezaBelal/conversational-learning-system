<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Bot.php';
require_once __DIR__ . '/src/CommandHandler.php';

// Create instances of the Bot and Database classes
$database = new Database();
$bot = new Bot(TELEGRAM_BOT_TOKEN);

// Get the incoming request
$update = json_decode(file_get_contents('php://input'));

if ($update) {
    // Process the update (incoming message)
    $commandHandler = new CommandHandler($database, $bot);

    // Get user details (user_id, username, phone_number, etc.)
    $user_id = $update->message->from->id;
    $username = $update->message->from->username;
    $phone_number = $update->message->contact->phone_number ?? null;

    // Save user details in the database
    $commandHandler->handleNewUser($user_id, $username, $phone_number);

    // Handle the incoming message
    $bot->handleUpdate($update);
}
