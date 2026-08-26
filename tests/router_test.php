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

assertSameValue('start', resolveBotModule('/start'), 'start route');
assertSameValue('courses', resolveBotModule('/start', 'courses'), 'courses deep link');
assertSameValue('interview', resolveBotModule('/interview'), 'interview route');
assertSameValue('support', resolveBotModule('/contact'), 'contact alias');
assertSameValue(null, resolveBotModule('/unknown'), 'unknown route');

fwrite(STDOUT, "Router tests passed.\n");
