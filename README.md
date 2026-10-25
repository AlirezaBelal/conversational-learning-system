# Conversational Learning System

[![CI](https://github.com/AlirezaBelal/conversational-learning-system/actions/workflows/ci.yml/badge.svg)](https://github.com/AlirezaBelal/conversational-learning-system/actions/workflows/ci.yml)

A small Telegram learning gateway: Persian menus, course-bot links, deep links and
minimal learner state. It is not an LMS, AI tutor or autonomous support service.
The linked bots provide their own experiences; their continued operation is not
verified by this repository's tests. No live bot is required to test this source.

## Requirements and setup

PHP 8.0–8.4 (64-bit), Composer, cURL, PDO MySQL and an InnoDB MySQL database.

```bash
composer install --no-interaction
cp config/.env.example config/.env
mysql -u <schema-admin> -p < bot_database.sql
```

Fill in the database credentials, bot token and a random webhook secret in
`config/.env`, outside the public document root. `DB_PORT` defaults to 3306.
`TELEGRAM_BOT_USERNAME` must match the gateway bot's actual username, without `@`.
Composer's committed lockfile targets PHP 8.0 compatibility; CI installs the same
versions on every supported runtime. No server or webhook is registered by setup.

For an **existing installation**, back up first and run
`migrations/001_update_receipts.sql` against the existing database before deploying
this code. It adds a table and does not alter historical rows. Verify that users,
user_states and messages already use InnoDB; transactions cannot protect MyISAM
tables. Convert legacy engines in a separately planned maintenance window, not
implicitly at application startup. The optional retention index is documented in
the migration; check `SHOW INDEX FROM messages` before adding it.

## Secure web entry point

Expose **only `public/`**, never the repository root. The root `index.php` remains
for compatibility, not as a recommendation to expose config, SQL, scripts or logs.
Disable PHP `display_errors` and directory listing in production; configure TLS.

Minimal Nginx routing inside your HTTPS server configuration:

```nginx
root /srv/learning-gateway/public;
client_max_body_size 1m;
location = /index.php {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
}
location / { return 404; }
```

Adjust the installed FPM socket and TLS settings. For Apache, set `DocumentRoot`
to the same `public/` directory, configure PHP/FPM, and use:

```apache
<Directory /srv/learning-gateway/public>
    Options -Indexes
    AllowOverride None
    Require all granted
</Directory>
```

Do not add aliases that expose the parent directory. Only `/index.php` needs to be
registered as the webhook URL, over HTTPS, using Telegram's `secret_token` option.
Do not put the secret in a URL. Registration is an operator action, not part of CI.

## Authentication and request handling

`APP_ENV=production` is the default, even if omitted. A missing secret fails closed.
Requests must use POST and match `X-Telegram-Bot-Api-Secret-Token` with constant-time
comparison. Invalid credentials return 403, other methods 405, oversized bodies
413 and malformed JSON/update identifiers 400, before a database connection.
The application reads at most 1 MiB plus one byte and rejects deeper malformed JSON.

Unauthenticated development requires **both** `APP_ENV=development` and
`ALLOW_UNAUTHENTICATED_LOCAL_WEBHOOK=true`, and a loopback peer address. This is
only for an isolated local listener, never a public tunnel or reverse proxy. The
application does not trust forwarded IP headers. A secret is simpler and safer.

Unsupported updates and messages without text are acknowledged without storage.
**Only private chats** with the gateway are processed: known group, supergroup
and channel chat types are ignored without persisting learner state. Messages
without a chat type retain backward compatibility with older integrations.
Commands addressed to another bot are ignored. Supported text in message or
edited_message updates follows the same routing contract with a distinct update ID.

## Learner routing

| Command | Destination |
| --- | --- |
| `/start` | Gateway menu |
| `/help` | Command help |
| `/courses` | Course-specific Telegram bots |
| `/interview` | External path-selection bot |
| `/support`, `/contact` | External support destination |

`/start courses`, `/start interview` and `/start support` support deep links.
Unknown commands receive a short Persian `/help` hint, preserve an existing route,
and initialize a new learner as `new_user`.
Static Persian responses are in the five `commands/` modules. Markdown is static;
user text is never interpolated into replies. The gateway's **own** `/start`
deep links in the start/help copy now use the configured, syntax-validated
`TELEGRAM_BOT_USERNAME`; changing that variable no longer requires editing
these modules. Independent course, interview and support bot links remain fixed,
so operators must review their ownership and availability separately.
External bot availability is not guaranteed.

## Persistence, deduplication and delivery

Medoo initializes the existing PDO connection; a small processing function receives
PDO and a delivery callback explicitly. Tests substitute the callback, not Telegram.

An InnoDB transaction inserts a unique update_id receipt, creates the chat row,
records privacy-minimized text metadata and updates a recognized route state.
Only then is a Telegram reply attempted, with 5-second connect and 15-second total
timeouts. Transport/HTTP errors and invalid/API-rejected responses are failures.
Successful delivery commits the transaction; failure rolls it back and returns
503 so Telegram can retry. An already committed receipt returns 200 without
writing or sending again. Database failures are not reported as success.

This is **not exact-once messaging**: a response may reach Telegram before a lost
acknowledgement or failed database commit. Retrying that ambiguous request can
repeat the reply. There is no local retry loop, worker or external queue. A duplicate
in flight may wait for the first transaction; keep transactions and timeouts bounded.

## Privacy and retention

Only chat ID, route state, timestamps and message metadata are needed. New requests
do not copy Telegram names, usernames or language into profile columns. Legacy
profile columns/data remain untouched; review historical cleanup separately.

Defaults remain `STORE_RAW_MESSAGES=false` and `MESSAGE_RETENTION_DAYS=30`.
Stored text is then `[redacted:bytes=N]`, not the original message. Byte length can
still reveal message size; enable raw storage only for a justified, disclosed need.

```bash
php scripts/purge_messages.php
```

Schedule this CLI command at least daily; configuration alone deletes nothing.
It purges old messages and receipts atomically, reports counts, and exits nonzero
on failure. The retention window (1–3650 days) also bounds deduplication: a replay
after its receipt has been purged can run again. Database timestamps use UTC.
Chat/state rows remain until an operator erases the learner, for example with a
prepared `DELETE FROM users WHERE chat_id = ?`; foreign keys delete related states
and messages. Receipts contain only update IDs/timestamps and expire independently.
This is data minimization, not a claim of regulatory compliance.

Use a dedicated runtime database user with SELECT/INSERT/UPDATE/DELETE on these
tables. Use a separate schema account for installation/migration and test DDL.

## Offline verification

```bash
composer install --no-interaction
for test in tests/*_test.php; do php "$test" || exit 1; done
```

Router, auth/JSON/size/shape, Persian reply and mocked delivery checks need no bot
credentials or database. The MySQL test explicitly reports a skip unless configured.
For real persistence checks, create an **isolated** `learning_gateway_test` database:

```bash
export TEST_DATABASE_DSN='mysql:host=127.0.0.1;dbname=learning_gateway_test;charset=utf8mb4'
export TEST_DATABASE_USER='<test-user>'
export TEST_DATABASE_PASSWORD='<test-password>'
REQUIRE_DATABASE_TEST=true php tests/database_test.php
```

Use only synthetic data. The test creates tables, exercises replay/rollback/retention
and removes its synthetic rows. Never point it at production. CI runs these checks
with an isolated MySQL 8.0 service on PHP 8.0–8.4, plus syntax, Composer validation,
locked installation, dependency audit and secret-pattern checks. No test sends a
Telegram request. Real hosted Telegram/FPM behavior still requires operator testing.

## Security and license

See [SECURITY.md](SECURITY.md). Keep tokens, database passwords, raw updates and
secret-bearing URLs out of commits/logs. Revoke exposed credentials at their provider;
deleting a file does not revoke history copies. Existing code remains [MIT](LICENSE).
