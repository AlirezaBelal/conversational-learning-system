<?php

// Exercise the actual entry point in isolated CLI processes; no listener or API calls.
foreach ([
    [[], 503],
    [['REQUEST_METHOD' => 'POST'], 403],
    [['REQUEST_METHOD' => 'GET', 'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'synthetic'], 405],
    [['REQUEST_METHOD' => 'POST', 'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'synthetic', 'CONTENT_LENGTH' => 1048577], 413],
    [['REQUEST_METHOD' => 'POST', 'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => 'synthetic'], 400],
] as $index => [$server, $expected]) {
    $code = '$_ENV["APP_ENV"]="production";$_ENV["ALLOW_UNAUTHENTICATED_LOCAL_WEBHOOK"]="false";'
        . '$_ENV["TELEGRAM_WEBHOOK_SECRET"]=' . var_export($index === 0 ? '' : 'synthetic', true) . ';'
        . '$_SERVER=' . var_export($server, true) . ';require ' . var_export(__DIR__ . '/../public/index.php', true)
        . ';echo http_response_code();';
    $process = proc_open([PHP_BINARY, '-r', $code], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start isolated PHP entry-point check.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    if ($exit !== 0 || trim($stdout) !== (string)$expected || str_contains($stderr, 'synthetic')) {
        throw new RuntimeException('Entry-point authentication/parsing check failed.');
    }
}
fwrite(STDOUT, "HTTP entry-point status and pre-database rejection tests passed (CLI harness).\n");
