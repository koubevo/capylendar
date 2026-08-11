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

        return $capitalize ? ucfirst($label) : $label;
    }
}
