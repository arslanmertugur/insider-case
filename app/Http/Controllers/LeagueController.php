<?php


namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\LeagueService;
use Illuminate\Http\JsonResponse;

class LeagueController extends Controller
{
    protected $leagueSetupService;
    protected $groupRepository;

    public function __construct(\App\Services\League\LeagueSetupService $leagueSetupService, \App\Repositories\GroupRepository $groupRepository)
    {
        $this->leagueSetupService = $leagueSetupService;
        $this->groupRepository = $groupRepository;
    }

    public function groups(): JsonResponse
    {
        // Use Repository
        $groups = $this->groupRepository->getAllGroupsWithTeams()
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
        $result = $this->leagueSetupService->drawGroups();
        // The service returns the groups, but the original drawGroups returned $this->getGroups() which is same structure.
        // We need to make sure the structure matches what front-end expects.
        // LeagueSetupService::drawGroups returns exactly that structure.

        // We need to map it again if the service returns raw models, but let's check LeagueService
        // Original returned $this->getGroups() which did the mapping.
        // LeagueSetupService::drawGroups returns raw Collection.
        // Let's just reuse the mapping logic for now or update Service to return mapped data.
        // Actually, LeagueSetupService::drawGroups returns what groupRepository->getAllGroupsWithTeams() returns.
        // So we need to map it here to match the JSON structure expected by frontend.

        $mapped = $result->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'created_at' => $group->created_at,
                'updated_at' => $group->updated_at,
                'teams' => $group->groupTeams->map(function ($groupTeam) {
                    return [
                        'id' => $groupTeam->team->id,
                        'name' => $groupTeam->team->name,
                        'country' => $groupTeam->team->country,
                        'pot' => $groupTeam->team->pot,
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
                })->sortByDesc('stats.points')->values()
            ];
        });

        return response()->json($mapped);
    }
}
