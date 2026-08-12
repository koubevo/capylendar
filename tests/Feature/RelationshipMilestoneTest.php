<?php

use App\Models\RelationshipMilestoneDelivery;
use App\Models\RelationshipSettings;
use App\Models\User;
use App\Models\WatchDevice;
use App\Notifications\RelationshipMilestoneNotification;
use App\Services\RelationshipMilestoneNotificationService;
use App\Services\RelationshipMilestoneService;
use Carbon\Carbon;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow();
});

afterEach(function () {
    Carbon::setTestNow();
});

function relationshipSettings(string $startedOn, array $attributes = []): RelationshipSettings
{
    $user = User::factory()->create();

    return RelationshipSettings::factory()->make([
        'started_on' => $startedOn,
        'created_by' => $user->id,
        'updated_by' => $user->id,
        ...$attributes,
    ]);
}

it('counts the start day as day zero and identifies every hundred-day milestone', function () {
    $service = app(RelationshipMilestoneService::class);
    $settings = relationshipSettings('2026-01-01');

    expect($service->summary($settings, Carbon::parse('2026-01-01'))['days_together'])->toBe(0)
        ->and(array_column($service->milestonesForDate($settings, Carbon::parse('2026-04-11')), 'key'))
        ->toContain('days:100')
        ->and(array_column($service->milestonesForDate($settings, Carbon::parse('2026-07-20')), 'key'))
        ->toContain('days:200')
        ->and(array_column($service->milestonesForDate($settings, Carbon::parse('2026-04-11')), 'description'))
        ->toContain("100 dn\u{00ed} spolu");
});

it('handles leap days and end-of-month monthly anniversaries', function () {
    $service = app(RelationshipMilestoneService::class);

    $leapDay = relationshipSettings('2024-02-29');
    $monthEnd = relationshipSettings('2026-01-31');

    expect(array_column($service->milestonesForDate($leapDay, Carbon::parse('2025-02-28')), 'key'))
        ->toContain('yearly:1')
        ->not->toContain('monthly:12')
        ->and(array_column($service->milestonesForDate($monthEnd, Carbon::parse('2026-02-28')), 'key'))
        ->toContain('monthly:1')
        ->and(array_column($service->milestonesForDate($monthEnd, Carbon::parse('2027-02-28')), 'key'))
        ->toContain('monthly:13');
});

it('combines categories that fall on the same date', function () {
    $service = app(RelationshipMilestoneService::class);
    $settings = relationshipSettings('2020-04-27');

    expect(array_column($service->milestonesForDate($settings, Carbon::parse('2034-01-04')), 'key'))
        ->toContain('days:5000', 'interesting_numbers:5000');
});

it('honours weekly boundaries', function () {
    $service = app(RelationshipMilestoneService::class);
    $settings = relationshipSettings('2025-09-02');

    expect(array_column($service->milestonesForDate($settings, Carbon::parse('2026-08-18')), 'key'))
        ->toContain('weeks:50');
});

it('includes documented playful milestones', function (int $days) {
    $service = app(RelationshipMilestoneService::class);
    $settings = relationshipSettings('2026-01-01');

    expect(array_column($service->milestonesForDate($settings, Carbon::parse('2026-01-01')->addDays($days)), 'key'))
        ->toContain('interesting_numbers:'.$days);
})->with([69, 420, 666, 696, 6969]);

it('stores one delivery and sends one merged notification on retry', function () {
    Notification::fake();
    Carbon::setTestNow('2026-05-28 07:00:00');

    $user = User::factory()->create(['notifications_enabled' => true]);
    $user->updatePushSubscription(
        'https://fcm.googleapis.com/fcm/send/relationship-test',
        'test-public-key',
        'test-auth-token',
    );

    $settings = RelationshipSettings::factory()->create([
        'started_on' => '2020-04-27',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $service = app(RelationshipMilestoneNotificationService::class);

    expect($service->sendMorningNotifications()['users_notified'])->toBe(1)
        ->and($service->sendMorningNotifications()['users_notified'])->toBe(0)
        ->and(RelationshipMilestoneDelivery::query()->where('relationship_settings_id', $settings->id)->count())->toBe(1);

    Notification::assertSentToTimes($user, RelationshipMilestoneNotification::class, 1);
});

it('does not schedule milestones when notifications are disabled', function () {
    Carbon::setTestNow('2026-04-11 07:00:00');
    $user = User::factory()->create();

    RelationshipSettings::factory()->create([
        'started_on' => '2026-01-01',
        'notifications_enabled' => false,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    expect(app(RelationshipMilestoneNotificationService::class)->sendMorningNotifications())
        ->toBe(['users_notified' => 0, 'errors' => 0])
        ->and(RelationshipMilestoneDelivery::query()->count())->toBe(0);
});

it('allows either authenticated user to update settings', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $payload = [
        'started_on' => '2025-01-01',
        'name' => 'Us',
        'notifications_enabled' => true,
        'original_started_on' => null,
    ];

    $this->actingAs($first)->put(route('relationship-settings.update'), $payload)
        ->assertRedirect(route('relationship-settings.index'))
        ->assertSessionHas('success', "Nastaven\u{00ed} vztahu bylo ulo\u{017e}eno.");

    $this->actingAs($second)->put(route('relationship-settings.update'), [
        ...$payload,
        'original_started_on' => '2025-01-01',
    ])->assertRedirect();

    expect(RelationshipSettings::query()->sole()->updated_by)->toBe($second->id);
});

it('rejects guests from relationship settings', function () {
    $this->get(route('relationship-settings.index'))->assertRedirect(route('login'));
});

it('shares the relationship count with authenticated menu pages', function () {
    Carbon::setTestNow('2026-04-11 12:00:00');
    $user = User::factory()->create();

    RelationshipSettings::factory()->create([
        'started_on' => '2026-01-01',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)->get(route('profile'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('relationshipSummary', [
                'days_together' => 100,
                'human_label' => "3 m\u{011b}s\u{00ed}ce 10 dn\u{00ed}",
            ]));
});

it('shares an ISO date for the date field and a human label for milestones', function () {
    Carbon::setTestNow('2026-04-11 12:00:00');
    $user = User::factory()->create();

    RelationshipSettings::factory()->create([
        'started_on' => '2026-01-01',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)->get(route('relationship-settings.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('relationship.started_on', '2026-01-01')
            ->where('summary.next_milestone.date_label', 'Dnes'));
});

it('formats relationship duration with only non-zero parts', function () {
    $service = app(RelationshipMilestoneService::class);
    $fullDuration = relationshipSettings('2024-01-07');
    $withoutDays = relationshipSettings('2024-01-11');

    $fullLabel = $service->summary($fullDuration, Carbon::parse('2026-04-11'))['human_label'];
    $withoutDaysLabel = $service->summary($withoutDays, Carbon::parse('2026-04-11'))['human_label'];

    expect($fullLabel)
        ->toContain('2 roky')
        ->toContain('4 dny')
        ->and($withoutDaysLabel)
        ->toContain('2 roky')
        ->not->toContain('0');
});
it('retries only recipients whose relationship notification failed', function () {
    Carbon::setTestNow('2026-05-28 07:00:00');

    $successfulUser = User::factory()->create(['notifications_enabled' => true]);
    $failedUser = User::factory()->create(['notifications_enabled' => true]);

    foreach ([$successfulUser, $failedUser] as $user) {
        $user->updatePushSubscription(
            'https://fcm.googleapis.com/fcm/send/relationship-test-'.$user->id,
            'test-public-key',
            'test-auth-token',
        );
    }

    $settings = RelationshipSettings::factory()->create([
        'started_on' => '2020-04-27',
        'created_by' => $successfulUser->id,
        'updated_by' => $successfulUser->id,
    ]);

    $attempts = [];
    $failedOnce = false;
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('send')
        ->times(3)
        ->andReturnUsing(function (User $user) use (&$attempts, &$failedOnce, $failedUser): void {
            $attempts[] = $user->id;

            if ($user->is($failedUser) && ! $failedOnce) {
                $failedOnce = true;

                throw new RuntimeException('Temporary push failure');
            }
        });
    app()->instance(Dispatcher::class, $dispatcher);

    $service = app(RelationshipMilestoneNotificationService::class);

    expect($service->sendMorningNotifications())
        ->toBe(['users_notified' => 1, 'errors' => 1])
        ->and(RelationshipMilestoneDelivery::query()->pluck('user_id')->all())
        ->toBe([$successfulUser->id])
        ->and($service->sendMorningNotifications())
        ->toBe(['users_notified' => 1, 'errors' => 0])
        ->and($service->sendMorningNotifications())
        ->toBe(['users_notified' => 0, 'errors' => 0])
        ->and($attempts)
        ->toBe([$successfulUser->id, $failedUser->id, $failedUser->id])
        ->and(RelationshipMilestoneDelivery::query()
            ->where('relationship_settings_id', $settings->id)
            ->count())
        ->toBe(2);
});

it('enforces the fixed relationship settings singleton id in an empty table', function () {
    $user = User::factory()->create();

    expect(fn () => RelationshipSettings::query()->create([
        'id' => 2,
        'started_on' => '2025-01-01',
        'notifications_enabled' => true,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]))->toThrow(QueryException::class)
        ->and(RelationshipSettings::query()->count())->toBe(0);

    RelationshipSettings::factory()->create([
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    expect(RelationshipSettings::current())->not->toBeNull();
});

it('requires confirmation before changing the relationship start date', function () {
    $user = User::factory()->create();
    RelationshipSettings::factory()->create([
        'started_on' => '2025-01-01',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $payload = [
        'started_on' => '2025-02-01',
        'name' => 'My dva',
        'notifications_enabled' => true,
        'original_started_on' => '2025-01-01',
    ];

    $this->actingAs($user)->put(route('relationship-settings.update'), $payload)
        ->assertSessionHasErrors('started_on');

    expect(RelationshipSettings::current()?->started_on?->toDateString())->toBe('2025-01-01');

    $this->actingAs($user)->put(route('relationship-settings.update'), [
        ...$payload,
        'confirm_started_on_change' => true,
    ])->assertRedirect(route('relationship-settings.index'));

    expect(RelationshipSettings::current()?->started_on?->toDateString())->toBe('2025-02-01');
});

it('rejects a stale relationship settings form after the start date changed', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    RelationshipSettings::factory()->create([
        'started_on' => '2025-01-01',
        'created_by' => $first->id,
        'updated_by' => $first->id,
    ]);

    $this->actingAs($first)->put(route('relationship-settings.update'), [
        'started_on' => '2025-02-01',
        'original_started_on' => '2025-01-01',
        'name' => 'My dva',
        'notifications_enabled' => true,
        'confirm_started_on_change' => true,
    ])->assertRedirect(route('relationship-settings.index'));

    $this->actingAs($second)->put(route('relationship-settings.update'), [
        'started_on' => '2025-01-01',
        'original_started_on' => '2025-01-01',
        'name' => 'Starý formulář',
        'notifications_enabled' => true,
    ])->assertConflict();

    expect(RelationshipSettings::current()?->started_on?->toDateString())->toBe('2025-02-01')
        ->and(RelationshipSettings::current()?->name)->toBe('My dva');
});
it('removes the unusable web summary endpoint and keeps the watch endpoint', function () {
    $user = User::factory()->create();
    $plainTextToken = 'capy_watch_relationship_summary_test';

    WatchDevice::factory()->create([
        'user_id' => $user->id,
        'token_hash' => hash('sha256', $plainTextToken),
    ]);
    RelationshipSettings::factory()->create([
        'started_on' => '2025-01-01',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $this->actingAs($user)->getJson('/api/relationship/summary')->assertNotFound();

    $this->withToken($plainTextToken)
        ->getJson(route('watch.relationship.summary'))
        ->assertOk()
        ->assertJsonPath('data.days_together', fn (int $days): bool => $days >= 0);
});
