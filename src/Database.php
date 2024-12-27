<?php

class Database
{
    private $conn;

    public function __construct()
    {
        $this->conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    // Insert or update user
    public function saveUser($user_id, $username, $phone_number = null)
    {
        $stmt = $this->conn->prepare("INSERT INTO users (user_id, username, phone_number) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE username = ?, phone_number = ?");
        $stmt->bind_param("issss", $user_id, $username, $phone_number, $username, $phone_number);
        $stmt->execute();
        $stmt->close();
    }
}
