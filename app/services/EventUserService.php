<?php

namespace App\services;

use App\Models\Event;
use App\Models\User;

class EventUserService
{
    public function assignSubscribers(Event $event, bool $isPrivateEvent, User $author): void
    {
        if ($isPrivateEvent) {
            $event->subscribers()->sync($author);
        } else {
            $event->subscribers()->sync(User::pluck('id'));
        }
    }
}
