<?php

use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use App\services\EventTagService;

beforeEach(function () {
    $this->eventTagService = new EventTagService;
    $this->user = User::factory()->create();
    $this->event = Event::factory()->create(['author_id' => $this->user->id]);
});

describe('EventTagService assignTags', function () {
    it('assigns tags to an event', function () {
        $tags = Tag::factory()->count(3)->create();
        $tagIds = $tags->pluck('id')->toArray();

        $this->eventTagService->assignTags($this->event, $tagIds);

        $this->event->refresh();
        expect($this->event->tags)->toHaveCount(3);
    });

    it('replaces existing tags with new tags', function () {
        $oldTags = Tag::factory()->count(2)->create();
        $this->event->tags()->attach($oldTags->pluck('id'));

        $newTags = Tag::factory()->count(3)->create();
        $newTagIds = $newTags->pluck('id')->toArray();

        $this->eventTagService->assignTags($this->event, $newTagIds);

        $this->event->refresh();
        expect($this->event->tags)->toHaveCount(3);
        foreach ($newTags as $tag) {
            expect($this->event->tags->contains($tag))->toBeTrue();
        }
    });

    it('removes all tags when given empty array', function () {
        $tags = Tag::factory()->count(2)->create();
        $this->event->tags()->attach($tags->pluck('id'));

        $this->eventTagService->assignTags($this->event, []);

        $this->event->refresh();
        expect($this->event->tags)->toHaveCount(0);
    });

    it('handles single tag', function () {
        $tag = Tag::factory()->create();

        $this->eventTagService->assignTags($this->event, [$tag->id]);

        $this->event->refresh();
        expect($this->event->tags)->toHaveCount(1);
        expect($this->event->tags->first()->id)->toBe($tag->id);
    });
});
