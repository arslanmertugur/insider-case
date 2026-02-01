<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\LeagueService;
use App\Models\Team;
use App\Models\Fixture;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LeagueServiceTest extends TestCase
{
    use RefreshDatabase; 

    private LeagueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeagueService();
    }


public function it_calculates_match_results_consistently()
{
    
    $strongTeam = Team::factory()->create(['power' => 95, 'attack' => 95, 'defense' => 90, 'goalkeeper' => 90]);
    $weakTeam = Team::factory()->create(['power' => 40, 'attack' => 40, 'defense' => 30, 'goalkeeper' => 30]);

    $match = new Fixture([
        'home_team_id' => $strongTeam->id,
        'away_team_id' => $weakTeam->id,
        'group_id' => 1
    ]);
    
    $match->setRelation('homeTeam', $strongTeam);
    $match->setRelation('awayTeam', $weakTeam);

    
    $method = new \ReflectionMethod(LeagueService::class, 'calculateMatchResult');
    $method->setAccessible(true); 
    
    
    $result = $method->invoke($this->service, $match, collect());

    
    $this->assertArrayHasKey('home_goals', $result);
    $this->assertArrayHasKey('away_goals', $result);
    $this->assertGreaterThanOrEqual(0, $result['home_goals']);
}

    
    public function it_updates_points_correctly_after_a_win()
    {
        
        $team = Team::factory()->create();
        $groupTeam = \App\Models\GroupTeam::create([
            'group_id' => 1,
            'team_id' => $team->id,
            'points' => 0,
            'played' => 0,
            'won' => 0,
            'form' => ''
        ]);

        $groupTeams = collect([$team->id => collect([$groupTeam])]);

        $method = new \ReflectionMethod(LeagueService::class, 'processStatsInMemory');
        $method->setAccessible(true);
        $method->invoke($this->service, $groupTeams, 1, $team->id, 3, 1);

        
        $updatedStat = $groupTeams[$team->id]->first();
        
        $this->assertEquals(3, $updatedStat->points, "Galibiyet sonrası puan 3 olmalı.");
        $this->assertEquals(1, $updatedStat->played, "Oynanan maç sayısı 1 olmalı.");
        $this->assertEquals(1, $updatedStat->won, "Galibiyet sayısı 1 olmalı.");
        $this->assertEquals('W', $updatedStat->form, "Form 'W' olarak güncellenmeli.");
    }
}