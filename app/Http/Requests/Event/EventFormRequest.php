<?php

namespace App\Http\Requests\Event;

use App\Enums\Capybara;
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

        $this->merge([
            'start_at' => $dbStartAt,
            'end_at' => $dbEndAt,
            'is_all_day' => $isAllDay,
            'is_private' => $this->boolean('is_private'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'is_all_day' => ['boolean'],
            'is_private' => ['boolean'],
            'capybara' => ['required', Rule::enum(Capybara::class)],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
