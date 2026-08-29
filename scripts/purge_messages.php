<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/utils.php';
require_once __DIR__ . '/../src/privacy.php';

use Dotenv\Dotenv;
use Medoo\Medoo;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->safeLoad();

$database = new Medoo([
    'database_type' => 'mysql',
    'database_name' => requiredEnv('DB_DATABASE'),
    'server' => requiredEnv('DB_SERVER'),
    'username' => requiredEnv('DB_USERNAME'),
    'password' => requiredEnv('DB_PASSWORD'),
    'charset' => 'utf8mb4',
]);

$retentionDays = messageRetentionDays();
$cutoff = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
    ->modify("-{$retentionDays} days")
    ->format('Y-m-d H:i:s');

$statement = $database->delete('messages', [
    'received_at[<]' => $cutoff,
]);

$count = $statement->rowCount();
fwrite(STDOUT, "Purged {$count} message row(s) older than {$retentionDays} day(s).\n");
