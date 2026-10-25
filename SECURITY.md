# Security

The GitHub repository is named **telegram-learning-gateway**. Renaming the
source repository does not change Telegram BotFather usernames, webhook
secrets, token validity, database credentials or live deployment URLs.
Operators must independently update hard-coded clone/CI links and verify
webhook registrations in their own environments.

Expose only `public/` over HTTPS with PHP error display disabled. Never serve the
repository root, config/.env, SQL dumps, internal scripts, dependencies or logs.
Technical diagnostics are intentionally generic and contain no raw update bodies,
SQL errors, Telegram response bodies, bot-token URLs or configured secret values.

Production is the default. Configure a random `TELEGRAM_WEBHOOK_SECRET` matching
Telegram's secret-token format and use the same value when registering the webhook.
Missing configuration fails closed; missing/incorrect request headers return 403.
Explicit unauthenticated development is restricted to an isolated loopback listener;
never enable it behind a public proxy or tunnel. See README for request limits.

Only private learner chats are processed when Telegram includes a chat type;
group, supergroup and channel messages are ignored without storing state.
A missing chat type remains accepted for compatibility with older fixtures.
The gateway username is syntax-validated before placing its own deep links
in replies; unrelated course/support destinations remain explicitly fixed.

New requests retain chat ID and route state, not Telegram profile names/usernames.
Raw text is off by default. Message byte-length metadata and update receipts still
have a lifecycle: run `php scripts/purge_messages.php` daily, monitor its exit code,
and separately define erasure of learner state and legacy profile/raw-message data.
A retention setting without an executed purge is not automatic deletion.

Delivery is not exact-once. A Telegram acknowledgement followed by a failed commit,
or a lost acknowledgement, can result in a duplicate reply on redelivery. Failed
processing rolls back learner writes and receipt; committed receipts suppress replay
until expiry. Use InnoDB, a minimally privileged runtime account and separate schema
credentials. Test migrations/backup recovery before modifying existing installations.

Do not publish credentials or sensitive updates in an issue. Report a vulnerability
privately through GitHub's private reporting option if available, or ask the owner
for a private channel without disclosing details. Rotate/revoke exposed credentials;
removal from the latest revision does not invalidate Git history or external copies.
No regulatory-compliance or independently audited production-security claim is made.
