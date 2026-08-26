<?php

use Medoo\Medoo;

function requiredEnv(string $name): string
{
    $value = trim((string)($_ENV[$name] ?? ''));
    if ($value === '') {
        throw new RuntimeException("Missing required environment variable: {$name}");
    }
    return $value;
}

function sendMessage(int|string $chatId, string $text): bool
{
    $url = API_URL . 'sendMessage';
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlFailed = $response === false;
    curl_close($ch);

    if ($curlFailed || $statusCode < 200 || $statusCode >= 300) {
        error_log('Telegram sendMessage request failed.');
        return false;
    }

    $payload = json_decode((string)$response, true);
    return is_array($payload) && ($payload['ok'] ?? false) === true;
}

function ensureUserExists(array $message): void
{
    global $database;

    $chatId = $message['chat']['id'] ?? null;
    if ($chatId === null) {
        return;
    }

    $from = is_array($message['from'] ?? null) ? $message['from'] : [];
    $profile = [
        'first_name' => $from['first_name'] ?? null,
        'last_name' => $from['last_name'] ?? null,
        'username' => $from['username'] ?? null,
        'language_code' => $from['language_code'] ?? null,
    ];

    if ($database->has('users', ['chat_id' => $chatId])) {
        $database->update('users', $profile, ['chat_id' => $chatId]);
    } else {
        $database->insert('users', array_merge(
            ['chat_id' => $chatId, 'created_at' => Medoo::raw('CURRENT_TIMESTAMP')],
            $profile
        ));
    }

    if (!$database->has('user_states', ['chat_id' => $chatId])) {
        $database->insert('user_states', [
            'chat_id' => $chatId,
            'state' => 'new_user',
        ]);
    }
}

function setUserState(int|string $chatId, string $state): void
{
    global $database;

    if ($database->has('user_states', ['chat_id' => $chatId])) {
        $database->update('user_states', ['state' => $state], ['chat_id' => $chatId]);
        return;
    }

    $database->insert('user_states', [
        'chat_id' => $chatId,
        'state' => $state,
    ]);
}

function saveIncomingMessage(int|string $chatId, string $message): void
{
    global $database;

    $database->insert('messages', [
        'chat_id' => $chatId,
        'message_text' => $message,
        'received_at' => Medoo::raw('CURRENT_TIMESTAMP'),
    ]);
}
