<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateRelationshipSettingsRequest;
use App\Models\RelationshipSettings;
use App\Services\RelationshipMilestoneService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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

        $values = $request->safe()->except('confirm_started_on_change');
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

        return to_route('relationship-settings.index')->with('success', "Nastaven\u{00ed} vztahu bylo ulo\u{017e}eno.");
    }
}
