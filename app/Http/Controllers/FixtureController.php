<?php

namespace App\Http\Controllers;

use App\Services\LeagueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class FixtureController extends Controller
{
    protected $leagueService;

    public function __construct(LeagueService $leagueService)
    {
        $this->leagueService = $leagueService;
    }


    public function generate(): JsonResponse
    {
        try {
            $data = $this->leagueService->generateFixtures();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    public function playNextWeek(): JsonResponse
    {
        try {
            $data = $this->leagueService->playNextWeek();

            return response()->json([
                'message' => "{$data['played_week']}. hafta başarıyla oynandı.",
                'status' => 'success',
                'data' => $data['results']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }


    public function index()
    {
        // İlişkileri yükle (group ve team tabloları bağlı olmalı)
        $groupTeams = \App\Models\GroupTeam::with(['team', 'group'])->get();

        $grouped = $groupTeams->groupBy(function ($item) {
            // Eğer gruptan isim gelmiyorsa 'A' olarak default ata veya hata ayıkla
            return $item->group->name ?? 'Unknown';
        })->map(function ($groupItems) {
            return $groupItems->map(function ($gt) {
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
            })->sortByDesc('points')
                ->values(); // Sıralamayı koru ve array'e çevir
        });

        return response()->json($grouped);
    }


    public function playAll(): JsonResponse
    {
        return response()->json($this->leagueService->playAllWeeks());
    }

    public function getPredictions(): JsonResponse
    {
        return response()->json($this->leagueService->getPredictions());
    }

    public function updateMatch(Request $request, $id): JsonResponse
    {
        $this->leagueService->updateMatchAndRecalculate($id, $request->home_goals, $request->away_goals);
        return response()->json(['status' => 'success']);
    }
    public function getAllFixtures()
    {
        // İlişkileri çekiyoruz (homeTeam, awayTeam ve group tabloları bağlı olmalı)
        $fixtures = \App\Models\Fixture::with(['homeTeam', 'awayTeam', 'group'])->get();

        $grouped = $fixtures->groupBy(function ($item) {
            // Birinci seviye gruplama: Grup İsmi (A, B, C...)
            return $item->group->name ?? 'Unknown';
        })->map(function ($groupMatches) {
            // İkinci seviye gruplama: Hafta (1, 2, 3...)
            return $groupMatches->groupBy('week')->map(function ($weekMatches) {
                // Her haftanın içindeki maç detaylarını sadeleştiriyoruz
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
            DB::beginTransaction();

            // 1. Tüm maç skorlarını ve 'played' durumunu sıfırla
            DB::table('fixture')->update([
                'home_goals' => 0,
                'away_goals' => 0,
                'played' => 0,
            ]);

            // 2. Takımların puanlarını ve istatistiklerini sıfırla
            DB::table('group_teams')->update([
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 0,
                'guess' => 0
            ]);

            DB::commit();

            return response()->json(['message' => 'League reset successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}