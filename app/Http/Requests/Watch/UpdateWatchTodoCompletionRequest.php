<?php

namespace App\Http\Requests\Watch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWatchTodoCompletionRequest extends FormRequest
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
            'finished' => ['required', 'boolean'],
        ];
    }
}
