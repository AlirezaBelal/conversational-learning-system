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

function commandReply(?string $module): string
{
    $allowed = ['start', 'help', 'courses', 'interview', 'support'];
    if ($module === null || !in_array($module, $allowed, true)) {
        return "متوجه منظورت نشدم. برای دیدن راهنما، دستور /help را بفرست. 🙂";
    }
    return require __DIR__ . '/../commands/' . $module . '.php';
}
