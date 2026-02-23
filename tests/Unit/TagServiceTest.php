<?php

use App\Models\Tag;
use App\Services\TagService;

beforeEach(function () {
    $this->tagService = new TagService;
});

describe('TagService getAvailableTags', function () {
    it('returns all tags', function () {
        Tag::factory()->count(3)->create();

        $tags = $this->tagService->getAvailableTags();

        expect($tags)->toHaveCount(3);
    });

    it('returns empty array when no tags exist', function () {
        $tags = $this->tagService->getAvailableTags();

        expect($tags)->toBeEmpty();
    });

    it('orders tags by label ascending by default', function () {
        Tag::factory()->create(['label' => 'Zebra']);
        Tag::factory()->create(['label' => 'Apple']);
        Tag::factory()->create(['label' => 'Mango']);

        $tags = $this->tagService->getAvailableTags();

        expect($tags[0]['label'])->toBe('Apple');
        expect($tags[1]['label'])->toBe('Mango');
        expect($tags[2]['label'])->toBe('Zebra');
    });

    it('orders tags by specified column and direction', function () {
        Tag::factory()->create([
            'label' => 'First',
            'created_at' => now()->subHours(2),
        ]);
        Tag::factory()->create([
            'label' => 'Second',
            'created_at' => now()->subHour(),
        ]);
        Tag::factory()->create([
            'label' => 'Third',
            'created_at' => now(),
        ]);

        $tags = $this->tagService->getAvailableTags([
            'by' => 'created_at',
            'order' => 'desc',
        ]);

        expect($tags[0]['label'])->toBe('Third');
        expect($tags[1]['label'])->toBe('Second');
        expect($tags[2]['label'])->toBe('First');
    });

    it('returns tags as array', function () {
        Tag::factory()->create(['label' => 'Test']);

        $tags = $this->tagService->getAvailableTags();

        expect($tags)->toBeArray();
        expect($tags[0])->toBeArray();
        expect($tags[0])->toHaveKey('label');
        expect($tags[0])->toHaveKey('color');
    });
});
