<?php

namespace App\Http\Controllers;

use App\Enums\ReactionType;
use App\Http\Requests\ReactionRequest;
use App\Models\Todo;
use App\Models\User;
use App\Services\ReactionService;
use Illuminate\Http\JsonResponse;

class TodoReactionController extends Controller
{
    public function __construct(private ReactionService $reactionService) {}

    public function store(ReactionRequest $request, Todo $todo, string $reaction): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'reactions' => $this->reactionService->addReaction($todo, $user, ReactionType::from($reaction)),
        ]);
    }

    public function destroy(ReactionRequest $request, Todo $todo, string $reaction): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'reactions' => $this->reactionService->removeReaction($todo, $user, ReactionType::from($reaction)),
        ]);
    }
}
