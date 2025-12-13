<?php

namespace App\services;

use App\Models\Tag;

class TagService
{
    /**
     * @param  array<string, string>  $orderBy
     * @return array<mixed>
     */
    public function getAvailableTags(array $orderBy = ['by' => 'label', 'order' => 'asc']): array
    {
        return Tag::orderBy($orderBy['by'], $orderBy['order'])->get()->toArray();
    }
}
