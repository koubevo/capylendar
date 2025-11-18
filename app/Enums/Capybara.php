<?php

namespace App\Enums;

use Illuminate\Support\Facades\Config;

enum Capybara: string
{
    case Pink = 'pink';

    case Blue = 'blue';

    case Yellow = 'yellow';

    public function getLabel(): string
    {
        return $this->info()['label'];
    }

    public function getAvatar(): string
    {
        return $this->info()['avatar'];
    }

    public function info(): array
    {
        return match ($this) {
            self::Pink => [
                'label' => Config::get('app.pink.name', 'Pink'),
                'avatar' => '/images/capys/pink.jpg',
            ],
            self::Blue => [
                'label' => Config::get('app.blue.name', 'Blue'),
                'avatar' => '/images/capys/blue.jpg',
            ],
            self::Yellow => [
                'label' => Config::get('app.yellow.name', 'Yellow'),
                'avatar' => '/images/capys/yellow.jpg',
            ],
        };
    }
}
