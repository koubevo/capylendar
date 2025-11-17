<?php

namespace App\Enums;

use Illuminate\Support\Facades\Config;

enum Capybara: string
{
    case Blue = 'blue';

    case Pink = 'pink';

    case Yellow = 'yellow';

    public function getLabel(): mixed
    {
        return match ($this) {
            self::Blue => Config::get('app.blue.name', 'Blue'),
            self::Pink => Config::get('app.pink.name', 'Pink'),
            self::Yellow => Config::get('app.yellow.name', 'Yellow'),
        };
    }
}
