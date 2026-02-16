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
                'link_classes' => 'bg-pink-50 text-pink-700 hover:bg-pink-100 dark:bg-pink-900/30 dark:text-pink-300 dark:hover:bg-pink-900/50',
            ],
            self::Blue => [
                'value' => self::Blue->value,
                'label' => Config::get('app.blue.name', 'Blue'),
                'avatar' => ['src' => '/images/capys/blue.jpg', 'alt' => 'Blue'],
                'classes' => 'bg-blue-100 md:bg-blue-50 hover:bg-blue-100',
                'link_classes' => 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50',
            ],
            self::Yellow => [
                'value' => self::Yellow->value,
                'label' => Config::get('app.yellow.name', 'Yellow'),
                'avatar' => ['src' => '/images/capys/yellow.jpg', 'alt' => 'Yellow'],
                'classes' => 'bg-yellow-100 md:bg-yellow-50 hover:bg-yellow-100',
                'link_classes' => 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-300 dark:hover:bg-yellow-900/50',
            ],
        };
    }
}
