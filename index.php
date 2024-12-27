<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;
use Medoo\Medoo;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$telegramToken = $_ENV['TELEGRAM_BOT_TOKEN'];
define('API_URL', "https://api.telegram.org/bot" . $telegramToken . "/");

$database = new Medoo([
    'database_type' => 'mysql',
    'database_name' => $_ENV['DB_DATABASE'],
    'server' => $_ENV['DB_SERVER'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8mb4',
]);

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $message = $update['message']['text'];

    $database->insert("messages", [
        "chat_id" => $chat_id,
        "message_text" => $message,
        "received_at" => date("Y-m-d H:i:s")
    ]);

    switch ($message) {
        case '/start':
            sendMessage($chat_id, "
لطفا از دستورات زیر استفاده کنید:
/help - دریافت راهنمایی
/courses - مشاهده دوره‌های موجود
/quiz - شروع آزمون دانش
/contact - تماس با پشتیبانی
");
            break;

        case '/help':
            sendMessage($chat_id, "
سلام! به ربات خوش آمدید. در اینجا دستورات موجود برای شما آورده شده است:

/help - دریافت راهنمایی
/courses - مشاهده دوره‌های آموزشی
/quiz - شروع آزمون
/contact - تماس با پشتیبانی
");
            break;

        case '/courses':
            sendMessage($chat_id, "
در حال حاضر، ما سه دوره آموزشی داریم:

1. **هوش مصنوعی در خدمت مدیران محصول**  
   [لینک به دوره](https://bozhan.school/courses/aiforpms/)

2. **محصول و کسب‌وکار برای متخصصین منابع انسانی**  
   [لینک به دوره](https://bozhan.school/courses/businessforhr/)

3. **دوره مدیریت محصول دیجیتال**  
   [لینک به دوره](https://bozhan.school/courses/productmanagement/)
");
            break;

        case '/quiz':
            sendMessage($chat_id, "دستور دریافت کردم: شما آزمون دانش را درخواست کرده‌اید.");
            break;

        case '/contact':
            sendMessage($chat_id, "
برای دریافت پشتیبانی و کمک بیشتر، لطفاً با تیم پشتیبانی تماس بگیرید:

🔹 **پشتیبانی تلگرام:** [@maninickroshan](https://t.me/maninickroshan)
");
            break;

        default:
            sendMessage($chat_id, "دستور دریافت کردم: دستور ناشناخته. لطفاً از دستور `/help` استفاده کنید.");
            break;
    }
}

function sendMessage($chat_id, $text)
{
    $url = API_URL . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($text);
    file_get_contents($url);
}