<?php

namespace App\Services;

use App\Models\Todo;
use App\Models\User;

class TodoUserService
{
    public function assignSubscribers(Todo $todo, bool $isPrivateTodo, User $author): void
    {
        if ($isPrivateTodo) {
            $todo->subscribers()->sync($author);
        } else {
            // Intentional: sync all users for non-private todos in this small-scale app
            $todo->subscribers()->sync(User::pluck('id'));
        }
    }
}
