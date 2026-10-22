<?php

namespace App\Http\Requests;

use App\Enums\ReactionType;
use App\Models\Event;
use App\Models\Todo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $item = $this->route('event') ?? $this->route('todo');

        return ($item instanceof Event || $item instanceof Todo) && Gate::allows('view', $item);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', Rule::enum(ReactionType::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['key.enum' => 'Vyberte jednu z podporovaných reakcí.'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['key' => $this->route('reaction')]);
    }
}
