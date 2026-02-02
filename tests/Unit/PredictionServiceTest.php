<?php

namespace Tests\Unit;

use App\Services\League\PredictionService;
use App\Repositories\GroupRepository;
use App\Models\GroupTeam;
use App\Models\Team;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Mockery;

class PredictionServiceTest extends TestCase
{
    private PredictionService $predictionService;
    private $groupRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->groupRepository = Mockery::mock(GroupRepository::class);
        $this->predictionService = new PredictionService($this->groupRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_calculates_predictions_in_memory()
    {
        $teams = $this->createMockTeams();

        $this->predictionService->calculatePredictionsInMemory($teams, 1);

        // Check that predictions were set
        foreach ($teams as $team) {
            $this->assertIsInt($team->guess);
            $this->assertGreaterThanOrEqual(0, $team->guess);
            $this->assertLessThanOrEqual(100, $team->guess);
        }
    }

    /** @test */
    public function predictions_sum_to_100_percent()
    {
        $teams = $this->createMockTeams();

        $this->predictionService->calculatePredictionsInMemory($teams, 1);

        $total = 0;
        foreach ($teams as $team) {
            $total += $team->guess;
        }

        $this->assertEquals(100, $total);
    }

    /** @test */
    public function no_predictions_before_week_4()
    {
        $teams = collect([
            $this->createMockTeam(1, 'Team A', 3, 9), // Only 3 games played
            $this->createMockTeam(2, 'Team B', 3, 6),
            $this->createMockTeam(3, 'Team C', 3, 3),
            $this->createMockTeam(4, 'Team D', 3, 0),
        ]);

        $this->predictionService->calculatePredictionsInMemory($teams, 1);

        foreach ($teams as $team) {
            $this->assertEquals(0, $team->guess);
        }
    }

    /** @test */
    public function stronger_teams_get_higher_predictions()
    {
        $teams = collect([
            $this->createMockTeam(1, 'Strong Team', 4, 12, 95), // High strength
            $this->createMockTeam(2, 'Medium Team', 4, 6, 75),
            $this->createMockTeam(3, 'Weak Team', 4, 3, 60),
            $this->createMockTeam(4, 'Very Weak', 4, 0, 50),
        ]);

        $this->predictionService->calculatePredictionsInMemory($teams, 1);

        $strongTeam = $teams->firstWhere('team_id', 1);
        $weakTeam = $teams->firstWhere('team_id', 4);

        $this->assertGreaterThan($weakTeam->guess, $strongTeam->guess);
    }

    /** @test */
    public function team_with_more_points_gets_higher_prediction()
    {
        $teams = collect([
            $this->createMockTeam(1, 'Leader', 4, 12, 80),
            $this->createMockTeam(2, 'Second', 4, 9, 80),
            $this->createMockTeam(3, 'Third', 4, 6, 80),
            $this->createMockTeam(4, 'Last', 4, 3, 80),
        ]);

        $this->predictionService->calculatePredictionsInMemory($teams, 1);

        $leader = $teams->firstWhere('team_id', 1);
        $last = $teams->firstWhere('team_id', 4);

        $this->assertGreaterThan($last->guess, $leader->guess);
    }

    /** @test */
    public function goal_difference_affects_predictions()
    {
        $teams = collect([
            $this->createMockTeam(1, 'Team A', 4, 9, 80, 5),  // +5 GD
            $this->createMockTeam(2, 'Team B', 4, 9, 80, 0),  // 0 GD
            $this->createMockTeam(3, 'Team C', 4, 9, 80, -2), // -2 GD
            $this->createMockTeam(4, 'Team D', 4, 0, 80, -3),
        ]);

        $this->predictionService->calculatePredictionsInMemory($teams, 1);

        $teamA = $teams->firstWhere('team_id', 1);
        $teamC = $teams->firstWhere('team_id', 3);

        $this->assertGreaterThan($teamC->guess, $teamA->guess);
    }

    /** @test */
    public function remaining_games_affect_predictions()
    {
        // Team with fewer games played has more potential
        $teams = collect([
            $this->createMockTeam(1, 'Team A', 4, 9, 90), // 2 games left
            $this->createMockTeam(2, 'Team B', 5, 9, 90), // 1 game left
            $this->createMockTeam(3, 'Team C', 4, 6, 80),
            $this->createMockTeam(4, 'Team D', 4, 3, 70),
        ]);

        $this->predictionService->calculatePredictionsInMemory($teams, 1);

        $teamA = $teams->firstWhere('team_id', 1);
        $teamB = $teams->firstWhere('team_id', 2);

        // Team A has more games left, so more potential
        $this->assertGreaterThanOrEqual($teamB->guess, $teamA->guess);
    }

    /** @test */
    public function handles_equal_teams_gracefully()
    {
        $teams = collect([
            $this->createMockTeam(1, 'Team A', 4, 6, 80),
            $this->createMockTeam(2, 'Team B', 4, 6, 80),
            $this->createMockTeam(3, 'Team C', 4, 6, 80),
            $this->createMockTeam(4, 'Team D', 4, 6, 80),
        ]);

        $this->predictionService->calculatePredictionsInMemory($teams, 1);

        // All teams should have equal or very close predictions
        $predictions = $teams->pluck('guess')->toArray();
        $this->assertCount(4, $predictions);

        // Total should still be 100
        $this->assertEquals(100, array_sum($predictions));
    }

    private function createMockTeams(): Collection
    {
        return collect([
            $this->createMockTeam(1, 'Team A', 4, 12, 90),
            $this->createMockTeam(2, 'Team B', 4, 9, 85),
            $this->createMockTeam(3, 'Team C', 4, 6, 75),
            $this->createMockTeam(4, 'Team D', 4, 3, 70),
        ]);
    }

    private function createMockTeam(
        int $id,
        string $name,
        int $played,
        int $points,
        int $strength = 80,
        int $goalDifference = 0
    ): object {
        $team = new \stdClass();
        $team->id = $id;
        $team->team_id = $id;
        $team->group_id = 1;
        $team->played = $played;
        $team->points = $points;
        $team->goal_difference = $goalDifference;
        $team->guess = 0;

        // Mock team relationship
        $mockTeam = new \stdClass();
        $mockTeam->strength = $strength;
        $mockTeam->name = $name;
        $team->team = $mockTeam;

        return $team;
    }
}
