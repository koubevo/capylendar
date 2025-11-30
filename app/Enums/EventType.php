<?php

namespace App\Enums;

enum EventType: string
{
    case Upcoming = 'upcoming';

    case History = 'history';

    public function sortDirection(): string
    {
        return match ($this) {
            self::Upcoming => 'asc',
            self::History => 'desc',
        };
    }

    public function operator(): string
    {
        return match ($this) {
            self::Upcoming => '>=',
            self::History => '<',
        };
    }
}
