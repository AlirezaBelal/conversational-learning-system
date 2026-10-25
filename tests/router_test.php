<?php

require_once __DIR__ . '/../src/router.php';

function assertSameValue(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAILED: {$label}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

assertSameValue(['/start', null], parseBotCommand('/start'), 'plain start command');
assertSameValue(['/start', 'courses'], parseBotCommand('/start courses'), 'start deep link parameter');
assertSameValue(['/help', null], parseBotCommand('/help@acuLearn_bot'), 'command addressed to bot username');
assertSameValue(['', null], parseBotCommand('   '), 'empty message');

assertSameValue(['', null], parseBotCommand('/help@other!'), 'malformed addressed command');

assertSameValue('start', resolveBotModule('/start'), 'start route');
assertSameValue('courses', resolveBotModule('/start', 'courses'), 'courses deep link');
assertSameValue('interview', resolveBotModule('/interview'), 'interview route');
assertSameValue('support', resolveBotModule('/contact'), 'contact alias');
assertSameValue(null, resolveBotModule('/unknown'), 'unknown route');
$customStart = commandReply('start', 'new_learning_bot');
assertSameValue(true, str_contains($customStart, 'https://t.me/new_learning_bot?start=courses'), 'gateway courses link follows configured name');
assertSameValue(true, str_contains($customStart, 'https://t.me/new_learning_bot?start=interview'), 'gateway interview link follows configured name');
assertSameValue(false, str_contains($customStart, 'https://t.me/acuLearn_bot?start='), 'stale gateway link removed');
$customHelp = commandReply('help', 'new_learning_bot');
assertSameValue(true, str_contains($customHelp, 'https://t.me/new_learning_bot?start=support'), 'gateway support deep link follows configured name');
assertSameValue(true, str_contains(commandReply('courses', 'new_learning_bot'), 'https://t.me/pm_aculearn_bot'), 'external course bot remains unchanged');
assertSameValue(true, str_contains(commandReply('support', 'new_learning_bot'), 'https://t.me/sup_aculearn_bot'), 'external support bot remains unchanged');
assertSameValue(true, str_contains(commandReply('start'), 'https://t.me/acuLearn_bot?start='), 'default legacy bot link compatible');
foreach (['@new_learning_bot', 'name with spaces', 'bot/evil', 'foo.example/path'] as $invalid) {
    try {
        commandReply('start', $invalid);
        throw new RuntimeException('Invalid bot username unexpectedly accepted');
    } catch (InvalidArgumentException) {
        // Only a safe Telegram username may be interpolated into Markdown links.
    }
}

fwrite(STDOUT, "Router tests passed.\n");
