<?php

namespace App\Services;

use App\Concerns\FormatsHumanDates;
use App\Models\RelationshipSettings;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class RelationshipMilestoneService
{
    use FormatsHumanDates;

    /** @var list<int> */
    private const INTERESTING_DAY_MILESTONES = [69, 111, 222, 333, 420, 444, 555, 666, 696, 777, 888, 999, 1111, 1234, 2000, 2222, 2500, 3333, 5000, 6969, 10000];

    /** @return array{days_together: int, human_label: string}|null */
    public function menuSummary(?RelationshipSettings $settings, ?CarbonInterface $today = null): ?array
    {
        if (! $settings?->started_on) {
            return null;
        }

        $today = $this->today($today);

        return [
            'days_together' => $this->daysTogether($settings, $today),
            'human_label' => $this->humanLabel($settings->started_on, $today),
        ];
    }

    /**
     * @return array{days_together: int, human_label: string, next_milestone: array{date: string, date_label: string, type: string, description: string, days_remaining: int}|null, upcoming_milestones: list<array{date: string, date_label: string, type: string, description: string, days_remaining: int}>}|null
     */
    public function summary(?RelationshipSettings $settings, ?CarbonInterface $today = null): ?array
    {
        if (! $settings?->started_on) {
            return null;
        }

        $today = $this->today($today);
        $daysTogether = $this->daysTogether($settings, $today);
        $upcoming = $this->upcomingMilestones($settings, $today);

        return [
            'days_together' => $daysTogether,
            'human_label' => $this->humanLabel($settings->started_on, $today),
            'next_milestone' => $upcoming[0] ?? null,
            'upcoming_milestones' => array_slice($upcoming, 0, 5),
        ];
    }

    /**
     * @return list<array{key: string, type: string, description: string, value: int}>
     */
    public function milestonesForDate(RelationshipSettings $settings, CarbonInterface $date): array
    {
        if (! $settings->started_on) {
            return [];
        }

        $date = $this->today($date);
        $days = $this->daysTogether($settings, $date);

        if ($days < 0) {
            return [];
        }

        $milestones = [];
        $months = ($date->year - $settings->started_on->year) * 12 + $date->month - $settings->started_on->month;

        if ($months >= 1 && $months % 12 !== 0 && $this->monthlyAnniversary($settings->started_on, $months)->isSameDay($date)) {
            $milestones[] = $this->milestone('monthly', $months, $this->durationPart($months, "m\u{011b}s\u{00ed}c", "m\u{011b}s\u{00ed}ce", "m\u{011b}s\u{00ed}c\u{016f}").' spolu');
        }

        $years = $date->year - $settings->started_on->year;

        if ($years >= 1 && $this->yearlyAnniversary($settings->started_on, $date->year)->isSameDay($date)) {
            $milestones[] = $this->milestone('yearly', $years, $years.". v\u{00fd}ro\u{010d}\u{00ed}");
        }

        if ($days >= 100 && $days % 100 === 0) {
            $milestones[] = $this->milestone('days', $days, $this->durationPart($days, 'den', 'dny', "dn\u{00ed}").' spolu');
        }

        if (in_array($days, self::INTERESTING_DAY_MILESTONES, true)) {
            $milestones[] = $this->milestone('interesting_numbers', $days, $this->durationPart($days, 'den', 'dny', "dn\u{00ed}").' spolu');
        }

        $weeks = intdiv($days, 7);

        if ($days > 0 && $days % 7 === 0 && ($weeks === 50 || $weeks >= 100 && $weeks % 100 === 0)) {
            $milestones[] = $this->milestone('weeks', $weeks, $this->durationPart($weeks, "t\u{00fd}den", "t\u{00fd}dny", "t\u{00fd}dn\u{016f}").' spolu');
        }

        return $milestones;
    }

    /**
     * @return list<array{date: string, date_label: string, type: string, description: string, days_remaining: int}>
     */
    private function upcomingMilestones(RelationshipSettings $settings, CarbonInterface $today): array
    {
        $upcoming = [];

        for ($offset = 0; $offset <= 366 && count($upcoming) < 5; $offset++) {
            $date = $today->copy()->addDays($offset);

            foreach ($this->milestonesForDate($settings, $date) as $milestone) {
                $upcoming[] = [
                    'date' => $date->toDateString(),
                    'date_label' => $this->humanDateLabel($date),
                    'type' => $milestone['type'],
                    'description' => $milestone['description'],
                    'days_remaining' => $offset,
                ];
            }
        }

        return $upcoming;
    }

    private function daysTogether(RelationshipSettings $settings, CarbonInterface $date): int
    {
        $startedOn = $settings->started_on;

        if (! $startedOn) {
            return 0;
        }

        return (int) $startedOn->copy()->startOfDay()->diffInDays($date, false);
    }

    private function humanLabel(CarbonInterface $startedOn, CarbonInterface $today): string
    {
        $interval = $startedOn->copy()->startOfDay()->diff($today);

        $parts = [];

        foreach ([
            [$interval->y, 'rok', 'roky', 'let'],
            [$interval->m, "m\u{011b}s\u{00ed}c", "m\u{011b}s\u{00ed}ce", "m\u{011b}s\u{00ed}c\u{016f}"],
            [$interval->d, 'den', 'dny', "dn\u{00ed}"],
        ] as [$value, $singular, $few, $many]) {
            if ($value > 0) {
                $parts[] = $this->durationPart($value, $singular, $few, $many);
            }
        }

        return $parts === [] ? "0 dn\u{00ed}" : implode(' ', $parts);
    }

    private function durationPart(int $value, string $singular, string $few, string $many): string
    {
        if ($value === 1) {
            return $value.' '.$singular;
        }

        if ($value % 100 >= 12 && $value % 100 <= 14) {
            return $value.' '.$many;
        }

        if ($value % 10 >= 2 && $value % 10 <= 4) {
            return $value.' '.$few;
        }

        return $value.' '.$many;
    }

    private function monthlyAnniversary(CarbonInterface $startedOn, int $months): CarbonInterface
    {
        return $startedOn->copy()->startOfDay()->addMonthsNoOverflow($months);
    }

    private function yearlyAnniversary(CarbonInterface $startedOn, int $year): Carbon
    {
        $month = $startedOn->month;
        $monthStart = Carbon::parse(sprintf('%04d-%02d-01', $year, $month));
        $day = min($startedOn->day, $monthStart->daysInMonth);

        return Carbon::parse(sprintf('%04d-%02d-%02d', $year, $month, $day))->startOfDay();
    }

    /**
     * @return array{key: string, type: string, description: string, value: int}
     */
    private function milestone(string $type, int $value, string $description): array
    {
        return [
            'key' => $type.':'.$value,
            'type' => $type,
            'description' => $description,
            'value' => $value,
        ];
    }

    private function today(?CarbonInterface $today = null): Carbon
    {
        $timezone = config('app.timezone');

        return ($today ? Carbon::instance($today) : now(is_string($timezone) ? $timezone : null))->startOfDay();
    }
}
