<?php

use App\Enums\EventKind;
use App\Models\Event;
use App\Models\User;
use App\Services\EventSurpriseService;
use App\Services\EventTagService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake();
    $this->user = User::factory()->create();
    $this->event = Event::factory()->create(['author_id' => $this->user->id]);
    $this->event->subscribers()->attach($this->user);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function surpriseEventPayload(array $overrides = []): array
{
    return [
        'title' => 'Narozeniny kapybary',
        'date' => now()->addDay()->format('Y-m-d'),
        'start_at' => '10:00',
        'is_all_day' => false,
        'capybara' => 'blue',
        'kind' => 'birthday',
        ...$overrides,
    ];
}

function lockSurprise(Event $event, string $password = 'meloun', ?string $hint = 'Oblíbené ovoce'): Event
{
    $path = 'event-surprise-images/'.fake()->uuid().'.jpg';
    Storage::disk()->put($path, UploadedFile::fake()->image('surprise.jpg')->getContent());

    $event->update([
        'kind' => EventKind::Birthday,
        'surprise_image_path' => $path,
        'surprise_password' => app(EventSurpriseService::class)->hashPassword($password),
        'surprise_hint' => $hint,
    ]);

    return $event;
}

describe('event kind', function () {
    it('defaults to a standard event', function () {
        $this->actingAs($this->user)
            ->post(route('event.store'), surpriseEventPayload(['kind' => null, 'title' => 'Bez typu']))
            ->assertRedirect();

        expect(Event::where('title', 'Bez typu')->firstOrFail()->kind)->toBe(EventKind::Standard);
    });

    it('stores a birthday event', function () {
        $this->actingAs($this->user)
            ->post(route('event.store'), surpriseEventPayload())
            ->assertRedirect();

        expect(Event::where('title', 'Narozeniny kapybary')->firstOrFail()->kind)->toBe(EventKind::Birthday);
    });

    it('rejects an invalid kind', function (mixed $kind) {
        $this->actingAs($this->user)
            ->post(route('event.store'), surpriseEventPayload(['kind' => $kind]))
            ->assertSessionHasErrors('kind');
    })->with([
        'unknown value' => 'wedding',
        'array' => [['birthday']],
        'number' => 42,
    ]);

    it('keeps the stored kind when an update omits it', function () {
        lockSurprise($this->event);

        $this->actingAs($this->user)
            ->put(route('event.update', $this->event), surpriseEventPayload(['kind' => null]))
            ->assertRedirect();

        $this->event->refresh();
        expect($this->event->kind)->toBe(EventKind::Birthday);
        expect($this->event->surprise_image_path)->not->toBeNull();
    });

    it('provides kind options to the forms', function () {
        $this->actingAs($this->user)
            ->get(route('event.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('kindOptions', 2)
                ->where('kindOptions.1.value', 'birthday')
                ->where('kindOptions.1.allows_surprise', true)
            );
    });
});

describe('surprise image storage', function () {
    it('cleans up both uploads when storing fails', function (string $failure) {
        if ($failure === 'hashing') {
            $this->mock(EventSurpriseService::class)
                ->shouldReceive('hashPassword')->once()->andThrow(new RuntimeException('Upload failed'));
        } else {
            $this->mock(EventTagService::class)
                ->shouldReceive('assignTags')->once()->andThrow(new RuntimeException('Upload failed'));
        }

        $this->withoutExceptionHandling();

        expect(fn () => $this->actingAs($this->user)
            ->post(route('event.store'), surpriseEventPayload([
                'image' => UploadedFile::fake()->image('event.jpg'),
                'surprise_image' => UploadedFile::fake()->image('gift.jpg'),
                'surprise_password' => 'meloun',
            ])))->toThrow(RuntimeException::class, 'Upload failed');

        expect(Storage::disk()->allFiles())->toBeEmpty();
        expect(Event::where('title', 'Narozeniny kapybary')->exists())->toBeFalse();
    })->with(['hashing', 'transaction']);

    it('keeps original images and removes new uploads when updating fails', function (string $failure) {
        lockSurprise($this->event);
        $oldSurprisePath = $this->event->surprise_image_path;
        $oldPassword = $this->event->surprise_password;
        $oldImagePath = 'event-images/original.jpg';
        Storage::disk()->put($oldImagePath, UploadedFile::fake()->image('original.jpg')->getContent());
        $this->event->update(['image_path' => $oldImagePath]);

        if ($failure === 'hashing') {
            $this->mock(EventSurpriseService::class)
                ->shouldReceive('hashPassword')->once()->andThrow(new RuntimeException('Upload failed'));
        } else {
            $this->mock(EventTagService::class)
                ->shouldReceive('assignTags')->once()->andThrow(new RuntimeException('Upload failed'));
        }

        $this->withoutExceptionHandling();

        expect(fn () => $this->actingAs($this->user)
            ->put(route('event.update', $this->event), surpriseEventPayload([
                'image' => UploadedFile::fake()->image('event.jpg'),
                'surprise_image' => UploadedFile::fake()->image('gift.jpg'),
                'surprise_password' => 'jahoda',
            ])))->toThrow(RuntimeException::class, 'Upload failed');

        $this->event->refresh();
        expect($this->event->image_path)->toBe($oldImagePath);
        expect($this->event->surprise_image_path)->toBe($oldSurprisePath);
        expect($this->event->surprise_password)->toBe($oldPassword);
        expect(Storage::disk()->allFiles())->toHaveCount(2);
        Storage::disk()->assertExists($oldImagePath);
        Storage::disk()->assertExists($oldSurprisePath);
    })->with(['hashing', 'transaction']);

    it('stores a hashed password, hint, and image for a birthday event', function () {
        $this->actingAs($this->user)
            ->post(route('event.store'), surpriseEventPayload([
                'surprise_image' => UploadedFile::fake()->image('gift.jpg'),
                'surprise_password' => 'Meloun',
                'surprise_hint' => 'Oblíbené ovoce',
            ]))
            ->assertRedirect();

        $event = Event::where('title', 'Narozeniny kapybary')->firstOrFail();

        expect($event->surprise_image_path)->toStartWith('event-surprise-images/');
        expect($event->surprise_password)->not->toBe('Meloun');
        expect($event->surprise_hint)->toBe('Oblíbené ovoce');
        expect($event->image_path)->toBeNull();
        Storage::disk()->assertExists($event->surprise_image_path);
    });

    it('requires a password with a surprise image', function () {
        $this->actingAs($this->user)
            ->post(route('event.store'), surpriseEventPayload([
                'surprise_image' => UploadedFile::fake()->image('gift.jpg'),
            ]))
            ->assertSessionHasErrors('surprise_password');

        expect(Storage::disk()->allFiles())->toBeEmpty();
    });

    it('rejects a surprise image on a standard event', function () {
        $this->actingAs($this->user)
            ->post(route('event.store'), surpriseEventPayload([
                'kind' => 'standard',
                'surprise_image' => UploadedFile::fake()->image('gift.jpg'),
                'surprise_password' => 'meloun',
            ]))
            ->assertSessionHasErrors('surprise_image');
    });

    it('rejects non-image and oversized surprise files', function (UploadedFile $file) {
        $this->actingAs($this->user)
            ->post(route('event.store'), surpriseEventPayload([
                'surprise_image' => $file,
                'surprise_password' => 'meloun',
            ]))
            ->assertSessionHasErrors('surprise_image');
    })->with([
        'pdf' => fn () => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        'too large' => fn () => UploadedFile::fake()->image('huge.jpg')->size(6000),
        'too wide' => fn () => UploadedFile::fake()->image('wide.jpg', 6001, 10),
    ]);

    it('ignores a password sent without a new image', function () {
        lockSurprise($this->event);
        $originalPassword = $this->event->surprise_password;

        $this->actingAs($this->user)
            ->put(route('event.update', $this->event), surpriseEventPayload([
                'surprise_password' => 'jine-heslo',
                'surprise_hint' => 'Nová nápověda',
            ]))
            ->assertRedirect();

        $this->event->refresh();
        expect($this->event->surprise_password)->toBe($originalPassword);
        expect($this->event->surprise_hint)->toBe('Nová nápověda');
    });

    it('replaces the surprise image and password together', function () {
        lockSurprise($this->event);
        $oldPath = $this->event->surprise_image_path;

        $this->actingAs($this->user)
            ->put(route('event.update', $this->event), surpriseEventPayload([
                'surprise_image' => UploadedFile::fake()->image('new.jpg'),
                'surprise_password' => 'jahoda',
            ]))
            ->assertRedirect();

        $this->event->refresh();
        expect($this->event->surprise_image_path)->not->toBe($oldPath);
        expect($this->event->surprise_hint)->toBeNull();
        Storage::disk()->assertMissing($oldPath);
        Storage::disk()->assertExists($this->event->surprise_image_path);

        $this->actingAs($this->user)
            ->postJson(route('event.surprise.unlock', $this->event), ['password' => 'meloun'])
            ->assertUnprocessable();
        $this->actingAs($this->user)
            ->postJson(route('event.surprise.unlock', $this->event), ['password' => 'jahoda'])
            ->assertNoContent();
    });

    it('removes the surprise on request', function () {
        lockSurprise($this->event);
        $oldPath = $this->event->surprise_image_path;

        $this->actingAs($this->user)
            ->put(route('event.update', $this->event), surpriseEventPayload(['remove_surprise_image' => true]))
            ->assertRedirect();

        $this->event->refresh();
        expect($this->event->surprise_image_path)->toBeNull();
        expect($this->event->surprise_password)->toBeNull();
        expect($this->event->surprise_hint)->toBeNull();
        Storage::disk()->assertMissing($oldPath);
    });

    it('removes the surprise when the event stops being a birthday', function () {
        lockSurprise($this->event);
        $oldPath = $this->event->surprise_image_path;

        $this->actingAs($this->user)
            ->put(route('event.update', $this->event), surpriseEventPayload(['kind' => 'standard']))
            ->assertRedirect();

        $this->event->refresh();
        expect($this->event->kind)->toBe(EventKind::Standard);
        expect($this->event->surprise_image_path)->toBeNull();
        expect($this->event->surprise_password)->toBeNull();
        Storage::disk()->assertMissing($oldPath);
    });
});

describe('surprise unlock', function () {
    it('keeps the image locked until the password is entered', function () {
        lockSurprise($this->event);

        $this->actingAs($this->user)
            ->get(route('event.surprise.show', $this->event))
            ->assertForbidden();

        $this->actingAs($this->user)
            ->get(route('event.show', $this->event))
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.kind.value', 'birthday')
                ->where('event.surprise.hint', 'Oblíbené ovoce')
                ->where('event.surprise.is_unlocked', false)
                ->where('event.surprise.image_url', null)
                ->missing('event.surprise_password')
                ->missing('event.surprise_image_path')
            );
    });

    it('locks the image for its author too', function () {
        lockSurprise($this->event);

        expect($this->event->author_id)->toBe($this->user->id);

        $this->actingAs($this->user)
            ->get(route('event.surprise.show', $this->event))
            ->assertForbidden();
    });

    it('rejects a wrong password', function () {
        lockSurprise($this->event);

        $this->actingAs($this->user)
            ->postJson(route('event.surprise.unlock', $this->event), ['password' => 'banán'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->actingAs($this->user)
            ->get(route('event.surprise.show', $this->event))
            ->assertForbidden();
    });

    it('requires a password', function () {
        lockSurprise($this->event);

        $this->actingAs($this->user)
            ->postJson(route('event.surprise.unlock', $this->event), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    });

    it('unlocks the image for the session with the right password', function (string $password) {
        lockSurprise($this->event);

        $this->actingAs($this->user)
            ->postJson(route('event.surprise.unlock', $this->event), ['password' => $password])
            ->assertNoContent();

        $this->actingAs($this->user)
            ->get(route('event.surprise.show', $this->event))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeader('cache-control', 'no-store, private');

        $this->actingAs($this->user)
            ->get(route('event.show', $this->event))
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.surprise.is_unlocked', true)
                ->where('event.surprise.image_url', $this->event->refresh()->surprise_image_url)
            );
    })->with([
        'exact' => 'meloun',
        'different case' => 'MeLoUn',
        'surrounding spaces' => '  meloun ',
    ]);

    it('does not unlock other events', function () {
        lockSurprise($this->event);
        $otherEvent = Event::factory()->create(['author_id' => $this->user->id]);
        $otherEvent->subscribers()->attach($this->user);
        lockSurprise($otherEvent, 'jahoda');

        $this->actingAs($this->user)
            ->postJson(route('event.surprise.unlock', $this->event), ['password' => 'meloun'])
            ->assertNoContent();

        $this->actingAs($this->user)
            ->get(route('event.surprise.show', $otherEvent))
            ->assertForbidden();
    });

    it('keeps another session locked', function () {
        lockSurprise($this->event);

        $this->actingAs($this->user)
            ->postJson(route('event.surprise.unlock', $this->event), ['password' => 'meloun'])
            ->assertNoContent();

        $this->flushSession();

        $this->actingAs($this->user)
            ->get(route('event.surprise.show', $this->event))
            ->assertForbidden();
    });

    it('still requires subscriber access after the session is unlocked', function () {
        lockSurprise($this->event);

        $this->actingAs($this->user)
            ->postJson(route('event.surprise.unlock', $this->event), ['password' => 'meloun'])
            ->assertNoContent();

        $this->event->subscribers()->detach($this->user);

        $this->actingAs($this->user)
            ->get(route('event.surprise.show', $this->event))
            ->assertForbidden();
    });

    it('locks again after the surprise image is replaced', function () {
        lockSurprise($this->event);

        $this->actingAs($this->user)
            ->postJson(route('event.surprise.unlock', $this->event), ['password' => 'meloun'])
            ->assertNoContent();

        lockSurprise($this->event, 'jahoda');

        $this->actingAs($this->user)
            ->get(route('event.surprise.show', $this->event))
            ->assertForbidden();
    });

    it('returns not found without a surprise', function () {
        $this->actingAs($this->user)
            ->get(route('event.surprise.show', $this->event))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->postJson(route('event.surprise.unlock', $this->event), ['password' => 'meloun'])
            ->assertNotFound();
    });

    it('forbids non-subscribers even with the right password', function () {
        lockSurprise($this->event);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->postJson(route('event.surprise.unlock', $this->event), ['password' => 'meloun'])
            ->assertForbidden();

        $this->actingAs($otherUser)
            ->get(route('event.surprise.show', $this->event))
            ->assertForbidden();
    });

    it('requires authentication', function () {
        lockSurprise($this->event);

        $this->get(route('event.surprise.show', $this->event))
            ->assertRedirect(route('login'));
        $this->post(route('event.surprise.unlock', $this->event), ['password' => 'meloun'])
            ->assertRedirect(route('login'));
    });

    it('throttles repeated unlock attempts', function () {
        lockSurprise($this->event);

        foreach (range(1, 10) as $attempt) {
            $this->actingAs($this->user)
                ->postJson(route('event.surprise.unlock', $this->event), ['password' => 'banán'])
                ->assertUnprocessable();
        }

        $this->actingAs($this->user)
            ->postJson(route('event.surprise.unlock', $this->event), ['password' => 'meloun'])
            ->assertTooManyRequests();
    });

    it('does not copy the surprise when duplicating', function () {
        lockSurprise($this->event);

        $this->actingAs($this->user)
            ->get(route('event.create', ['duplicate_event_id' => $this->event->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.kind.value', 'birthday')
                ->where('event.surprise.image_url', null)
            );
    });
});
