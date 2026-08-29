<?php

require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/privacy.php';

function assertSameValueStrict(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAILED: {$label}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertThrowsRuntime(callable $callback, string $label): void
{
    try {
        $callback();
    } catch (RuntimeException) {
        return;
    }

    fwrite(STDERR, "FAILED: {$label}\nExpected RuntimeException.\n");
    exit(1);
}

validateWebhookSecretConfiguration('development', '');
validateWebhookSecretConfiguration('production', 'configured-secret');
assertThrowsRuntime(
    fn() => validateWebhookSecretConfiguration('production', ''),
    'production requires webhook secret'
);
assertSameValueStrict(true, webhookSecretMatches('', ''), 'development-compatible empty secret');
assertSameValueStrict(true, webhookSecretMatches('secret', 'secret'), 'matching webhook secret');
assertSameValueStrict(false, webhookSecretMatches('secret', ''), 'missing webhook header');
assertSameValueStrict(false, webhookSecretMatches('secret', 'wrong'), 'incorrect webhook header');

unset($_ENV['STORE_RAW_MESSAGES'], $_ENV['MESSAGE_RETENTION_DAYS']);
assertSameValueStrict('[redacted:bytes=5]', messageStorageValue('hello'), 'raw messages disabled by default');
assertSameValueStrict(30, messageRetentionDays(), 'default retention');

$_ENV['STORE_RAW_MESSAGES'] = 'true';
assertSameValueStrict('hello', messageStorageValue('hello'), 'raw message storage is explicit opt-in');
$_ENV['STORE_RAW_MESSAGES'] = 'false';

$_ENV['MESSAGE_RETENTION_DAYS'] = '90';
assertSameValueStrict(90, messageRetentionDays(), 'configured retention');
$_ENV['MESSAGE_RETENTION_DAYS'] = '0';
assertThrowsRuntime(fn() => messageRetentionDays(), 'zero-day retention rejected');
$_ENV['MESSAGE_RETENTION_DAYS'] = 'forever';
assertThrowsRuntime(fn() => messageRetentionDays(), 'non-integer retention rejected');

fwrite(STDOUT, "Security and privacy tests passed.\n");
