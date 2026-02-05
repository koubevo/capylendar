<?php

use App\Http\Resources\EventResource;
use App\Models\Event;

describe('EventResource', function () {
    it('returns "Dnes" for today events', function () {
        $event = Event::factory()->create([
            // Use setTestNow or manual date matches
            'start_at' => now(),
        ]);

        $resource = (new EventResource($event))->resolve();

        expect($resource['date']['label'])->toBe('Dnes');
    });

    it('returns "Zítra" for tomorrow events', function () {
        $event = Event::factory()->create([
            'start_at' => now()->addDay(),
        ]);

        $resource = (new EventResource($event))->resolve();

        expect($resource['date']['label'])->toBe('Zítra');
    });

    it('formats map preview correctly', function () {
        $mapUrl = 'https://maps.app.goo.gl/test';
        $desc = "Party at $mapUrl";
        $meta = ['map_preview' => ['url' => $mapUrl]];

        $event = Event::factory()->create([
            'description' => $desc,
            'meta' => $meta,
        ]);

        $resource = (new EventResource($event))->resolve();

        expect($resource['description_without_meta'])->toBe('Party at');
        expect($resource['has_map_meta'])->toBeTrue();
    });
});
