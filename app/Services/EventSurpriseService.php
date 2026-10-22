<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EventSurpriseService
{
    private const SESSION_KEY = 'event_surprise_unlocked';

    public function hashPassword(string $password): string
    {
        return Hash::make($this->normalizePassword($password));
    }

    public function unlock(Event $event, string $password, Session $session): bool
    {
        if (! $event->surprise_image_path || ! $event->surprise_password) {
            return false;
        }

        if (! Hash::check($this->normalizePassword($password), $event->surprise_password)) {
            return false;
        }

        $session->put($this->sessionKey($event), $event->surprise_image_path);

        return true;
    }

    /**
     * An unlock is bound to the stored image, so a replaced surprise is locked again.
     */
    public function isUnlocked(Event $event, ?Session $session): bool
    {
        if (! $session || ! $event->surprise_image_path) {
            return false;
        }

        return $session->get($this->sessionKey($event)) === $event->surprise_image_path;
    }

    private function normalizePassword(string $password): string
    {
        return Str::lower(Str::squish($password));
    }

    private function sessionKey(Event $event): string
    {
        return self::SESSION_KEY.'.'.$event->id;
    }
}
