<?php

namespace App\Services;

use App\Models\Tag;

class TagService
{
    /**
     * @param  array{by: string, order: 'asc'|'desc'}  $orderBy
     * @return array<mixed>
     */
    public function getAvailableTags(array $orderBy = ['by' => 'label', 'order' => 'asc']): array
    {
        return Tag::orderBy($orderBy['by'], $orderBy['order'])->get()->toArray();
    }
}
