<?php

namespace App\Enums;

use App\Models\User;

enum Capybara: string
{
    case Blue = 'blue';

    case Pink = 'pink';

    case Yellow = 'yellow';

    public function getLabel(): string
    {
        return match ($this) {
            self::Blue => User::find(1)->name,
            self::Pink => User::find(2)->name,
            self::Yellow => 'Oba',
        };
    }
}
