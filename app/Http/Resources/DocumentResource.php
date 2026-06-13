<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @property Document $resource
 */
class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'body' => $this->resource->body,
            'excerpt' => Str::limit(
                trim(preg_replace('/\s+/', ' ', $this->resource->body) ?? ''),
                140
            ),
            'author' => [
                'id' => $this->resource->author->id,
                'name' => $this->resource->author->name,
                'capybara' => $this->resource->author->capybara,
            ],
            'created_at_human' => $this->resource->created_at_human,
            'updated_at_human' => $this->resource->updated_at_human,
        ];
    }
}
