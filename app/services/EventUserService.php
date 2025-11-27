<?php

namespace App\services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EventUserService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function assignSubscribers(Event $event, bool $isPrivateEvent): void
    {
        if ($isPrivateEvent) {
            $event->subscribers()->attach(Auth::user());
        } else {
            $event->subscribers()->attach(User::pluck('id'));
        }
    }
}
