<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->subscriber = User::factory()->create();
    $this->nonSubscriber = User::factory()->pink()->create();
    $this->event = Event::factory()->create();
    $this->event->subscribers()->attach($this->subscriber);
});

describe('show event', function () {
    it('allows subscriber to view event', function () {
        $this->actingAs($this->subscriber)
            ->get(route('event.show', $this->event))
            ->assertOk();
    });

    it('forbids non-subscriber from viewing event', function () {
        $this->actingAs($this->nonSubscriber)
            ->get(route('event.show', $this->event))
            ->assertForbidden();
    });
});

describe('edit event', function () {
    it('allows subscriber to access edit form', function () {
        $this->actingAs($this->subscriber)
            ->get(route('event.edit', $this->event))
            ->assertOk();
    });

    it('forbids non-subscriber from accessing edit form', function () {
        $this->actingAs($this->nonSubscriber)
            ->get(route('event.edit', $this->event))
            ->assertForbidden();
    });
});

describe('update event', function () {
    it('allows subscriber to update event', function () {
        $this->actingAs($this->subscriber)
            ->put(route('event.update', $this->event), [
                'title' => 'Updated Title',
                'start_at' => now()->addDay()->toDateTimeString(),
                'is_all_day' => false,
                'capybara' => 'blue',
            ])
            ->assertRedirect();
    });

    it('forbids non-subscriber from updating event', function () {
        $this->actingAs($this->nonSubscriber)
            ->put(route('event.update', $this->event), [
                'title' => 'Updated Title',
                'start_at' => now()->addDay()->toDateTimeString(),
                'is_all_day' => false,
                'capybara' => 'blue',
            ])
            ->assertForbidden();
    });
});

describe('delete event', function () {
    it('allows subscriber to delete event', function () {
        $this->actingAs($this->subscriber)
            ->delete(route('event.destroy', $this->event))
            ->assertRedirect();
    });

    it('forbids non-subscriber from deleting event', function () {
        $this->actingAs($this->nonSubscriber)
            ->delete(route('event.destroy', $this->event))
            ->assertForbidden();
    });
});

describe('restore event', function () {
    beforeEach(function () {
        $this->event->delete();
    });

    it('allows subscriber to restore deleted event', function () {
        $this->actingAs($this->subscriber)
            ->post(route('event.restore', $this->event))
            ->assertRedirect();

        expect($this->event->fresh()->trashed())->toBeFalse();
    });

    it('forbids non-subscriber from restoring event', function () {
        $this->actingAs($this->nonSubscriber)
            ->post(route('event.restore', $this->event))
            ->assertForbidden();
    });
});

describe('duplicate event', function () {
    it('allows subscriber to duplicate event', function () {
        $this->actingAs($this->subscriber)
            ->get(route('event.create', ['duplicate_event_id' => $this->event->id]))
            ->assertOk();
    });

    it('forbids non-subscriber from duplicating event', function () {
        $this->actingAs($this->nonSubscriber)
            ->get(route('event.create', ['duplicate_event_id' => $this->event->id]))
            ->assertForbidden();
    });
});
