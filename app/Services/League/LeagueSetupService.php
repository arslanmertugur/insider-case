<?php

namespace App\Services\League;

use App\Models\Team;
use App\Models\Group;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Repositories\GroupRepository;
use App\Repositories\FixtureRepository;
use App\Domain\Scheduler\FixtureGenerator;

class LeagueSetupService
{
    private const TEAMS_PER_GROUP = 4;

    protected $groupRepository;
    protected $fixtureRepository;
    protected $fixtureGenerator;

    public function __construct(
        GroupRepository $groupRepository,
        FixtureRepository $fixtureRepository,
        FixtureGenerator $fixtureGenerator
    ) {
        $this->groupRepository = $groupRepository;
        $this->fixtureRepository = $fixtureRepository;
        $this->fixtureGenerator = $fixtureGenerator;
    }

    public function drawGroups(): Collection
    {
        Log::info('Group draw started');

        $allTeams = Team::all();
        $groups = Group::orderBy('name')->get();

        $this->validateDrawPreconditions($allTeams, $groups);
        $result = $this->performDraw($allTeams, $groups);
        $this->saveToDB($result, $groups);

        return $this->groupRepository->getAllGroupsWithTeams();
    }

    public function generateFixtures()
    {
        return DB::transaction(function () {
            $groups = Group::with('groupTeams')->get();
            if ($groups->isEmpty())
                throw new \Exception("Gruplar bulunamadı.");

            $this->fixtureRepository->deleteAll();

            $fixtures = $this->fixtureGenerator->generate($groups);

            $this->fixtureRepository->insertBulk($fixtures);

            return $this->fixtureRepository->getFixturesGroupedByWeek();
        });
    }

    public function resetLeague()
    {
        DB::transaction(function () {
            $this->fixtureRepository->resetAll();
            $this->groupRepository->resetAllStats();
        });
    }

    

    private function validateDrawPreconditions(Collection $allTeams, Collection $groups): void
    {
        $groupCount = $groups->count();
        if ($groupCount === 0)
            throw new \Exception("Hiç grup bulunamadı.");
        $expectedTeamCount = $groupCount * self::TEAMS_PER_GROUP;
        if ($allTeams->count() !== $expectedTeamCount)
            throw new \Exception("Takım sayısı hatalı.");

        
        $countryCounts = $allTeams->groupBy('country')->map->count();
        foreach ($countryCounts as $country => $count) {
            if ($count > $groupCount) {
                throw new \Exception("İmkansız: {$country} ülkesinden fazla takım var.");
            }
        }
    }

    private function performDraw(Collection $allTeams, Collection $groups): array
    {
        $maxAttempts = 100;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $assignments = [];
                $groupNames = $groups->pluck('name')->toArray();

                foreach ($groupNames as $name)
                    $assignments[$name] = [];

                $pots = $allTeams->groupBy('pot');

                $this->drawPot1($pots[1], $groupNames, $assignments);
                $this->drawPotWithCountryPriority($pots[2], $groupNames, $assignments, $allTeams);
                $this->drawPotWithCountryPriority($pots[3], $groupNames, $assignments, $allTeams);
                $this->drawPotWithCountryPriority($pots[4], $groupNames, $assignments, $allTeams);

                return $assignments;

            } catch (\Exception $e) {
                if ($attempt === $maxAttempts)
                    throw $e;
                continue;
            }
        }
        throw new \Exception("Kura çekilemedi.");
    }

    private function drawPot1(Collection $teams, array $groupNames, array &$assignments): void
    {
        $shuffledTeams = $teams->shuffle();
        foreach ($groupNames as $index => $groupName) {
            $assignments[$groupName][] = $shuffledTeams[$index]->id;
        }
    }

    private function drawPotWithCountryPriority(Collection $teams, array $groupNames, array &$assignments, Collection $allTeams): void
    {
        $teamsByCountry = $teams->groupBy('country')->sortByDesc(fn($teams) => $teams->count());

        foreach ($teamsByCountry as $country => $countryTeams) {
            foreach ($countryTeams->shuffle() as $team) {
                $validGroups = $this->findValidGroupsForTeam($team, $assignments, $groupNames, $allTeams);
                if (empty($validGroups))
                    throw new \Exception("Uygun grup bulunamadı!");

                $selectedGroup = $validGroups[array_rand($validGroups)];
                $assignments[$selectedGroup][] = $team->id;
            }
        }
    }

    private function findValidGroupsForTeam(Team $team, array $assignments, array $allGroupNames, Collection $allTeams): array
    {
        $validGroups = [];
        foreach ($allGroupNames as $groupName) {
            if (count($assignments[$groupName]) >= self::TEAMS_PER_GROUP)
                continue;

            
            foreach ($assignments[$groupName] as $existingTeamId) {
                $existingTeam = $allTeams->firstWhere('id', $existingTeamId);
                if ($existingTeam && $existingTeam->pot === $team->pot)
                    continue 2; 
            }

            
            foreach ($assignments[$groupName] as $existingTeamId) {
                $existingTeam = $allTeams->firstWhere('id', $existingTeamId);
                if ($existingTeam && $existingTeam->country === $team->country)
                    continue 2; 
            }

            $validGroups[] = $groupName;
        }
        return $validGroups;
    }

    private function saveToDB(array $assignments, Collection $groups): void
    {
        DB::transaction(function () use ($assignments, $groups) {
            $this->groupRepository->clearGroupAssignments();
            $bulkData = [];
            foreach ($assignments as $groupName => $teamIds) {
                $group = $groups->firstWhere('name', $groupName);
                foreach ($teamIds as $teamId) {
                    $bulkData[] = [
                        'id' => Str::uuid()->toString(),
                        'group_id' => $group->id,
                        'team_id' => $teamId,
                        'played' => 0,
                        'won' => 0,
                        'drawn' => 0,
                        'lost' => 0,
                        'points' => 0,
                        'goals_for' => 0,
                        'goals_against' => 0,
                        'goal_difference' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            $this->groupRepository->saveGroupAssignments($bulkData);
        });
    }
}
