<?php

namespace App\Http\Controllers;

use App\Enums\ReactionType;
use App\Http\Requests\ReactionRequest;
use App\Models\Event;
use App\Models\User;
use App\Services\ReactionService;
use Illuminate\Http\JsonResponse;

class EventReactionController extends Controller
{
    public function __construct(private ReactionService $reactionService) {}

    public function store(ReactionRequest $request, Event $event, string $reaction): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'reactions' => $this->reactionService->addReaction($event, $user, ReactionType::from($reaction)),
        ]);
    }

    public function destroy(ReactionRequest $request, Event $event, string $reaction): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'reactions' => $this->reactionService->removeReaction($event, $user, ReactionType::from($reaction)),
        ]);
    }
}
