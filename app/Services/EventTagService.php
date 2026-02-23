<?php

namespace App\Services;

use App\Models\Event;

class EventTagService
{
    /**
     * @param  array<int>  $tagIds
     */
    public function assignTags(Event $event, array $tagIds): void
    {
        $event->tags()->sync($tagIds);
    }
}
