<?php

namespace App\Http\Requests\Todo;

use App\Enums\Capybara;
use App\Enums\Priority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class TodoFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'deadline' => ['required', 'date'],
            'is_private' => ['boolean'],
            'capybara' => ['required', Rule::enum(Capybara::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],

        ];
    }
}
