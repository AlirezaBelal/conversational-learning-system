# Conversational Learning System

[![CI](https://github.com/AlirezaBelal/conversational-learning-system/actions/workflows/ci.yml/badge.svg)](https://github.com/AlirezaBelal/conversational-learning-system/actions/workflows/ci.yml)

A stateful Telegram learning gateway for course discovery, guided routing, and learner support.

## What this repository implements

This repository contains the main AcuLearn Telegram entry point. It receives Telegram webhook updates, persists learner activity and state in MySQL, and routes users into the appropriate learning experience.

The current implementation focuses on orchestration rather than pretending to be a full LMS. Specialized course, interview, and support experiences are reached through dedicated Telegram bots linked from this gateway.

### Implemented capabilities

- Telegram webhook entry point
- `/start`, `/help`, `/courses`, `/interview`, `/support`, and `/contact` routing
- Telegram deep-link routing such as `/start courses`
- Persistent learner profiles and interaction state
- Incoming text-message logging
- Course discovery and guided-learning entry points
- Optional Telegram webhook secret verification
- Environment-based credential handling
- MySQL storage through Medoo
- Network-independent command-router tests
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
      ├── request validation
      ├── webhook-secret verification
      ├── learner persistence
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

The main bot acts as a navigation and state-management layer:

1. A learner opens the bot or uses a Telegram deep link.
2. The webhook receives the update.
3. The learner profile is created or refreshed in MySQL.
4. The incoming text message is recorded.
5. The router resolves the command or deep-link parameter.
6. The learner state is updated to the selected module.
7. The bot returns the relevant course, interview, or support destination.

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

Install dependencies:

```bash
composer install
```

Create local configuration:

```bash
cp config/.env.example config/.env
```

Fill in the local database and Telegram settings in `config/.env`. Never commit the populated file.

Initialize a new database using:

```bash
mysql -u <user> -p < bot_database.sql
```

Point a web server at the repository and expose `index.php` over HTTPS. Register that HTTPS endpoint as the Telegram webhook URL.

For stronger webhook authentication, set `TELEGRAM_WEBHOOK_SECRET` locally and configure the same secret when registering the Telegram webhook. Requests without the matching Telegram secret header will then be rejected.

## Database model

### `users`
Stores the Telegram chat identifier and the latest available learner profile fields.

### `user_states`
Stores the learner's latest routed module such as `start`, `courses`, `interview`, or `support`.

### `messages`
Stores incoming text interactions with timestamps for operational traceability and learning-flow analysis.

The SQL file represents the schema for a fresh installation. Existing deployments should use an explicit migration rather than re-running schema changes blindly.

## Testing

Run the network-independent router test:

```bash
php tests/router_test.php
```

Lint application PHP files:

```bash
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

GitHub Actions runs Composer validation, dependency installation, PHP syntax checks, router tests, and dependency auditing across multiple supported PHP versions.

## Security

Runtime credentials belong only in `config/.env` or the deployment platform's secret store. `config/.env.example` contains placeholders only.

If a real database password, Telegram bot token, or other credential has ever appeared in a public commit, log, or chat, rotate it. Removing it from the latest file does not invalidate copies that may remain in Git history or external caches.

See `SECURITY.md` for the repository's credential-handling and webhook guidance.

## Scope and interpretation

This project demonstrates conversational product routing, state management, Telegram integration, learner-flow orchestration, and backend persistence. It does **not** claim that this repository contains an LLM, an autonomous tutor, or a complete learning-management system.

The Persian conversational copy in `commands/` is product content used by the Telegram experience; the implementation and architecture remain framework-light PHP.
