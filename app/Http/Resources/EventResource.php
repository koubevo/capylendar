<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * @var Event
     */
    public $resource;

    public function __construct(Event $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $capybaraData = $this->resource->capybara->info();

        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,

            'date' => $this->resource->start_at->format('Y-m-d'),

            'start_at' => $this->resource->start_at->format('H:i'),

            'end_at' => $this->resource->end_at?->format('H:i'),

            'is_all_day' => $this->resource->is_all_day,

            'capybara' => [
                'value' => $this->resource->capybara->value,
                'label' => $capybaraData['label'],
                'classes' => $capybaraData['classes'],
                'avatar' => $capybaraData['avatar'],
            ],
        ];
    }
}
