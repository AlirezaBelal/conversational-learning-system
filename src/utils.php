<?php

use Medoo\Medoo;

function sendMessage($chat_id, $text) {
    $url = API_URL . "sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true,
    ];
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
    ];
    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);
}

function ensureUserExists($chat_id) {
    global $database;

    $userExists = $database->has("users", ["chat_id" => $chat_id]);

    if (!$userExists) {
        $database->insert("users", [
            "chat_id" => $chat_id,
            "created_at" => Medoo::raw('CURRENT_TIMESTAMP')
        ]);
    }

    $userStateExists = $database->has("user_states", ["chat_id" => $chat_id]);

    if (!$userStateExists) {
        $database->insert("user_states", [
            "chat_id" => $chat_id,
            "state" => 'new_user',
        ]);
    }
}
