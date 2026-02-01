<?php

namespace App\Http\Controllers;

use App\Services\LeagueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class FixtureController extends Controller
{
    protected $leagueSetupService;
    protected $matchService;
    protected $predictionService;
    protected $groupRepository;
    protected $fixtureRepository;

    public function __construct(
        \App\Services\League\LeagueSetupService $leagueSetupService,
        \App\Services\League\MatchService $matchService,
        \App\Services\League\PredictionService $predictionService,
        \App\Repositories\GroupRepository $groupRepository,
        \App\Repositories\FixtureRepository $fixtureRepository
    ) {
        $this->leagueSetupService = $leagueSetupService;
        $this->matchService = $matchService;
        $this->predictionService = $predictionService;
        $this->groupRepository = $groupRepository;
        $this->fixtureRepository = $fixtureRepository;
    }


    public function generate(): JsonResponse
    {
        try {
            $data = $this->leagueSetupService->generateFixtures();
            
            $formatted = $data->map(function ($weekMatches, $week) {
                return [
                    'week' => $week,
                    'matches' => $weekMatches->map(function ($match) {
                        return [
                            'id' => $match->id,
                            'group' => $match->group->name,
                            'day' => $match->match_day,
                            'home_team' => $match->homeTeam->name,
                            'away_team' => $match->awayTeam->name,
                            'score' => $match->played ? "{$match->home_goals} - {$match->away_goals}" : "TBD",
                            'played' => $match->played
                        ];
                    })
                ];
            })->values();

            return response()->json($formatted);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    public function playNextWeek(): JsonResponse
    {
        try {
            $data = $this->matchService->playNextWeek();

            
            $formattedResults = $data['results']->map(function ($match) {
                return [
                    'id' => $match->id,
                    'group' => $match->group->name, 
                    'day' => $match->match_day,
                    'home_team' => $match->homeTeam->name,
                    'away_team' => $match->awayTeam->name,
                    'score' => $match->played ? "{$match->home_goals} - {$match->away_goals}" : "TBD",
                    'played' => $match->played
                ];
            });

            return response()->json([
                'message' => "{$data['played_week']}. hafta başarıyla oynandı.",
                'status' => 'success',
                'data' => $formattedResults
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function playNextMatch(): JsonResponse
    {
        try {
            $data = $this->matchService->playNextMatch();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function index()
    {
        
        $groups = $this->groupRepository->getGroupsForStandings();

        $grouped = $groups->mapWithKeys(function ($group) {
            return [
                $group->name => $group->groupTeams->map(function ($gt) {
                    return [
                        'team_name' => $gt->team->name,
                        'points' => (int) $gt->points,
                        'played' => (int) $gt->played,
                        'won' => (int) $gt->won,
                        'drawn' => (int) $gt->drawn,
                        'lost' => (int) $gt->lost,
                        'goal_difference' => (int) $gt->goal_difference,
                        'guess' => (int) $gt->guess,
                    ];
                })
            ];
        });

        
        
        
        
        

        
        
        

        return response()->json($grouped);
    }


    public function playAll(): JsonResponse
    {
        $this->matchService->playAllWeeks();
        
        
        
        
        return response()->json(['message' => 'Simulated all weeks']);
    }

    public function getPredictions(): JsonResponse
    {
        return response()->json($this->predictionService->getPredictions());
    }

    public function updateMatch(Request $request, $id): JsonResponse
    {
        $this->matchService->updateMatchAndRecalculate($id, $request->home_goals, $request->away_goals);
        return response()->json(['status' => 'success']);
    }

    public function getAllFixtures()
    {
        
        $groupedByWeek = $this->fixtureRepository->getFixturesGroupedByWeek();

        
        
        

        
        

        
        $fixtures = \App\Models\Fixture::with(['homeTeam', 'awayTeam', 'group'])->get();
        

        
        

        

        $grouped = $fixtures->groupBy(function ($item) {
            return $item->group->name ?? 'Unknown';
        })->map(function ($groupMatches) {
            return $groupMatches->groupBy('week')->map(function ($weekMatches) {
                return $weekMatches->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'home_team_name' => $m->homeTeam->name ?? 'TBD',
                        'away_team_name' => $m->awayTeam->name ?? 'TBD',
                        'home_goals' => (int) $m->home_goals,
                        'away_goals' => (int) $m->away_goals,
                        'played' => (bool) $m->played,
                        'week' => (int) $m->week
                    ];
                });
            });
        });

        return response()->json($grouped);
    }

    public function reset()
    {
        try {
            $this->leagueSetupService->resetLeague();
            return response()->json(['message' => 'League reset successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}