<?php

namespace App\services;

use App\Models\Event;
use App\Models\User;

class EventUserService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function assignSubscribers(Event $event, bool $isPrivateEvent, User $author): void
    {
        if ($isPrivateEvent) {
            $event->subscribers()->attach($author);
        } else {
            $event->subscribers()->attach(User::pluck('id'));
        }
    }
}
