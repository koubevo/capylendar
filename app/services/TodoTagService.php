<?php

namespace App\services;

use App\Models\Tag;
use App\Models\Todo;

class TodoTagService
{
    /**
     * @param  array<Tag>  $tags
     */
    public function assignTags(Todo $todo, array $tags): void
    {
        $todo->tags()->sync($tags);
    }
}
