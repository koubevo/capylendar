<?php

namespace App\Concerns;

use Carbon\CarbonInterface;

trait FormatsHumanDates
{
    protected function humanDateLabel(
        CarbonInterface $date,
        string $fallbackFormat = 'l d.m.y',
        bool $capitalize = true,
    ): string {
        $label = match (true) {
            $date->isToday() => 'dnes',
            $date->isTomorrow() => 'zítra',
            default => $date->translatedFormat($fallbackFormat),
        };

        if (! $capitalize) {
            return $label;
        }

        return mb_strtoupper(mb_substr($label, 0, 1, 'UTF-8'), 'UTF-8')
            .mb_substr($label, 1, null, 'UTF-8');
    }
}
