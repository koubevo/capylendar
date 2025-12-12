<?php

namespace App\services;

use App\Models\Tag;

class TagService
{
    /**
     * @return array<mixed>
     */
    public function getAvailableTags(): array
    {
        return Tag::all()->toArray();
    }
}
