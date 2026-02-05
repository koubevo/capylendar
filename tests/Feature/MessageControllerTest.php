<?php

use App\Models\Message;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('MessageController index', function () {
    it('renders the chat page', function () {
        $this->actingAs($this->user)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Chat')
                ->has('messages')
            );
    });

    it('displays messages in chronological order', function () {
        $oldMessage = Message::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subHour(),
        ]);
        $newMessage = Message::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Chat')
                ->has('messages', 2)
                ->where('messages.0.id', $oldMessage->id)
                ->where('messages.1.id', $newMessage->id)
            );
    });

    it('includes user info with messages', function () {
        Message::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Chat')
                ->has('messages.0.user')
                ->has('messages.0.user.name')
                ->has('messages.0.user.capybara')
            );
    });

    it('requires authentication', function () {
        $this->get(route('chat.index'))
            ->assertRedirect(route('login'));
    });
});

describe('MessageController store', function () {
    it('stores a new message', function () {
        $this->actingAs($this->user)
            ->post(route('chat.store'), [
                'content' => 'Hello, partner!',
            ])
            ->assertRedirect(route('chat.index'));

        $this->assertDatabaseHas('messages', [
            'user_id' => $this->user->id,
            'content' => 'Hello, partner!',
        ]);
    });

    it('requires authentication to store message', function () {
        $this->post(route('chat.store'), [
            'content' => 'Hello!',
        ])
            ->assertRedirect(route('login'));
    });
});

describe('MessageController store validation', function () {
    it('requires content', function () {
        $this->actingAs($this->user)
            ->post(route('chat.store'), [])
            ->assertSessionHasErrors('content');
    });

    it('validates content max length', function () {
        $this->actingAs($this->user)
            ->post(route('chat.store'), [
                'content' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors('content');
    });

    it('accepts content at max length', function () {
        $this->actingAs($this->user)
            ->post(route('chat.store'), [
                'content' => str_repeat('a', 1000),
            ])
            ->assertRedirect(route('chat.index'));

        $this->assertDatabaseCount('messages', 1);
    });
});
