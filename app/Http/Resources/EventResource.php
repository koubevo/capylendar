<?php

namespace App\Http\Resources;

use App\Models\Event;
use Carbon\Carbon;
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

        /** @var Carbon $start */
        $start = $this->resource->start_at;

        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,

            'date' => [
                'key' => $start->format('Y-m-d'),
                'is_all_day' => (bool) $this->resource->is_all_day,

                'label' => $this->getHumanDateLabel($start),
                'start_time' => $start->format('H:i'),
                'end_time' => $this->resource->end_at?->format('H:i'),
            ],

            'capybara' => [
                'value' => $this->resource->capybara->value,
                'label' => $capybaraData['label'],
                'classes' => $capybaraData['classes'],
                'avatar' => $capybaraData['avatar'],
            ],
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

        // Vrátí např.: "Pondělí 20. listopadu"
        // 'l' = den slovy, 'j.' = den číslem bez nuly, 'F' = měsíc slovy
        // ucfirst() zajistí velké počáteční písmeno (Pondělí)
        return ucfirst($date->translatedFormat('l j. M'));
    }
}
