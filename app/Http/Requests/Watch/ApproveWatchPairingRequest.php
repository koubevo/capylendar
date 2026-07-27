<?php

namespace App\Http\Requests\Watch;

use Illuminate\Foundation\Http\FormRequest;

class ApproveWatchPairingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'user_code' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9-]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_code.required' => 'Zadejte kód zobrazený na hodinkách.',
            'user_code.regex' => 'Kód může obsahovat pouze písmena, číslice a pomlčku.',
        ];
    }
}
