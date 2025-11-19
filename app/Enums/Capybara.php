<?php

namespace App\Enums;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

enum Capybara: string
{
    case Pink = 'pink';

    case Blue = 'blue';

    case Yellow = 'yellow';

    public function getLabel(): mixed
    {
        return $this->info()['label'];
    }

    public function getAvatar(): mixed
    {
        return $this->info()['avatar'];
    }

    public function getClasses(): mixed
    {
        return $this->info()['classes'] ?? '';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function options(): Collection
    {
        return collect(self::cases())->map(function ($case) {
            return $case->info();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function info(): array
    {
        return match ($this) {
            self::Pink => [
                'value' => self::Pink->value,
                'label' => Config::get('app.pink.name', 'Pink'),
                'avatar' => ['src' => '/images/capys/pink.jpg', 'alt' => 'Pink'],
                'classes' => 'bg-pink-100 md:bg-pink-50 hover:bg-pink-100',
            ],
            self::Blue => [
                'value' => self::Blue->value,
                'label' => Config::get('app.blue.name', 'Blue'),
                'avatar' => ['src' => '/images/capys/blue.jpg', 'alt' => 'Blue'],
                'classes' => 'bg-blue-100 md:bg-blue-50 hover:bg-blue-100',
            ],
            self::Yellow => [
                'value' => self::Yellow->value,
                'label' => Config::get('app.yellow.name', 'Yellow'),
                'avatar' => ['src' => '/images/capys/yellow.jpg', 'alt' => 'Yellow'],
                'classes' => 'bg-yellow-100 md:bg-yellow-50 hover:bg-yellow-100',
            ],
        };
    }
}
