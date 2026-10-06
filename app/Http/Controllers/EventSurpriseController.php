<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\UnlockEventSurpriseRequest;
use App\Models\Event;
use App\Services\EventSurpriseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventSurpriseController extends Controller
{
    public function __construct(private EventSurpriseService $eventSurpriseService) {}

    public function show(Request $request, Event $event): StreamedResponse
    {
        Gate::authorize('view', $event);

        if (! $event->surprise_image_path || ! Storage::disk()->exists($event->surprise_image_path)) {
            abort(404);
        }

        if (! $this->eventSurpriseService->isUnlocked($event, $request->session())) {
            abort(403);
        }

        return Storage::disk()->response($event->surprise_image_path, headers: [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function unlock(UnlockEventSurpriseRequest $request, Event $event): Response
    {
        if (! $event->surprise_image_path) {
            abort(404);
        }

        if (! $this->eventSurpriseService->unlock($event, $request->string('password')->toString(), $request->session())) {
            throw ValidationException::withMessages([
                'password' => 'Tohle heslo není správně. Zkus to znovu.',
            ]);
        }

        return response()->noContent();
    }
}
