<?php

namespace App\Http\Resources;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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

        /** @var array<string> */
        $heartKeywords = config('app.heart_keywords', []);

        $hasHearts = Str::contains(
            Str::lower($this->resource->title),
            $heartKeywords
        );

        /** @var Carbon $start */
        $start = $this->resource->start_at;

        $meta = $this->resource->meta;
        $description = $this->resource->description;

        if (! empty($meta['map_preview']['url'])) {
            $description = str_replace($meta['map_preview']['url'], '', $description ?? '');
            $description = trim($description);
        }

        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'description' => $description,
            'is_private' => $this->resource->is_private,
            'has_hearts' => $hasHearts,
            'author' => [
                'id' => $this->resource->author->id,
                'name' => $this->resource->author->name,
                'capybara' => $this->resource->author->capybara,
            ],
            'meta' => $this->resource->meta,
            'has_map_meta' => ! empty($meta['map_preview']),

            'date' => [
                'key' => $start->format('Y-m-d'),
                'is_all_day' => (bool) $this->resource->is_all_day,

                'label' => $this->getHumanDateLabel($start),
                'start_time' => $this->resource->is_all_day ? '' : $start->format('H:i'),
                'end_time' => $this->resource->end_at?->format('H:i'),
            ],

            'capybara' => [
                'value' => $this->resource->capybara->value,
                'label' => $capybaraData['label'],
                'classes' => $capybaraData['classes'],
                'avatar' => $capybaraData['avatar'],
            ],

            'created_at_human' => $this->resource->created_at_human,
            'updated_at_human' => $this->resource->updated_at_human,
        ];
    }

    private function getHumanDateLabel(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'Dnes';
        }

        if ($date->isTomorrow()) {
            return 'Zítra';
        }

        return ucfirst($date->translatedFormat('l d.m.y'));
    }
}
