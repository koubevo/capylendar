<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendWakeNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:morning,evening'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Typ notifikace musí být morning nebo evening.',
        ];
    }
}
