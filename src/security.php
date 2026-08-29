<?php

function validateWebhookSecretConfiguration(string $appEnv, string $webhookSecret): void
{
    $environment = strtolower(trim($appEnv));
    if ($environment === 'production' && trim($webhookSecret) === '') {
        throw new RuntimeException('TELEGRAM_WEBHOOK_SECRET is required when APP_ENV=production.');
    }
}

function webhookSecretMatches(string $configuredSecret, string $providedSecret): bool
{
    $configuredSecret = trim($configuredSecret);
    if ($configuredSecret === '') {
        return true;
    }

    return $providedSecret !== '' && hash_equals($configuredSecret, $providedSecret);
}
