<?php

namespace App\Enums;

use Illuminate\Support\Collection;

enum EventKind: string
{
    case Standard = 'standard';

    case Birthday = 'birthday';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Běžný event',
            self::Birthday => 'Narozeniny',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Standard => 'i-lucide-calendar',
            self::Birthday => 'i-lucide-cake',
        };
    }

    public function allowsSurprise(): bool
    {
        return match ($this) {
            self::Standard => false,
            self::Birthday => true,
        };
    }

    public function notifiesOnCreation(): bool
    {
        return match ($this) {
            self::Standard => true,
            self::Birthday => false,
        };
    }

    /**
     * @return Collection<int, array{value: string, label: string, icon: string, allows_surprise: bool}>
     */
    public static function options(): Collection
    {
        return collect(self::cases())->map(function ($case) {
            return $case->info();
        });
    }

    /**
     * @return array{value: string, label: string, icon: string, allows_surprise: bool}
     */
    public function info(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'icon' => $this->icon(),
            'allows_surprise' => $this->allowsSurprise(),
        ];
    }
}
