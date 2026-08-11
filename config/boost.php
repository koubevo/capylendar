<?php

declare(strict_types=1);

return [
    'enabled' => env(
        'BOOST_ENABLED',
        env('APP_ENV', 'production') === 'local',
    ),

    'browser_logs_watcher' => env('BOOST_BROWSER_LOGS_WATCHER', true),
];
