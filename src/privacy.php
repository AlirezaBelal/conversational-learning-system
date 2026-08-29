<?php

function envFlag(string $name, bool $default = false): bool
{
    $raw = strtolower(trim((string)($_ENV[$name] ?? '')));
    if ($raw === '') {
        return $default;
    }

    if (in_array($raw, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }

    if (in_array($raw, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }

    throw new RuntimeException("Invalid boolean environment variable: {$name}");
}

function messageStorageValue(string $message): string
{
    if (envFlag('STORE_RAW_MESSAGES', false)) {
        return $message;
    }

    return '[redacted:bytes=' . strlen($message) . ']';
}

function messageRetentionDays(): int
{
    $raw = trim((string)($_ENV['MESSAGE_RETENTION_DAYS'] ?? '30'));
    if ($raw === '' || !ctype_digit($raw)) {
        throw new RuntimeException('MESSAGE_RETENTION_DAYS must be an integer between 1 and 3650.');
    }

    $days = (int)$raw;
    if ($days < 1 || $days > 3650) {
        throw new RuntimeException('MESSAGE_RETENTION_DAYS must be an integer between 1 and 3650.');
    }

    return $days;
}
