<?php
// بارگذاری فایل .env برای تنظیمات
require 'vendor/autoload.php'; // اگر از Composer استفاده می‌کنید

use Dotenv\Dotenv;
use Medoo\Medoo;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// توکن تلگرام و تنظیمات دیتابیس از فایل .env
$telegramToken = $_ENV['TELEGRAM_BOT_TOKEN'];
$chat_id = ''; // آیدی چت به صورت داینامیک از پیام‌ها دریافت می‌شود

// تنظیمات API تلگرام
define('API_URL', "https://api.telegram.org/bot" . $telegramToken . "/");

// تنظیمات دیتابیس با استفاده از Medoo
$database = new Medoo([
    'database_type' => 'mysql',
    'database_name' => $_ENV['DB_DATABASE'],
    'server' => $_ENV['DB_SERVER'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD']
]);

// بررسی دریافت پیام از تلگرام
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// چک کردن وجود پیام جدید
if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $message = $update['message']['text'];

    // ذخیره اطلاعات پیام در دیتابیس با Medoo
    $database->insert("messages", [
        "chat_id" => $chat_id,
        "message_text" => $message,
        "received_at" => date("Y-m-d H:i:s")
    ]);

    // پاسخ به دستورات مختلف
    switch ($message) {
        case '/help':
            sendMessage($chat_id, "دستور دریافت کردم: شما از ربات کمک خواسته‌اید.");
            break;

        case '/courses':
            sendMessage($chat_id, "دستور دریافت کردم: شما فهرست دوره‌ها را درخواست کرده‌اید.");
            break;

        case '/quiz':
            sendMessage($chat_id, "دستور دریافت کردم: شما آزمون دانش را درخواست کرده‌اید.");
            break;

        case '/contact':
            sendMessage($chat_id, "دستور دریافت کردم: شما درخواست پشتیبانی کرده‌اید.");
            break;

        default:
            sendMessage($chat_id, "دستور دریافت کردم: دستور ناشناخته.");
            break;
    }
}

// تابع برای ارسال پیام به تلگرام
function sendMessage($chat_id, $text)
{
    $url = API_URL . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($text);
    file_get_contents($url);
}
