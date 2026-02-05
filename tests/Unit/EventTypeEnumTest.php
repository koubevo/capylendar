<?php

use App\Enums\EventType;

describe('EventType Enum', function () {
    it('has expected cases', function () {
        expect(EventType::cases())->toHaveCount(2);
        expect(EventType::cases())->toContain(EventType::Upcoming);
        expect(EventType::cases())->toContain(EventType::History);
    });

    it('returns correct sort direction', function () {
        expect(EventType::Upcoming->sortDirection())->toBe('asc');
        expect(EventType::History->sortDirection())->toBe('desc');
    });

    it('returns correct operator', function () {
        expect(EventType::Upcoming->operator())->toBe('>=');
        expect(EventType::History->operator())->toBe('<');
    });
});
