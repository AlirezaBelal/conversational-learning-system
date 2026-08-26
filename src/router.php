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
