<?php

namespace App\services;

use App\Models\Event;
use App\Models\Tag;

class EventTagService
{
    /**
     * @param  array<Tag>  $tags
     */
    public function assignTags(Event $event, array $tags): void
    {
        $event->tags()->sync($tags);
    }
}
