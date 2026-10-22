<?php

namespace App\Services;

use App\Enums\ReactionType;
use App\Models\Event;
use App\Models\Reaction;
use App\Models\Todo;
use App\Models\User;

class ReactionService
{
    /** @return list<array{key: string, users: list<array{id: int, name: string, capybara: string}>}> */
    public function addReaction(Event|Todo $item, User $user, ReactionType $key): array
    {
        $item->reactions()->createOrFirst(['user_id' => $user->id, 'key' => $key->value]);

        return $this->summarize($item->load('reactions.user:id,name,capybara'));
    }

    /** @return list<array{key: string, users: list<array{id: int, name: string, capybara: string}>}> */
    public function removeReaction(Event|Todo $item, User $user, ReactionType $key): array
    {
        $item->reactions()->where('user_id', $user->id)->where('key', $key->value)->delete();

        return $this->summarize($item->load('reactions.user:id,name,capybara'));
    }

    /** @return list<array{key: string, users: list<array{id: int, name: string, capybara: string}>}> */
    public function summarize(Event|Todo $item): array
    {
        $summary = [];

        foreach (ReactionType::cases() as $type) {
            $users = $item->reactions
                ->filter(fn (Reaction $reaction): bool => $reaction->key === $type)
                ->sortBy('user_id')
                ->map(fn (Reaction $reaction): array => [
                    'id' => $reaction->user->id,
                    'name' => $reaction->user->name,
                    'capybara' => $reaction->user->capybara->value,
                ])->values()->all();

            if ($users !== []) {
                $summary[] = ['key' => $type->value, 'users' => array_values($users)];
            }
        }

        return $summary;
    }
}
