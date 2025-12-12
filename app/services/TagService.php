<?php

namespace App\services;

use App\Models\Tag;

class TagService
{
    /**
     * @return array<Tag>
     */
    public function getAvailableTags(): array
    {
        return Tag::all()->toArray();
    }
}
