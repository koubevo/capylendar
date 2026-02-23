<?php

namespace App\Http\Resources;

use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @property Todo $resource
 */
class TodoResource extends JsonResource
{
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

        /** @var Carbon $deadline */
        $deadline = $this->resource->deadline;

        $meta = $this->resource->meta;
        $description = $this->resource->description;
        $descriptionWithoutMeta = $this->getDescriptionWithoutMeta($description, $meta);

        $priorityEnum = $this->resource->priority;

        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'description' => $description,
            'description_without_meta' => $descriptionWithoutMeta ?? $description,
            'is_private' => $this->resource->is_private,
            'has_hearts' => $hasHearts,
            'author' => [
                'id' => $this->resource->author->id,
                'name' => $this->resource->author->name,
                'capybara' => $this->resource->author->capybara,
            ],
            'meta' => $this->resource->meta,
            'has_map_meta' => ! empty($meta['map_preview']),

            'deadline' => [
                'key' => $deadline->format('Y-m-d'),
                'label' => $this->getHumanDateLabel($deadline),
            ],

            'priority' => [
                'value' => $priorityEnum->value,
                'label' => $priorityEnum->label(),
                'icon' => $priorityEnum->icon(),
                'border_class' => $priorityEnum->borderClass(),
                'icon_color' => $priorityEnum->iconColor(),
                'checkbox_color' => $priorityEnum->checkboxColor(),
            ],

            'capybara' => [
                'value' => $this->resource->capybara->value,
                'label' => $capybaraData['label'],
                'classes' => $capybaraData['classes'],
                'link_classes' => $capybaraData['link_classes'],
                'avatar' => $capybaraData['avatar'],
            ],

            'tags' => $this->resource->tags ?? [],

            'is_finished' => $this->resource->is_finished,
            'finished_at' => $this->resource->finished_at?->toISOString(),

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

    /**
     * @param  array<string, array<string, string>>|null  $meta
     */
    private function getDescriptionWithoutMeta(?string $description, ?array $meta): ?string
    {
        $descriptionWithoutMeta = null;

        if (! empty($meta['map_preview']['url'])) {
            $descriptionWithoutMeta = str_replace($meta['map_preview']['url'], '', $description ?? '');
            $descriptionWithoutMeta = trim($descriptionWithoutMeta);
        }

        return $descriptionWithoutMeta;
    }
}
