# Security

## Secrets

Do not commit live credentials to this repository.

Runtime values such as the Telegram bot token, database password, webhook secret, and administrator identifiers belong in `config/.env` or the deployment platform's secret store. The tracked `config/.env.example` file must contain placeholders only.

If a real credential has ever been committed, copied into a public log, or shared through an insecure channel, rotate or revoke it. Removing the value from the current branch is not sufficient because Git history and external caches may retain older copies.

## Telegram webhook

Production deployments are fail-closed:

1. Set `APP_ENV=production`.
2. Configure a strong `TELEGRAM_WEBHOOK_SECRET` outside source control.
3. Register the Telegram webhook using the same secret token.
4. Requests without the matching `X-Telegram-Bot-Api-Secret-Token` header are rejected with HTTP 403.
5. If the production secret is missing, application startup fails rather than accepting unauthenticated webhook traffic.
6. Serve the webhook over HTTPS only.

Development environments may omit the secret for local testing, but that mode must not be used for public production traffic.

## Data minimization and retention

Incoming message text is **not retained by default**. With `STORE_RAW_MESSAGES=false`, the database stores a redacted marker containing the message byte length rather than the original content.

Only enable `STORE_RAW_MESSAGES=true` when there is a documented product or operational need, an appropriate privacy notice, and a justified retention period.

`MESSAGE_RETENTION_DAYS` defaults to 30 days and is bounded to 1–3650. Enforce the lifecycle by scheduling:

```bash
php scripts/purge_messages.php
```

Existing deployments that previously stored raw messages should separately purge or migrate those historical rows according to their applicable privacy requirements.

## Logging

Avoid logging raw request URLs that contain credentials. Application error logging should remain generic and must not include the Telegram bot token, database password, webhook secret, full request body, or full secret-bearing environment values.

## Database

Use a dedicated database user with only the permissions required by this application. Do not reuse administrative database credentials for the bot.

The included `bot_database.sql` is intended for a fresh installation. Apply explicit migrations to existing production databases rather than replacing schemas directly.

## Reporting

For a privately discovered vulnerability, contact the repository owner directly rather than publishing credentials or exploit details in a public issue.
