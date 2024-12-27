<?php

class CommandHandler
{
    private $db;
    private $bot;

    public function __construct($db, $bot)
    {
        $this->db = $db;
        $this->bot = $bot;
    }

    // Handle new user registration
    public function handleNewUser($user_id, $username, $phone_number)
    {
        // Save user data to the database
        $this->db->saveUser($user_id, $username, $phone_number);

        // Send a welcome message
        $this->bot->sendMessage($user_id, "Welcome, $username! Your account has been created.");
    }
}
