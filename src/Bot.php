<?php

class Bot
{
    private $telegram;

    public function __construct($token)
    {
        $this->telegram = new \Telegram\Bot\Api($token);
    }

    // Send a message to the user
    public function sendMessage($chat_id, $message)
    {
        $this->telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $message
        ]);
    }

    // Handle the incoming updates (messages, commands)
    public function handleUpdate($update)
    {
        $message = $update->getMessage();
        $chat_id = $message->getChat()->getId();
        $text = $message->getText();

        // Handle commands
        if ($text == '/help') {
            $this->sendMessage($chat_id, "Here are the available commands:\n/help - Show this help\n/courses - View courses\n/quiz - Start quiz\n/contact - Contact support");
        } elseif ($text == '/courses') {
            $this->sendMessage($chat_id, "Here is the list of available courses:\n1. Digital Product Management\n2. UX/UI Design\n3. Product Growth\n... (more courses)");
        } elseif ($text == '/quiz') {
            $this->sendMessage($chat_id, "Starting the quiz...");
            // Add quiz logic here
        } elseif ($text == '/contact') {
            $this->sendMessage($chat_id, "You can contact support at support@example.com");
        } else {
            $this->sendMessage($chat_id, "Unknown command. Type /help for a list of commands.");
        }
    }
}