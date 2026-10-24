<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/utils.php';
require_once __DIR__ . '/../src/processor.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
try {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/../config')->safeLoad();
    $counts = purgeLearnerMessages(openLearningDatabase(), messageRetentionDays());
    fwrite(STDOUT, "Purged {$counts['messages']} message row(s) and {$counts['update_receipts']} update receipt(s).\n");
} catch (Throwable) {
    fwrite(STDERR, "Retention cleanup failed; check configuration, schema and database access.\n");
    exit(1);
}
