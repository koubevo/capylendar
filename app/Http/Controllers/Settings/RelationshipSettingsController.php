<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateRelationshipSettingsRequest;
use App\Models\RelationshipSettings;
use App\Services\RelationshipMilestoneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class RelationshipSettingsController extends Controller
{
    public function __construct(private readonly RelationshipMilestoneService $milestones) {}

    public function index(): Response
    {
        $settings = RelationshipSettings::current();

        return Inertia::render('settings/Relationship', [
            'relationship' => $settings ? [
                'started_on' => $settings->started_on?->toDateString(),
                'name' => $settings->name,
                'notifications_enabled' => $settings->notifications_enabled,
            ] : null,
            'summary' => $this->milestones->summary($settings),
        ]);
    }

    public function update(UpdateRelationshipSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $values = $request->safe()->only([
            'started_on',
            'name',
            'notifications_enabled',
        ]);
        $originalStartedOn = $request->input('original_started_on');
        $originalStartedOn = is_string($originalStartedOn) ? $originalStartedOn : null;

        Cache::lock('relationship-settings:update', 10)->block(5, function () use ($request, $user, $values, $originalStartedOn): void {
            DB::transaction(function () use ($request, $user, $values, $originalStartedOn): void {
                $settings = RelationshipSettings::query()
                    ->lockForUpdate()
                    ->find(RelationshipSettings::SINGLETON_ID);
                $storedStartedOn = $settings?->started_on?->toDateString();

                if ($storedStartedOn !== $originalStartedOn) {
                    throw new ConflictHttpException('Nastavení vztahu mezitím změnil jiný uživatel. Obnovte stránku a zkuste to znovu.');
                }

                if ($settings && $storedStartedOn !== $values['started_on'] && ! $request->boolean('confirm_started_on_change')) {
                    throw ValidationException::withMessages([
                        'started_on' => "Zm\u{011b}na po\u{010d}\u{00e1}te\u{010d}n\u{00ed}ho data vy\u{017e}aduje potvrzen\u{00ed}.",
                    ]);
                }

                $timestamp = now();

                RelationshipSettings::query()->upsert([
                    [
                        'id' => RelationshipSettings::SINGLETON_ID,
                        'started_on' => $values['started_on'],
                        'name' => $values['name'] ?? null,
                        'notifications_enabled' => $values['notifications_enabled'],
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ],
                ], ['id'], [
                    'started_on',
                    'name',
                    'notifications_enabled',
                    'updated_by',
                    'updated_at',
                ]);
            });
        });

        return to_route('relationship-settings.index')->with('success', "Nastaven\u{00ed} vztahu bylo ulo\u{017e}eno.");
    }
}
