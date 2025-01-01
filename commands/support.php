<?php

function handleContactCommand($chat_id)
{
    global $database;

    $updateResult = $database->update("user_states", ["state" => "in_chat"], ["chat_id" => $chat_id]);

    if ($updateResult->rowCount() > 0) {
        sendMessage($chat_id, "شما وارد چت با هوش مصنوعی شدید. می‌توانید پیام خود را ارسال کنید.");
    } else {
        sendMessage($chat_id, "متاسفانه مشکلی در شروع چت به وجود آمده است.");
    }
}
