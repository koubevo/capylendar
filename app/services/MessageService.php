<?php

namespace App\services;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;

class MessageService
{
    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, content: string, created_at_human: string, user: array{id: int, name: string, capybara: array<string, mixed>}}>
     */
    public function getMessages(): \Illuminate\Support\Collection
    {
        return Message::query()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn (Message $message) => [
                'id' => $message->id,
                'content' => $message->content,
                'created_at_human' => $message->created_at_human,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'capybara' => $message->user->capybara->info(),
                ],
            ]);
    }

    public function store(StoreMessageRequest $request): ?Message
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return null;
        }

        return Message::create([
            'user_id' => $user->id,
            'content' => $request->validated('content'),
        ]);
    }
}
