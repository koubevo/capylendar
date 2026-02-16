<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventImageController extends Controller
{
    public function show(Event $event): StreamedResponse
    {
        Gate::authorize('view', $event);

        if (! $event->image_path || ! Storage::disk()->exists($event->image_path)) {
            abort(404);
        }

        return Storage::disk()->response($event->image_path);
    }
}
