<?php

use Illuminate\Support\Env;

/**
 * @param  array<string, string>  $variables
 * @return array<string, mixed>
 */
function loadSecurityConfiguration(string $file, array $variables): array
{
    $keys = [
        'APP_ENV',
        'BOOST_BROWSER_LOGS_WATCHER',
        'BOOST_ENABLED',
        'SESSION_SECURE_COOKIE',
    ];
    $originalEnvironment = [];

    foreach ($keys as $key) {
        $originalEnvironment[$key] = [
            'environment_exists' => array_key_exists($key, $_ENV),
            'environment_value' => $_ENV[$key] ?? null,
            'server_exists' => array_key_exists($key, $_SERVER),
            'server_value' => $_SERVER[$key] ?? null,
            'process_value' => getenv($key),
        ];

        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    }

    foreach ($variables as $key => $value) {
        putenv("$key=$value");
    }

    Env::enablePutenv();

    try {
        return require config_path($file);
    } finally {
        foreach ($originalEnvironment as $key => $original) {
            putenv($key);

            if ($original['process_value'] !== false) {
                putenv("$key={$original['process_value']}");
            }

            if ($original['environment_exists']) {
                $_ENV[$key] = $original['environment_value'];
            } else {
                unset($_ENV[$key]);
            }

            if ($original['server_exists']) {
                $_SERVER[$key] = $original['server_value'];
            } else {
                unset($_SERVER[$key]);
            }
        }

        Env::enablePutenv();
    }
}

it('defaults secure session cookies by environment', function (
    string $environment,
    bool $expected,
) {
    $configuration = loadSecurityConfiguration('session.php', [
        'APP_ENV' => $environment,
    ]);

    expect($configuration['secure'])->toBe($expected);
})->with([
    'production' => ['production', true],
    'local' => ['local', false],
]);

it('allows explicit secure session cookie overrides', function (
    string $environment,
    string $override,
    bool $expected,
) {
    $configuration = loadSecurityConfiguration('session.php', [
        'APP_ENV' => $environment,
        'SESSION_SECURE_COOKIE' => $override,
    ]);

    expect($configuration['secure'])->toBe($expected);
})->with([
    'disable in production' => ['production', 'false', false],
    'enable locally' => ['local', 'true', true],
]);

it('defaults boost to local environments only', function (
    string $environment,
    bool $expected,
) {
    $configuration = loadSecurityConfiguration('boost.php', [
        'APP_ENV' => $environment,
    ]);

    expect($configuration['enabled'])->toBe($expected);
})->with([
    'local' => ['local', true],
    'production' => ['production', false],
]);

it('allows explicit boost overrides', function () {
    $configuration = loadSecurityConfiguration('boost.php', [
        'APP_ENV' => 'production',
        'BOOST_ENABLED' => 'true',
        'BOOST_BROWSER_LOGS_WATCHER' => 'false',
    ]);

    expect($configuration['enabled'])->toBeTrue()
        ->and($configuration['browser_logs_watcher'])->toBeFalse();
});
