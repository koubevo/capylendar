<?php

namespace App\ValueObjects;

use App\Models\Event;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class EventCountdown
{
    public function __construct(
        public bool $active,
        public string $label,
        public string $shortLabel,
        public int $days,
        public CarbonImmutable $targetAt,
        public CarbonImmutable $nextUpdateAt,
    ) {}

    public static function forEvent(Event $event, ?CarbonInterface $now = null): ?self
    {
        if (! $event->countdown_enabled) {
            return null;
        }

        $timezone = config('app.timezone');
        $timezone = is_string($timezone) ? $timezone : 'UTC';
        $current = CarbonImmutable::instance($now ?? now())->setTimezone($timezone);
        $target = CarbonImmutable::instance($event->start_at)->setTimezone($timezone);
        $activeUntil = $target->addDay()->startOfDay();

        if ($current->greaterThanOrEqualTo($activeUntil)) {
            return null;
        }

        $days = (int) $current->startOfDay()->diffInDays($target->startOfDay());
        $label = match ($days) {
            0 => 'dnes',
            1 => "z\u{00ED}tra",
            default => "za {$days} dn\u{00ED}",
        };

        return new self(
            active: true,
            label: $label,
            shortLabel: $days === 0 ? 'dnes' : "{$days} d",
            days: $days,
            targetAt: $target,
            nextUpdateAt: $current->addDay()->startOfDay(),
        );
    }

    /**
     * @return array{active: bool, label: string, short_label: string, days: int, target_at: string, next_update_at: string}
     */
    public function toArray(): array
    {
        return [
            'active' => $this->active,
            'label' => $this->label,
            'short_label' => $this->shortLabel,
            'days' => $this->days,
            'target_at' => $this->targetAt->toIso8601String(),
            'next_update_at' => $this->nextUpdateAt->toIso8601String(),
        ];
    }
}
