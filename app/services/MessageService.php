<?php

namespace App\services;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\User;
use App\Notifications\ChatMessageNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        $message = Message::create([
            'user_id' => $user->id,
            'content' => $request->validated('content'),
        ]);

        $this->notifyOtherUsers($message, $user);

        return $message;
    }

    private function notifyOtherUsers(Message $message, User $sender): void
    {
        $recipients = User::query()
            ->where('id', '!=', $sender->id)
            ->where('notifications_enabled', true)
            ->whereHas('pushSubscriptions')
            ->get();

        foreach ($recipients as $recipient) {
            try {
                $recipient->notify(new ChatMessageNotification($message));
            } catch (Throwable $e) {
                Log::error('Failed to send chat notification', [
                    'user_id' => $recipient->id,
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
