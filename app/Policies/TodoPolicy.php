<?php

namespace App\Policies;

use App\Models\Todo;
use App\Models\User;

class TodoPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Todo $todo): bool
    {
        return $this->isSubscriber($user, $todo);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Todo $todo): bool
    {
        return $this->isSubscriber($user, $todo);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Todo $todo): bool
    {
        return $this->isSubscriber($user, $todo);
    }

    /**
     * Determine whether the user can finish the model.
     */
    public function finish(User $user, Todo $todo): bool
    {
        return $this->isSubscriber($user, $todo);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Todo $todo): bool
    {
        return $this->isSubscriber($user, $todo);
    }

    private function isSubscriber(User $user, Todo $todo): bool
    {
        return $todo->subscribers()->where('users.id', $user->id)->exists();
    }
}
