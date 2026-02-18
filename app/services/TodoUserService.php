<?php

namespace App\services;

use App\Models\Todo;
use App\Models\User;

class TodoUserService
{
    public function assignSubscribers(Todo $todo, bool $isPrivateTodo, User $author): void
    {
        if ($isPrivateTodo) {
            $todo->subscribers()->sync($author);
        } else {
            $todo->subscribers()->sync(User::pluck('id'));
        }
    }
}
