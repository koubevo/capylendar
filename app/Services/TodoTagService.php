<?php

namespace App\Services;

use App\Models\Todo;

class TodoTagService
{
    /**
     * @param  array<int>  $tagIds
     */
    public function assignTags(Todo $todo, array $tagIds): void
    {
        $todo->tags()->sync($tagIds);
    }
}
