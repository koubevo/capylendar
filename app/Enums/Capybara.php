<?php

namespace App\Enums;

use Illuminate\Support\Facades\Config;

enum Capybara: string
{
    case Blue = 'blue';

    case Pink = 'pink';

    case Yellow = 'yellow';

    public function getLabel(): string
    {
        return match ($this) {
            self::Blue => Config::get('app.blue.name'),
            self::Pink => Config::get('app.pink.name'),
            self::Yellow => 'Oba',
        };
    }
}
