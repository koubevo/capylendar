<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;

class EventUserService
{
    public function assignSubscribers(Event $event, bool $isPrivateEvent, User $author): void
    {
        if ($isPrivateEvent) {
            $event->subscribers()->sync($author);
        } else {
            // Intentional: sync all users for non-private events in this small-scale app
            $event->subscribers()->sync(User::pluck('id'));
        }
    }
}
