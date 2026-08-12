<?php

namespace App\Services;

use App\Models\RelationshipMilestoneDelivery;
use App\Models\RelationshipSettings;
use App\Models\User;
use App\Notifications\RelationshipMilestoneNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RelationshipMilestoneNotificationService
{
    public function __construct(private readonly RelationshipMilestoneService $milestones) {}

    /** @return array{users_notified: int, errors: int} */
    public function sendMorningNotifications(?CarbonInterface $today = null): array
    {
        $settings = RelationshipSettings::current();

        if (! $settings?->started_on || ! $settings->notifications_enabled) {
            return ['users_notified' => 0, 'errors' => 0];
        }

        $timezone = config('app.timezone');
        $date = $today ?? now(is_string($timezone) ? $timezone : null);
        $milestones = $this->milestones->milestonesForDate($settings, $date);

        if ($milestones === []) {
            return ['users_notified' => 0, 'errors' => 0];
        }

        $milestoneKey = implode('|', array_column($milestones, 'key'));
        $users = User::query()
            ->with('pushSubscriptions')
            ->where('notifications_enabled', true)
            ->whereHas('pushSubscriptions')
            ->orderBy('id')
            ->get();

        $usersNotified = 0;
        $errors = 0;
        $title = ($settings->name ?: "V\u{00e1}\u{0161} vztah").": spole\u{010d}n\u{00fd} miln\u{00ed}k";
        $descriptions = array_column($milestones, 'description');

        foreach ($users as $user) {
            try {
                $sent = Cache::lock($this->lockKey($settings, $user, $milestoneKey), 30)
                    ->block(5, function () use ($settings, $user, $milestoneKey, $date, $title, $descriptions): bool {
                        $alreadyDelivered = RelationshipMilestoneDelivery::query()
                            ->where('relationship_settings_id', $settings->id)
                            ->where('user_id', $user->id)
                            ->where('milestone_key', $milestoneKey)
                            ->exists();

                        if ($alreadyDelivered) {
                            return false;
                        }

                        $user->notify(new RelationshipMilestoneNotification($title, $descriptions));

                        RelationshipMilestoneDelivery::query()->create([
                            'relationship_settings_id' => $settings->id,
                            'user_id' => $user->id,
                            'milestone_key' => $milestoneKey,
                            'milestone_on' => $date->toDateString(),
                            'delivered_at' => now(),
                        ]);

                        return true;
                    });

                if ($sent) {
                    $usersNotified++;
                }
            } catch (Throwable $exception) {
                Log::error('Failed to send relationship milestone notification', [
                    'user_id' => $user->id,
                    'relationship_settings_id' => $settings->id,
                    'error' => $exception->getMessage(),
                ]);
                $errors++;
            }
        }

        return ['users_notified' => $usersNotified, 'errors' => $errors];
    }

    private function lockKey(RelationshipSettings $settings, User $user, string $milestoneKey): string
    {
        return sprintf(
            'relationship-milestone:%d:%d:%s',
            $settings->id,
            $user->id,
            hash('sha256', $milestoneKey),
        );
    }
}
