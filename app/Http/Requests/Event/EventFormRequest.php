<?php

namespace App\Http\Requests\Event;

use App\Enums\Capybara;
use App\Enums\EventKind;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class EventFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $date = $this->string('date')->toString();
        $startTime = $this->string('start_at')->toString();

        $endTime = $this->input('end_at');
        $isAllDay = $this->boolean('is_all_day');

        $dbStartAt = $isAllDay ? "{$date} 00:00:00" : "{$date} {$startTime}:00";

        if ($isAllDay || ! is_string($endTime) || $endTime == '') {
            $dbEndAt = null;
        } else {
            $dbEndAt = "{$date} {$endTime}:00";
        }

        $event = $this->route('event');
        $currentKind = $event instanceof Event ? $event->kind : EventKind::Standard;

        $this->merge([
            'kind' => $this->input('kind') ?? $currentKind->value,
            'remove_surprise_image' => $this->boolean('remove_surprise_image'),
            'start_at' => $dbStartAt,
            'end_at' => $dbEndAt,
            'is_all_day' => $isAllDay,
            'is_private' => $this->boolean('is_private'),
            'countdown_enabled' => $this->boolean('countdown_enabled'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $kind = $this->input('kind');
        $allowsSurprise = is_string($kind) && (EventKind::tryFrom($kind)?->allowsSurprise() ?? false);

        return [
            'title' => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'is_all_day' => ['boolean'],
            'is_private' => ['boolean'],
            'countdown_enabled' => ['boolean'],
            'capybara' => ['required', Rule::enum(Capybara::class)],
            'description' => ['nullable', 'string', 'max:20000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'image' => [
                'nullable',
                'image',
                'max:5120',
                Rule::dimensions()->maxWidth(6000)->maxHeight(6000),
            ],
            'remove_image' => ['boolean'],
            'kind' => ['required', Rule::enum(EventKind::class)],
            'surprise_image' => [
                'nullable',
                Rule::prohibitedIf(! $allowsSurprise),
                'image',
                'max:5120',
                Rule::dimensions()->maxWidth(6000)->maxHeight(6000),
            ],
            'surprise_password' => ['exclude_without:surprise_image', 'required', 'string', 'max:255'],
            'surprise_hint' => ['nullable', 'string', 'max:255'],
            'remove_surprise_image' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'surprise_image.prohibited' => 'Tajný obrázek jde přidat jen k narozeninovému eventu.',
            'surprise_password.required' => 'K tajnému obrázku nastav heslo.',
        ];
    }
}
