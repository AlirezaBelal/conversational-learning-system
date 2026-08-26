# Security

## Secrets

Do not commit live credentials to this repository.

Runtime values such as the Telegram bot token, database password, webhook secret, and administrator identifiers belong in `config/.env` or the deployment platform's secret store. The tracked `config/.env.example` file must contain placeholders only.

If a real credential has ever been committed, copied into a public log, or shared through an insecure channel, rotate or revoke it. Removing the value from the current branch is not sufficient because Git history and external caches may retain older copies.

## Telegram webhook

The application supports Telegram's webhook secret header through `TELEGRAM_WEBHOOK_SECRET`.

When this value is configured:

1. Register the Telegram webhook with the same secret token.
2. Keep the secret outside source control.
3. Requests without the matching `X-Telegram-Bot-Api-Secret-Token` header are rejected with HTTP 403.
4. Serve the webhook over HTTPS only.

## Logging

Avoid logging raw request URLs that contain credentials. Application error logging should remain generic and must not include the Telegram bot token, database password, webhook secret, or full secret-bearing environment values.

## Database

Use a dedicated database user with only the permissions required by this application. Do not reuse administrative database credentials for the bot.

The included `bot_database.sql` is intended for a fresh installation. Apply explicit migrations to existing production databases rather than replacing schemas directly.

## Reporting

For a privately discovered vulnerability, contact the repository owner directly rather than publishing credentials or exploit details in a public issue.
