<?php

use App\Models\Event;
use App\Models\Tag;

describe('Tag Model', function () {
    it('has many events', function () {
        $tag = Tag::factory()->create();
        $events = Event::factory()->count(2)->create();
        $tag->events()->attach($events);

        expect($tag->events)->toHaveCount(2);
        expect($tag->events->first())->toBeInstanceOf(Event::class);
    });

    it('is mass assignable', function () {
        $tag = Tag::create([
            'label' => 'Test',
            'color' => '#ffffff',
        ]);

        expect($tag->label)->toBe('Test');
        expect($tag->color)->toBe('#ffffff');
    });
});
