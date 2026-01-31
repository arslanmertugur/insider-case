<?php


namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\LeagueService;
use Illuminate\Http\JsonResponse;

class LeagueController extends Controller
{
    protected $leagueService;

    public function __construct(LeagueService $leagueService)
    {
        $this->leagueService = $leagueService;
    }

    public function groups(): JsonResponse
    {
        $groups = Group::with(['groupTeams.team:id,name,country,pot'])
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'created_at' => $group->created_at,
                    'updated_at' => $group->updated_at,
                    'teams' => $group->groupTeams->map(function ($groupTeam) {
                        return [
                            'id' => $groupTeam->team?->id,
                            'name' => $groupTeam->team?->name,
                            'country' => $groupTeam->team?->country,
                            'pot' => $groupTeam->team?->pot,
                            'stats' => [
                                'played' => $groupTeam->played,
                                'won' => $groupTeam->won,
                                'drawn' => $groupTeam->drawn,
                                'lost' => $groupTeam->lost,
                                'points' => $groupTeam->points,
                                'goals_for' => $groupTeam->goals_for,
                                'goals_against' => $groupTeam->goals_against,
                                'goal_difference' => $groupTeam->goal_difference,
                            ]
                        ];
                    })
                ];
            });

        return response()->json($groups);
    }

    public function draw(): JsonResponse
    {
        $result = $this->leagueService->drawGroups();
        return response()->json($result);
    }
}
