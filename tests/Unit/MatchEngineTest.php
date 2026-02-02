<?php

namespace Tests\Unit;

use App\Domain\Simulation\MatchEngine;
use App\Models\Team;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MatchEngineTest extends TestCase
{
    private MatchEngine $matchEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matchEngine = new MatchEngine();
    }

    /** @test */
    public function it_simulates_match_and_returns_valid_goals()
    {
        $homeTeam = new Team([
            'id' => 1,
            'name' => 'Manchester City',
            'attack' => 90,
            'defense' => 85,
            'goalkeeper' => 88,
            'power' => 92,
            'supporter' => 10
        ]);

        $awayTeam = new Team([
            'id' => 2,
            'name' => 'Bayern Munich',
            'attack' => 88,
            'defense' => 83,
            'goalkeeper' => 86,
            'power' => 90,
            'supporter' => 0
        ]);

        $groupTeams = collect();

        $result = $this->matchEngine->simulateMatch($homeTeam, $awayTeam, $groupTeams, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('home_goals', $result);
        $this->assertArrayHasKey('away_goals', $result);
        $this->assertIsInt($result['home_goals']);
        $this->assertIsInt($result['away_goals']);
        $this->assertGreaterThanOrEqual(0, $result['home_goals']);
        $this->assertGreaterThanOrEqual(0, $result['away_goals']);
    }

    /** @test */
    public function it_generates_realistic_goals_using_poisson_distribution()
    {
        $reflection = new \ReflectionClass($this->matchEngine);
        $method = $reflection->getMethod('generateRealisticGoals');
        $method->setAccessible(true);

        // Test multiple times to ensure randomness
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $goals = $method->invoke($this->matchEngine, 2.5); // Average xG
            $results[] = $goals;
        }

        // Goals should be non-negative
        foreach ($results as $goals) {
            $this->assertGreaterThanOrEqual(0, $goals);
            $this->assertLessThanOrEqual(10, $goals); // Max 10 goals
        }

        // Average should be close to xG (within reasonable range)
        $average = array_sum($results) / count($results);
        $this->assertGreaterThan(1.5, $average);
        $this->assertLessThan(3.5, $average);
    }

    /** @test */
    public function it_never_generates_negative_goals()
    {
        $reflection = new \ReflectionClass($this->matchEngine);
        $method = $reflection->getMethod('generateRealisticGoals');
        $method->setAccessible(true);

        // Test with very low xG
        for ($i = 0; $i < 50; $i++) {
            $goals = $method->invoke($this->matchEngine, 0.1);
            $this->assertGreaterThanOrEqual(0, $goals);
        }

        // Test with very high xG
        for ($i = 0; $i < 50; $i++) {
            $goals = $method->invoke($this->matchEngine, 10.0);
            $this->assertGreaterThanOrEqual(0, $goals);
        }
    }

    /** @test */
    public function it_applies_home_advantage()
    {
        $homeTeam = new Team([
            'id' => 1,
            'name' => 'Team A',
            'attack' => 80,
            'defense' => 80,
            'goalkeeper' => 80,
            'power' => 80,
            'supporter' => 10 // Home advantage
        ]);

        $awayTeam = new Team([
            'id' => 2,
            'name' => 'Team B',
            'attack' => 80,
            'defense' => 80,
            'goalkeeper' => 80,
            'power' => 80,
            'supporter' => 0 // No supporter bonus
        ]);

        $groupTeams = collect();

        // Simulate many matches to see statistical difference
        $homeWins = 0;
        $awayWins = 0;
        $draws = 0;

        for ($i = 0; $i < 100; $i++) {
            $result = $this->matchEngine->simulateMatch($homeTeam, $awayTeam, $groupTeams, 1);

            if ($result['home_goals'] > $result['away_goals']) {
                $homeWins++;
            } elseif ($result['away_goals'] > $result['home_goals']) {
                $awayWins++;
            } else {
                $draws++;
            }
        }

        // Home team should win more often due to supporter advantage
        $this->assertGreaterThan($awayWins, $homeWins);
    }

    /** @test */
    public function it_calculates_form_bonus_correctly()
    {
        $reflection = new \ReflectionClass($this->matchEngine);
        $method = $reflection->getMethod('getFormBonus');
        $method->setAccessible(true);

        // Mock group team with winning form
        $groupTeams = collect([
            1 => (object) [
                'team_id' => 1,
                'group_id' => 1,
                'form' => 'WWWWW' // 5 wins
            ]
        ]);

        $bonus = $method->invoke($this->matchEngine, $groupTeams, 1, 1);

        // Last 3 wins = 3 * 0.5 = 1.5
        $this->assertEquals(1.5, $bonus);
    }

    /** @test */
    public function it_handles_empty_form()
    {
        $reflection = new \ReflectionClass($this->matchEngine);
        $method = $reflection->getMethod('getFormBonus');
        $method->setAccessible(true);

        $groupTeams = collect([
            1 => (object) [
                'team_id' => 1,
                'group_id' => 1,
                'form' => '' // No form yet
            ]
        ]);

        $bonus = $method->invoke($this->matchEngine, $groupTeams, 1, 1);

        $this->assertEquals(0, $bonus);
    }

    /** @test */
    public function it_handles_mixed_form()
    {
        $reflection = new \ReflectionClass($this->matchEngine);
        $method = $reflection->getMethod('getFormBonus');
        $method->setAccessible(true);

        $groupTeams = collect([
            1 => (object) [
                'team_id' => 1,
                'group_id' => 1,
                'form' => 'WWDLW' // Last 3: DLW
            ]
        ]);

        $bonus = $method->invoke($this->matchEngine, $groupTeams, 1, 1);

        // D=0, L=-0.5, W=+0.5 = 0
        $this->assertEquals(0, $bonus);
    }

    /** @test */
    public function stronger_team_scores_more_on_average()
    {
        $strongTeam = new Team([
            'id' => 1,
            'name' => 'Strong Team',
            'attack' => 95,
            'defense' => 90,
            'goalkeeper' => 90,
            'power' => 95,
            'supporter' => 10
        ]);

        $weakTeam = new Team([
            'id' => 2,
            'name' => 'Weak Team',
            'attack' => 60,
            'defense' => 60,
            'goalkeeper' => 60,
            'power' => 60,
            'supporter' => 0
        ]);

        $groupTeams = collect();

        $strongGoals = 0;
        $weakGoals = 0;

        for ($i = 0; $i < 50; $i++) {
            $result = $this->matchEngine->simulateMatch($strongTeam, $weakTeam, $groupTeams, 1);
            $strongGoals += $result['home_goals'];
            $weakGoals += $result['away_goals'];
        }

        // Strong team should score more on average
        $this->assertGreaterThan($weakGoals, $strongGoals);
    }
}
