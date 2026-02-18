<?php

namespace App\Enums;

use Illuminate\Support\Collection;

enum Priority: string
{
    case Low = 'low';

    case Medium = 'medium';

    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Nízká',
            self::Medium => 'Střední',
            self::High => 'Vysoká',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Low => 'i-lucide-arrow-down',
            self::Medium => 'i-lucide-minus',
            self::High => 'i-lucide-arrow-up',
        };
    }

    public function borderClass(): string
    {
        return match ($this) {
            self::Low => 'border-l-green-400',
            self::Medium => 'border-l-yellow-400',
            self::High => 'border-l-red-400',
        };
    }

    public function iconColor(): string
    {
        return match ($this) {
            self::Low => 'text-green-500',
            self::Medium => 'text-yellow-500',
            self::High => 'text-red-500',
        };
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function options(): Collection
    {
        return collect(self::cases())->map(function ($case) {
            return [
                'value' => $case->value,
                'label' => $case->label(),
                'icon' => $case->icon(),
                'border_class' => $case->borderClass(),
                'icon_color' => $case->iconColor(),
            ];
        });
    }
}
