<?php

namespace App\Http\Requests;

use App\Rules\PublicPushEndpoint;
use Illuminate\Foundation\Http\FormRequest;

class StorePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'max:2048', new PublicPushEndpoint],
            'keys.auth' => ['required', 'string', 'max:512'],
            'keys.p256dh' => ['required', 'string', 'max:1024'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'endpoint.required' => 'Push endpoint chybí.',
            'endpoint.max' => 'Push endpoint je příliš dlouhý.',
            'keys.auth.required' => 'Autorizační klíč push subscription chybí.',
            'keys.p256dh.required' => 'Veřejný klíč push subscription chybí.',
        ];
    }
}
