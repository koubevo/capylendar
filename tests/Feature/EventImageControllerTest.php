<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
    $this->user = User::factory()->create();
    $this->event = Event::factory()->create(['author_id' => $this->user->id]);
    $this->event->subscribers()->attach($this->user);
});

describe('EventImageController show', function () {
    it('returns the image for an authorized user', function () {
        $path = 'event-images/test-image.jpg';
        Storage::disk()->put($path, UploadedFile::fake()->image('test.jpg')->getContent());
        $this->event->update(['image_path' => $path]);

        $this->actingAs($this->user)
            ->get(route('event.image.show', $this->event))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    });

    it('returns 404 when event has no image', function () {
        $this->actingAs($this->user)
            ->get(route('event.image.show', $this->event))
            ->assertNotFound();
    });

    it('forbids unauthorized user from viewing image', function () {
        $path = 'event-images/test-image.jpg';
        Storage::disk()->put($path, UploadedFile::fake()->image('test.jpg')->getContent());
        $this->event->update(['image_path' => $path]);

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get(route('event.image.show', $this->event))
            ->assertForbidden();
    });

    it('requires authentication', function () {
        $this->get(route('event.image.show', $this->event))
            ->assertRedirect(route('login'));
    });
});

describe('Event image upload via EventController', function () {
    it('stores a new event with an image', function () {
        $file = UploadedFile::fake()->image('capybara.jpg');
        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => 'Event with Image',
                'date' => $tomorrow,
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'image' => $file,
            ])
            ->assertRedirect();

        $event = Event::where('title', 'Event with Image')->first();
        expect($event)->not->toBeNull();
        expect($event->image_path)->not->toBeNull();
        Storage::disk()->assertExists($event->image_path);
    });

    it('updates an event with a new image', function () {
        $file = UploadedFile::fake()->image('new-capybara.png');

        $this->actingAs($this->user)
            ->put(route('event.update', $this->event), [
                'title' => $this->event->title,
                'date' => $this->event->start_at->format('Y-m-d'),
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'image' => $file,
            ])
            ->assertRedirect();

        $this->event->refresh();
        expect($this->event->image_path)->not->toBeNull();
        Storage::disk()->assertExists($this->event->image_path);
    });

    it('replaces an existing image when updating with a new one', function () {
        $oldPath = 'event-images/old-image.jpg';
        Storage::disk()->put($oldPath, UploadedFile::fake()->image('old.jpg')->getContent());
        $this->event->update(['image_path' => $oldPath]);

        $newFile = UploadedFile::fake()->image('new-capybara.png');

        $this->actingAs($this->user)
            ->put(route('event.update', $this->event), [
                'title' => $this->event->title,
                'date' => $this->event->start_at->format('Y-m-d'),
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'image' => $newFile,
            ])
            ->assertRedirect();

        $this->event->refresh();
        expect($this->event->image_path)->not->toBe($oldPath);
        Storage::disk()->assertMissing($oldPath);
        Storage::disk()->assertExists($this->event->image_path);
    });

    it('removes an image when remove_image is true', function () {
        $path = 'event-images/to-remove.jpg';
        Storage::disk()->put($path, UploadedFile::fake()->image('test.jpg')->getContent());
        $this->event->update(['image_path' => $path]);

        $this->actingAs($this->user)
            ->put(route('event.update', $this->event), [
                'title' => $this->event->title,
                'date' => $this->event->start_at->format('Y-m-d'),
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'remove_image' => true,
            ])
            ->assertRedirect();

        $this->event->refresh();
        expect($this->event->image_path)->toBeNull();
        Storage::disk()->assertMissing($path);
    });

    it('rejects non-image files on store', function () {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => 'Event with PDF',
                'date' => $tomorrow,
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'image' => $file,
            ])
            ->assertSessionHasErrors('image');
    });

    it('rejects images larger than 5MB on store', function () {
        $file = UploadedFile::fake()->image('huge.jpg')->size(6000);
        $tomorrow = now()->addDay()->format('Y-m-d');

        $this->actingAs($this->user)
            ->post(route('event.store'), [
                'title' => 'Event with Huge Image',
                'date' => $tomorrow,
                'start_at' => '10:00',
                'is_all_day' => false,
                'capybara' => 'blue',
                'image' => $file,
            ])
            ->assertSessionHasErrors('image');
    });
});
