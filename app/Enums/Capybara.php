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
                'avatar' => 'https://raw.githubusercontent.com/koubevo/capys/refs/heads/main/pink.webp',
            ],
            self::Blue => [
                'label' => Config::get('app.blue.name', 'Blue'),
                'avatar' => 'https://raw.githubusercontent.com/koubevo/capys/refs/heads/main/blue.webp', // Sem dej odkaz na avatar
            ],
            self::Yellow => [
                'label' => Config::get('app.yellow.name', 'Yellow'),
                'avatar' => 'https://raw.githubusercontent.com/koubevo/capys/refs/heads/main/yellow.webp',
            ],
        };
    }
}
