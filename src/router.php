<?php

/**
 * Parse a Telegram command and optional parameter.
 * Supports commands addressed to a bot username, e.g. /start@my_bot courses.
 *
 * @return array{0:string,1:?string}
 */
function parseBotCommand(string $message): array
{
    $message = trim($message);
    if ($message === '') {
        return ['', null];
    }

    $parts = preg_split('/\s+/', $message, 2) ?: [];
    $rawCommand = strtolower((string)($parts[0] ?? ''));
    if (preg_match('/^\/[a-z0-9_]+(?:@[a-z0-9_]+)?$/D', $rawCommand) !== 1) {
        return ['', null];
    }
    $command = explode('@', $rawCommand, 2)[0];
    $parameter = isset($parts[1]) && trim($parts[1]) !== ''
        ? strtolower(trim($parts[1]))
        : null;

    return [$command, $parameter];
}

/**
 * Resolve a command/deep-link combination to a logical module name.
 */
function resolveBotModule(string $command, ?string $parameter = null): ?string
{
    if ($command === '/start' && $parameter !== null) {
        return match ($parameter) {
            'support' => 'support',
            'courses' => 'courses',
            'interview' => 'interview',
            default => null,
        };
    }

    return match ($command) {
        '/start' => 'start',
        '/help' => 'help',
        '/courses' => 'courses',
        '/support', '/contact' => 'support',
        '/interview' => 'interview',
        default => null,
    };
}


function commandTargetsBot(string $text, string $username): bool
{
    if (preg_match('/^\/[a-z0-9_]+@([a-z0-9_]+)(?:\s|$)/i', trim($text), $matches) !== 1) {
        return true;
    }
    return strcasecmp($matches[1], $username) === 0;
}

function commandReply(?string $module, string $botUsername = 'acuLearn_bot'): string
{
    $allowed = ['start', 'help', 'courses', 'interview', 'support'];
    if ($module === null || !in_array($module, $allowed, true)) {
        return "متوجه منظورت نشدم. برای دیدن راهنما، دستور /help را بفرست. 🙂";
    }
    if (preg_match('/\\A[A-Za-z][A-Za-z0-9_]{4,31}\\z/D', $botUsername) !== 1) {
        throw new InvalidArgumentException('Invalid Telegram bot username configuration.');
    }

    $reply = require __DIR__ . '/../commands/' . $module . '.php';
    // Only the gateway's own deep links are configurable. Do not rewrite the
    // independent course, interview, or human support bot destinations.
    return str_replace(
        'https://t.me/acuLearn_bot?start=',
        'https://t.me/' . $botUsername . '?start=',
        $reply
    );
}
