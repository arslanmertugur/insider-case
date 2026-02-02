<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixtureControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Seed teams
    }

    /** @test */
    public function it_can_draw_groups()
    {
        $response = $this->postJson('/api/draw-groups');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Groups drawn successfully'
            ]);

        // Verify groups were created
        $this->assertDatabaseCount('groups', 4);
        $this->assertDatabaseCount('group_teams', 16);
    }

    /** @test */
    public function it_can_generate_fixtures()
    {
        // First draw groups
        $this->postJson('/api/draw-groups');

        $response = $this->postJson('/api/generate-fixtures');

        $response->assertStatus(200);

        // Verify fixtures were created (4 groups × 6 matches per group = 24 matches)
        $this->assertDatabaseCount('fixtures', 24);
    }

    /** @test */
    public function it_can_play_next_match()
    {
        // Setup
        $this->postJson('/api/draw-groups');
        $this->postJson('/api/generate-fixtures');

        $response = $this->postJson('/api/play-next-match');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'match' => [
                    'id',
                    'home_team_name',
                    'away_team_name',
                    'home_goals',
                    'away_goals',
                    'group',
                    'week'
                ],
                'week',
                'remaining_matches',
                'is_last_match',
                'status'
            ]);

        // Verify match was played
        $this->assertDatabaseHas('fixtures', [
            'played' => true
        ]);
    }

    /** @test */
    public function it_updates_team_statistics_after_match()
    {
        // Setup
        $this->postJson('/api/draw-groups');
        $this->postJson('/api/generate-fixtures');

        // Play a match
        $this->postJson('/api/play-next-match');

        // Verify at least one team has updated stats
        $this->assertDatabaseHas('group_teams', [
            'played' => 1
        ]);
    }

    /** @test */
    public function it_can_get_standings()
    {
        // Setup
        $this->postJson('/api/draw-groups');

        $response = $this->getJson('/api/standings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'Group A' => [
                    '*' => [
                        'team_name',
                        'points',
                        'played',
                        'won',
                        'drawn',
                        'lost',
                        'goal_difference',
                        'guess'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_get_all_fixtures()
    {
        // Setup
        $this->postJson('/api/draw-groups');
        $this->postJson('/api/generate-fixtures');

        $response = $this->getJson('/api/fixtures');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'Group A' => [
                    '1' => [
                        '*' => [
                            'id',
                            'home_team_name',
                            'away_team_name',
                            'home_goals',
                            'away_goals',
                            'played',
                            'week'
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_reset_league()
    {
        // Setup
        $this->postJson('/api/draw-groups');
        $this->postJson('/api/generate-fixtures');

        $response = $this->postJson('/api/reset');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'League reset successfully'
            ]);

        // Verify everything was reset
        $this->assertDatabaseCount('groups', 0);
        $this->assertDatabaseCount('group_teams', 0);
        $this->assertDatabaseCount('fixtures', 0);
    }

    /** @test */
    public function it_calculates_predictions_after_week_4()
    {
        // Setup
        $this->postJson('/api/draw-groups');
        $this->postJson('/api/generate-fixtures');

        // Play 4 weeks (24 matches)
        for ($i = 0; $i < 24; $i++) {
            $this->postJson('/api/play-next-match');
        }

        // Check that predictions are calculated
        $response = $this->getJson('/api/standings');

        $data = $response->json();
        $firstGroup = array_values($data)[0];
        $firstTeam = $firstGroup[0];

        // Predictions should be set (not 0)
        $this->assertGreaterThan(0, $firstTeam['guess']);
    }

    /** @test */
    public function it_returns_error_when_no_matches_to_play()
    {
        // Setup
        $this->postJson('/api/draw-groups');
        $this->postJson('/api/generate-fixtures');

        // Play all matches
        for ($i = 0; $i < 24; $i++) {
            $this->postJson('/api/play-next-match');
        }

        // Try to play another match
        $response = $this->postJson('/api/play-next-match');

        $response->assertStatus(400)
            ->assertJsonStructure([
                'status',
                'message'
            ]);
    }

    /** @test */
    public function it_marks_last_match_of_week_correctly()
    {
        // Setup
        $this->postJson('/api/draw-groups');
        $this->postJson('/api/generate-fixtures');

        // Play 5 matches
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/play-next-match');
            $this->assertFalse($response->json('is_last_match'));
        }

        // 6th match should be last of week
        $response = $this->postJson('/api/play-next-match');
        $this->assertTrue($response->json('is_last_match'));
    }

    /** @test */
    public function predictions_sum_to_100_percent_per_group()
    {
        // Setup
        $this->postJson('/api/draw-groups');
        $this->postJson('/api/generate-fixtures');

        // Play 4 weeks
        for ($i = 0; $i < 24; $i++) {
            $this->postJson('/api/play-next-match');
        }

        $response = $this->getJson('/api/standings');
        $data = $response->json();

        foreach ($data as $groupName => $teams) {
            $total = array_sum(array_column($teams, 'guess'));
            $this->assertEquals(100, $total, "Predictions for {$groupName} should sum to 100");
        }
    }
}
