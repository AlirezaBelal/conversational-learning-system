# Conversational Learning System

[![CI](https://github.com/AlirezaBelal/conversational-learning-system/actions/workflows/ci.yml/badge.svg)](https://github.com/AlirezaBelal/conversational-learning-system/actions/workflows/ci.yml)

A stateful Telegram learning gateway for course discovery, guided routing, and learner support, with explicit webhook-authentication and message-retention boundaries.

## Product problem

A learning product often needs a lightweight entry layer that can route learners to the right experience, preserve minimal state, and remain operationally understandable without pretending to be a full LMS. This repository implements that orchestration boundary for Telegram.

## What this repository implements

The AcuLearn Telegram entry point receives webhook updates, persists learner identity/state in MySQL, and routes users into course, interview, and support experiences.

### Implemented capabilities

- Telegram webhook entry point
- `/start`, `/help`, `/courses`, `/interview`, `/support`, and `/contact` routing
- Telegram deep-link routing such as `/start courses`
- Persistent learner profiles and interaction state
- Privacy-minimized interaction logging by default
- Raw incoming-message storage only through explicit opt-in
- Bounded message-retention policy with an executable purge command
- Production fail-closed webhook-secret requirement
- Environment-based credential handling
- MySQL storage through Medoo
- Network-independent router, security, and privacy tests
- Multi-version PHP CI with syntax checks and dependency audit

## Architecture

```text
Telegram user
      ↓
Telegram Bot API / webhook
      ↓
index.php
      ↓
src/bot.php
      ├── environment / secret policy
      ├── webhook authentication
      ├── learner persistence
      ├── privacy-minimized interaction logging
      └── command routing
              ↓
        command modules
        ├── course discovery
        ├── personalized-path entry
        └── learner support entry
              ↓
     dedicated Telegram learning bots

MySQL
├── users
├── user_states
└── messages
```

## Learner flow

1. A learner opens the bot or uses a Telegram deep link.
2. Telegram sends the update to the HTTPS webhook.
3. Production requests must pass the configured Telegram webhook-secret check.
4. The learner profile is created or refreshed in MySQL.
5. The interaction is recorded using the configured privacy policy; raw text is off by default.
6. The router resolves the command or deep-link parameter.
7. Learner state is updated to the selected module.
8. The bot returns the relevant course, interview, or support destination.

Unsupported commands are handled explicitly and direct the learner back to `/help`.

## Commands

| Command | Purpose |
| --- | --- |
| `/start` | Main onboarding menu |
| `/help` | Available routes and support guidance |
| `/courses` | Course discovery |
| `/interview` | Personalized-learning path entry |
| `/support` | Learner support route |
| `/contact` | Alias for learner support |

Telegram deep links are also supported for `courses`, `interview`, and `support` through `/start <parameter>`.

## Requirements

- PHP 8.0+
- Composer
- MySQL or MariaDB compatible with the included schema
- PHP cURL extension
- Telegram Bot API token

## Local setup

```bash
composer install
cp config/.env.example config/.env
```

Fill in the local database and Telegram settings in `config/.env`. Never commit the populated file.

Initialize a new database:

```bash
mysql -u <user> -p < bot_database.sql
```

Point a web server at the repository and expose `index.php` over HTTPS.

### Production webhook authentication

Development can run with `APP_ENV=development`. For a deployed environment use:

```text
APP_ENV=production
TELEGRAM_WEBHOOK_SECRET=<strong-random-secret>
```

When `APP_ENV=production`, the application refuses to start without `TELEGRAM_WEBHOOK_SECRET`. Register the Telegram webhook using the same secret token. Requests without the matching `X-Telegram-Bot-Api-Secret-Token` value are rejected.

## Privacy and retention

The default configuration is intentionally data-minimizing:

```text
STORE_RAW_MESSAGES=false
MESSAGE_RETENTION_DAYS=30
```

With `STORE_RAW_MESSAGES=false`, the application stores a redacted marker containing only message byte length instead of the incoming text itself. Set `STORE_RAW_MESSAGES=true` only when a documented product/operational need justifies retaining raw text.

Retention is enforced by an explicit purge command:

```bash
php scripts/purge_messages.php
```

Run this command from cron, a scheduler, or the deployment platform at least daily. `MESSAGE_RETENTION_DAYS` accepts values from 1 through 3650 and defaults to 30.

Existing deployments that previously stored raw text should apply their own migration/cleanup plan before relying on the new default.

## Database model

### `users`
Stores the Telegram chat identifier and the latest available learner profile fields.

### `user_states`
Stores the learner's latest routed module such as `start`, `courses`, `interview`, or `support`.

### `messages`
Stores the privacy-policy output plus a receive timestamp. By default the stored value is redacted rather than the original message text.

The SQL file represents the schema for a fresh installation. Existing deployments should use explicit migrations rather than re-running schema changes blindly.

## Testing

Run all network-independent tests:

```bash
for test_file in tests/*_test.php; do php "$test_file"; done
```

Lint application PHP files:

```bash
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

GitHub Actions runs Composer validation, dependency installation, PHP syntax checks, router/security/privacy tests, tracked-token regression checks, privacy-default checks, and dependency auditing across PHP 8.0–8.4.

## Security

Runtime credentials belong only in `config/.env` or the deployment platform's secret store. `config/.env.example` contains placeholders only.

If a real database password, Telegram bot token, webhook secret, or other credential has ever appeared in a public commit, log, or chat, rotate it. Removing it from the latest file does not invalidate copies that may remain in Git history or external caches.

See `SECURITY.md` for the repository's credential-handling, webhook, and privacy guidance.

## Scope and interpretation

This project demonstrates conversational product routing, state management, Telegram integration, learner-flow orchestration, privacy minimization, retention policy, and backend persistence. It does **not** claim that this repository contains an LLM, an autonomous tutor, or a complete learning-management system.

The Persian conversational copy in `commands/` is product content used by the Telegram experience; the implementation and architecture remain framework-light PHP.
