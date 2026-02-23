<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Services\MessageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function __construct(protected MessageService $messageService) {}

    public function index(): Response
    {
        return Inertia::render('Chat', [
            'messages' => $this->messageService->getMessages(),
        ]);
    }

    public function store(StoreMessageRequest $request): RedirectResponse
    {
        $this->messageService->store($request);

        return redirect()->route('chat.index');
    }
}
