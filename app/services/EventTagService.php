<?php

namespace App\services;

use App\Models\Event;
use App\Models\Tag;

class EventTagService
{
    /**
     * @param  array<Tag>|null  $tags
     */
    public function assignTags(Event $event, array $tags): void
    {
        if (! empty($tags)) {
            $event->tags()->sync($tags);
        }
    }
}
