<?php

namespace App\Http\Requests\Event;

use Illuminate\Support\Facades\Gate;

class UpdateEventRequest extends EventFormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');

        return $event && Gate::allows('update', $event);
    }
}
