<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RelationshipSettings;
use App\Services\RelationshipMilestoneService;
use Illuminate\Http\JsonResponse;

class RelationshipSummaryController extends Controller
{
    public function __construct(private readonly RelationshipMilestoneService $milestones) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => $this->milestones->summary(RelationshipSettings::current()),
        ]);
    }
}
