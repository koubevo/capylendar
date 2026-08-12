<?php

namespace App\Http\Requests\Settings;

use App\Models\RelationshipSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateRelationshipSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'started_on' => ['required', 'date', 'before_or_equal:today'],
            'name' => ['nullable', 'string', 'max:120'],
            'notifications_enabled' => ['required', 'boolean'],
            'confirm_started_on_change' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $settings = RelationshipSettings::current();
            $startedOn = $this->string('started_on')->toString();

            if ($settings?->started_on
                && $settings->started_on->toDateString() !== $startedOn
                && ! $this->boolean('confirm_started_on_change')) {
                $validator->errors()->add('started_on', "Zm\u{011b}na po\u{010d}\u{00e1}te\u{010d}n\u{00ed}ho data vy\u{017e}aduje potvrzen\u{00ed}.");
            }
        });
    }
}
