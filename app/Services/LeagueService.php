<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Group;
use App\Models\GroupTeam;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class LeagueService
{
    private const TEAMS_PER_GROUP = 4;

    /**
     * @return Collection
     * @throws \Exception
     */
    public function drawGroups(): Collection
    {
        Log::info('Group draw started');

        $allTeams = Team::all();
        $groups = Group::orderBy('name')->get();

        $this->validateDrawPreconditions($allTeams, $groups);

        $result = $this->performDraw($allTeams, $groups);

        $this->saveToDB($result, $groups);

        return $this->getGroups();
    }

    private function validateDrawPreconditions(Collection $allTeams, Collection $groups): void
    {
        $groupCount = $groups->count();

        if ($groupCount === 0) {
            throw new \Exception("Hiç grup bulunamadı. Önce grupları oluşturun.");
        }

        $expectedTeamCount = $groupCount * self::TEAMS_PER_GROUP;

        if ($allTeams->count() !== $expectedTeamCount) {
            throw new \Exception(
                "Takım sayısı hatalı. {$expectedTeamCount} takım olmalı, " . $allTeams->count() . " takım var."
            );
        }

        // Ülke kontrolü - aynı ülkeden grup sayısından fazla takım olamaz
        $countryCounts = $allTeams->groupBy('country')->map->count();
        foreach ($countryCounts as $country => $count) {
            if ($count > $groupCount) {
                throw new \Exception(
                    "İmkansız: {$country} ülkesinden {$count} takım var ama sadece {$groupCount} grup var."
                );
            }
        }

        Log::info('Validation passed', [
            'total_teams' => $allTeams->count(),
            'total_groups' => $groupCount
        ]);
    }

    private function performDraw(Collection $allTeams, Collection $groups): array
    {
        $maxAttempts = 100;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                Log::debug("Kura denemesi: {$attempt}");

                $assignments = [];
                $groupNames = $groups->pluck('name')->toArray();

                foreach ($groupNames as $name) {
                    $assignments[$name] = [];
                }

                $pots = $allTeams->groupBy('pot');

                Log::info('Drawing Pot 1');
                $this->drawPot1($pots[1], $groupNames, $assignments);

                Log::info('Drawing Pot 2');
                $this->drawPotWithCountryPriority($pots[2], $groupNames, $assignments, $allTeams);

                Log::info('Drawing Pot 3');
                $this->drawPotWithCountryPriority($pots[3], $groupNames, $assignments, $allTeams);

                Log::info('Drawing Pot 4');
                $this->drawPotWithCountryPriority($pots[4], $groupNames, $assignments, $allTeams);

                Log::info("✅ Kura başarılı! (Deneme: {$attempt})");
                return $assignments;

            } catch (\Exception $e) {
                Log::warning("⚠️  Deneme {$attempt} başarısız: " . $e->getMessage());

                if ($attempt === $maxAttempts) {
                    throw new \Exception("Kura {$maxAttempts} denemede çekilemedi. Son hata: " . $e->getMessage());
                }

                continue;
            }
        }

        throw new \Exception("Kura çekilemedi.");
    }

    private function drawPot1(Collection $teams, array $groupNames, array &$assignments): void
    {
        $shuffledTeams = $teams->shuffle();

        foreach ($groupNames as $index => $groupName) {
            $team = $shuffledTeams[$index];
            $assignments[$groupName][] = $team->id;

            Log::debug("Pot 1: {$team->name} → Grup {$groupName}");
        }
    }

    private function drawPotWithCountryPriority(
        Collection $teams,
        array $groupNames,
        array &$assignments,
        Collection $allTeams
    ): void {
        $teamsByCountry = $teams->groupBy('country')
            ->sortByDesc(fn($teams) => $teams->count());

        foreach ($teamsByCountry as $country => $countryTeams) {
            $shuffledCountryTeams = $countryTeams->shuffle();

            foreach ($shuffledCountryTeams as $team) {
                $validGroups = $this->findValidGroupsForTeam($team, $assignments, $groupNames, $allTeams);

                if (empty($validGroups)) {
                    throw new \Exception(
                        "Pot {$team->pot} - {$team->name} ({$team->country}) için uygun grup bulunamadı!"
                    );
                }

                $selectedGroup = $validGroups[array_rand($validGroups)];
                $assignments[$selectedGroup][] = $team->id;

                Log::debug("Pot {$team->pot}: {$team->name} ({$country}) → Grup {$selectedGroup}");
            }
        }
    }

    private function findValidGroupsForTeam(
        Team $team,
        array $assignments,
        array $allGroupNames,
        Collection $allTeams
    ): array {
        $validGroups = [];

        foreach ($allGroupNames as $groupName) {
            if (count($assignments[$groupName]) >= self::TEAMS_PER_GROUP) {
                continue;
            }
            $hasPotConflict = false;
            foreach ($assignments[$groupName] as $existingTeamId) {
                $existingTeam = $allTeams->firstWhere('id', $existingTeamId);

                if ($existingTeam && $existingTeam->pot === $team->pot) {
                    $hasPotConflict = true;
                    break;
                }
            }

            if ($hasPotConflict) {
                continue;
            }

            $hasCountryConflict = false;
            foreach ($assignments[$groupName] as $existingTeamId) {
                $existingTeam = $allTeams->firstWhere('id', $existingTeamId);

                if ($existingTeam && $existingTeam->country === $team->country) {
                    $hasCountryConflict = true;
                    break;
                }
            }

            if (!$hasCountryConflict) {
                $validGroups[] = $groupName;
            }
        }

        return $validGroups;
    }



    private function saveToDB(array $assignments, Collection $groups): void
    {
        DB::transaction(function () use ($assignments, $groups) {
            $this->clearGroupAssignments();

            $bulkData = [];
            foreach ($assignments as $groupName => $teamIds) {
                $group = $groups->firstWhere('name', $groupName);

                foreach ($teamIds as $teamId) {
                    $bulkData[] = [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
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

            DB::table('group_teams')->insert($bulkData);

            Log::info('Group assignments saved successfully');
        });
    }
    public function clearGroupAssignments(): void
    {
        Schema::disableForeignKeyConstraints();
        GroupTeam::query()->delete();
        Schema::enableForeignKeyConstraints();

        Log::info('Group assignments cleared');
    }

    public function getGroups(): Collection
    {
        return Group::with(['groupTeams.team:id,name,country,pot'])
            ->get()
            ->map(function ($group) {
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
    }

    public function getStandings()
    {
        // 1. group_teams tablosunda 'guess' sütununun modelde 'fillable' olduğundan emin ol.
        return \App\Models\Group::with([
            'groupTeams' => function ($query) {
                // Puan, Averaj ve Atılan Gol (PL kuralları) sırasıyla sıralayalım
                $query->orderBy('points', 'desc')
                    ->orderBy('goal_difference', 'desc');
            },
            'groupTeams.team'
        ])->get()->mapWithKeys(function ($group) {
            return [
                $group->name => $group->groupTeams->map(function ($gt) {
                    return [
                        // team_name'i güvenli bir şekilde alalım
                        'team_name' => $gt->team ? $gt->team->name : 'Unknown',
                        'points' => (int) $gt->points,
                        'played' => (int) $gt->played,
                        'won' => (int) $gt->won,
                        'drawn' => (int) $gt->drawn,
                        'lost' => (int) $gt->lost,
                        'goal_difference' => (int) $gt->goal_difference,
                        // 'guess' değerini integer'a zorlayarak (cast) gönderelim
                        'guess' => (int) ($gt->guess ?? 0),
                    ];
                })
            ];
        });
    }

    public function generateFixtures()
    {
        return DB::transaction(function () {
            $groups = Group::with('groupTeams')->get();
            if ($groups->isEmpty())
                throw new \Exception("Gruplar bulunamadı.");

            DB::table('fixture')->delete();

            $groupSides = $this->assignTvRightsSides($groups);
            $fixtures = [];

            $scheduleMatrix = [
                1 => [[0, 3], [1, 2]],
                2 => [[2, 0], [3, 1]],
                3 => [[0, 1], [2, 3]],
                4 => [[3, 0], [2, 1]],
                5 => [[0, 2], [1, 3]],
                6 => [[1, 0], [3, 2]],
            ];

            foreach ($groups as $group) {
                $teamIds = $group->groupTeams->pluck('team_id')->values();
                $side = $groupSides[$group->id] ?? 'RED';

                foreach ($scheduleMatrix as $week => $matches) {
                    $matchDay = $this->calculateMatchDay($week, $side);
                    foreach ($matches as $match) {
                        $fixtures[] = [
                            'id' => (string) \Illuminate\Support\Str::uuid(),
                            'group_id' => $group->id,
                            'week' => $week,
                            'match_day' => $matchDay,
                            'home_team_id' => $teamIds[$match[0]],
                            'away_team_id' => $teamIds[$match[1]],
                            'played' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            DB::table('fixture')->insert($fixtures);

            return $this->getFixtures();
        });
    }

    public function getFixtures()
    {
        return \App\Models\Fixture::with([
            'homeTeam:id,name',
            'awayTeam:id,name',
            'group:id,name'
        ])
            ->orderBy('week')
            ->orderBy('match_day')
            ->get()
            ->groupBy('week')
            ->map(function ($weekMatches, $week) {
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
    }
    private function assignTvRightsSides($groups)
    {
        $sides = [];

        $allGroupTeams = \App\Models\GroupTeam::with('team')->get();
        $teamsByCountry = $allGroupTeams->groupBy(fn($gt) => $gt->team->country);

        $redCount = 0;
        $blueCount = 0;

        $sortedCountries = $teamsByCountry->sortByDesc(fn($teams) => $teams->count());

        foreach ($sortedCountries as $country => $groupTeamRecords) {
            $records = $groupTeamRecords->sortBy(fn($gt) => $gt->team->pot)->values();

            foreach ($records as $index => $record) {
                $groupId = $record->group_id;

                if (isset($sides[$groupId]))
                    continue;

                if ($index % 2 == 0) {
                    $side = ($redCount <= $blueCount) ? 'RED' : 'BLUE';
                } else {
                    $prevGroupId = $records[$index - 1]->group_id;
                    $side = ($sides[$prevGroupId] == 'RED') ? 'BLUE' : 'RED';
                }

                $sides[$groupId] = $side;

                if ($side == 'RED')
                    $redCount++;
                else
                    $blueCount++;
            }
        }

        return $sides;
    }

    private function calculateMatchDay($week, $side)
    {
        $isRedTuesday = ($week % 2 != 0);

        if ($side == 'RED') {
            return $isRedTuesday ? 'Tuesday' : 'Wednesday';
        } else {
            return $isRedTuesday ? 'Wednesday' : 'Tuesday';
        }
    }

    public function playNextWeek()
    {
        set_time_limit(120);

        $nextWeek = DB::table('fixture')->where('played', false)->min('week');
        if (is_null($nextWeek))
            throw new \Exception("Oynanacak hafta kalmadı.");

        $matches = \App\Models\Fixture::where('week', $nextWeek)->where('played', false)->get();
        $teamIds = $matches->pluck('home_team_id')->merge($matches->pluck('away_team_id'))->unique();

        $groupTeams = \App\Models\GroupTeam::whereIn('team_id', $teamIds)->get()->groupBy('team_id');
        $teams = \App\Models\Team::whereIn('id', $teamIds)->get()->keyBy('id');

        return DB::transaction(function () use ($matches, $nextWeek, $groupTeams, $teams) {
            $fixtureUpdates = [];

            foreach ($matches as $match) {
                $match->setRelation('homeTeam', $teams[$match->home_team_id]);
                $match->setRelation('awayTeam', $teams[$match->away_team_id]);

                $result = $this->calculateMatchResult($match, $groupTeams);
                $match->home_goals = $result['home_goals'];
                $match->away_goals = $result['away_goals'];
                $match->played = true;
                $match->updated_at = now();
                $this->processStatsInMemory($groupTeams, $match->group_id, $match->home_team_id, $result['home_goals'], $result['away_goals']);
                $this->processStatsInMemory($groupTeams, $match->group_id, $match->away_team_id, $result['away_goals'], $result['home_goals']);
            }

            foreach ($matches as $m) {
                DB::table('fixture')->where('id', $m->id)->update([
                    'home_goals' => $m->home_goals,
                    'away_goals' => $m->away_goals,
                    'played' => true,
                    'updated_at' => now()
                ]);
            }

            $upsertData = [];
            foreach ($groupTeams as $teamGroupCollection) {
                foreach ($teamGroupCollection as $gt) {
                    $upsertData[] = $gt->toArray();
                }
            }

            \App\Models\GroupTeam::upsert($upsertData, ['id'], [
                'played',
                'won',
                'drawn',
                'lost',
                'points',
                'goals_for',
                'goals_against',
                'goal_difference',
                'form'
            ]);

            $groups = Group::all();
            foreach ($groups as $group) {
                $this->calculatePredictions($group->id);
            }

            return [
                'played_week' => $nextWeek,
                'results' => $this->getFixturesByWeek($nextWeek)
            ];
        });
    }
    public function calculatePredictions($groupId)
    {
        $teams = GroupTeam::with('team')->where('group_id', $groupId)->get();

        if ($teams->max('played') < 4) {
            GroupTeam::where('group_id', $groupId)->update(['guess' => 0]);
            return;
        }

        $totalPowerScores = 0;
        $predictionData = [];

        foreach ($teams as $team) {
            $remainingGames = 6 - $team->played;
            $strength = $team->team->strength; // Örn: 80, 70, 60...

            // 1. Puan Etkisi (Karesini alarak farkı açıyoruz)
            // 12 puan alanla 9 puan alan arasındaki farkı dramatize eder.
            $pointsEffect = pow($team->points, 2);

            // 2. Güç Katsayısı (Strength artık bir çarpan)
            // Güç farkı doğrudan tüm puanı domine eder.
            $strengthMultiplier = $strength / 10;

            // 3. Potansiyel (Kalan maçlar güçle çarpılır)
            $potential = ($remainingGames * $strengthMultiplier);

            // Formül: (Puanların Karesi + Potansiyel) * Güç Çarpanı + Averaj
            $powerScore = ($pointsEffect + $potential) * $strengthMultiplier;

            // Averajı ekle (zayıf bir etki olarak kalsın ama eşitlikte işe yarsın)
            $powerScore += ($team->goal_difference * 2);

            $powerScore = max(1, $powerScore);
            $predictionData[$team->id] = $powerScore;
            $totalPowerScores += $powerScore;
        }

        // Normalizasyon ve Veritabanı Güncelleme
        $currentTotal = 0;
        foreach ($predictionData as $teamId => $score) {
            $percentage = ($totalPowerScores > 0) ? ($score / $totalPowerScores) * 100 : 0;
            $rounded = (int) round($percentage);

            $currentTotal += $rounded;

            GroupTeam::where('id', $teamId)->update([
                'guess' => $rounded
            ]);
        }

        // Toplamın 100 olmama ihtimaline karşı (99 veya 101) küçük bir düzeltme:
        if ($currentTotal !== 100 && $currentTotal > 0) {
            $diff = 100 - $currentTotal;
            $highestTeamId = array_search(max($predictionData), $predictionData);
            GroupTeam::where('id', $highestTeamId)->increment('guess', $diff);
        }
    }
    private function processStatsInMemory($groupTeams, $groupId, $teamId, $gf, $ga)
    {
        $gt = $groupTeams[$teamId]->firstWhere('group_id', $groupId);
        if ($gt) {
            $isWin = $gf > $ga;
            $isDraw = $gf == $ga;

            $gt->played += 1;
            $gt->won += $isWin ? 1 : 0;
            $gt->drawn += $isDraw ? 1 : 0;
            $gt->lost += (!$isWin && !$isDraw) ? 1 : 0;
            $gt->points += ($isWin ? 3 : ($isDraw ? 1 : 0));
            $gt->goals_for += $gf;
            $gt->goals_against += $ga;
            $gt->goal_difference = $gt->goals_for - $gt->goals_against;
            $gt->form = substr($gt->form . ($isWin ? 'W' : ($isDraw ? 'D' : 'L')), -5);
        }
    }
    private function calculateMatchResult($match, $groupTeams)
    {
        $home = $match->homeTeam;
        $away = $match->awayTeam;

        $homeForm = $this->getFormBonus($groupTeams, $match->group_id, $home->id);
        $awayForm = $this->getFormBonus($groupTeams, $match->group_id, $away->id);
        $homeAttackRating = ($home->attack * 0.6) + ($home->power * 0.6) + ($home->supporter * 0.1) + ($homeForm * 2);
        $awayAttackRating = ($away->attack * 0.6) + ($away->power * 0.6) + ($awayForm * 2);

        $homeDefenseRating = ($home->defense * 0.7) + ($home->goalkeeper * 0.3) + 5;
        $awayDefenseRating = ($away->defense * 0.7) + ($away->goalkeeper * 0.2);

        $homeXG = ($homeAttackRating / max(1, $awayDefenseRating)) * 1.4;
        $awayXG = ($awayAttackRating / max(1, $homeDefenseRating)) * 1.2;

        $homeGoals = $this->generateRealisticGoals($homeXG);
        $awayGoals = $this->generateRealisticGoals($awayXG);

        return ['home_goals' => $homeGoals, 'away_goals' => $awayGoals];
    }

    private function generateRealisticGoals($xg)
    {
        $goals = 0;

        while (true) {
            $probability = $xg / ($goals + 1.5);

            if ((rand(0, 1000) / 1000) < $probability) {
                $goals++;

                if ($goals >= 9)
                    break;
            } else {
                break;
            }
        }

        return $goals;
    }

    private function getFormBonus($groupTeams, $groupId, $teamId)
    {
        $teamStats = $groupTeams[$teamId]->firstWhere('group_id', $groupId);

        if (!$teamStats || empty($teamStats->form))
            return 0;

        $lastThree = substr($teamStats->form, -3);
        $bonus = 0;
        foreach (str_split($lastThree) as $char) {
            if ($char === 'W')
                $bonus += 0.5;
            if ($char === 'L')
                $bonus -= 0.5;
        }
        return $bonus;
    }

    public function getFixturesByWeek(int $week)
    {
        return \App\Models\Fixture::with(['homeTeam:id,name', 'awayTeam:id,name'])
            ->where('week', $week)
            ->get();
    }


    public function getPredictions()
    {
        $groups = \App\Models\Group::with(['groupTeams.team'])->get();
        $predictions = [];

        foreach ($groups as $group) {
            $teams = $group->groupTeams;
            $totalWeight = 0;
            $groupResults = [];

            foreach ($teams as $gt) {
                $weight = ($gt->points * 10) + ($gt->team->power * 0.5) + ($gt->goal_difference * 1);
                $groupResults[] = [
                    'team_id' => $gt->team_id,
                    'team_name' => $gt->team->name,
                    'weight' => max(1, $weight)
                ];
                $totalWeight += max(1, $weight);
            }

            foreach ($groupResults as $res) {
                $predictions[$group->name][] = [
                    'team_name' => $res['team_name'],
                    'probability' => round(($res['weight'] / $totalWeight) * 100)
                ];
            }
        }

        return $predictions;
    }


    public function playAllWeeks()
    {
        $results = [];
        while (DB::table('fixture')->where('played', false)->exists()) {
            $results[] = $this->playNextWeek();
        }
        return $results;
    }

    public function updateMatchAndRecalculate($matchId, $homeGoals, $awayGoals)
    {
        return DB::transaction(function () use ($matchId, $homeGoals, $awayGoals) {

            $match = \App\Models\Fixture::findOrFail($matchId);
            $match->update([
                'home_goals' => $homeGoals,
                'away_goals' => $awayGoals,
                'played' => true
            ]);

            \App\Models\GroupTeam::where('group_id', $match->group_id)->update([
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'points' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'form' => ''
            ]);

            $playedMatches = \App\Models\Fixture::where('group_id', $match->group_id)
                ->where('played', true)
                ->orderBy('week')
                ->get();

            $groupTeams = \App\Models\GroupTeam::where('group_id', $match->group_id)->get()->groupBy('team_id');

            foreach ($playedMatches as $m) {
                $this->processStatsInMemory($groupTeams, $m->group_id, $m->home_team_id, $m->home_goals, $m->away_goals);
                $this->processStatsInMemory($groupTeams, $m->group_id, $m->away_team_id, $m->away_goals, $m->home_goals);
            }

            $upsertData = [];
            foreach ($groupTeams as $collection) {
                foreach ($collection as $gt) {
                    $upsertData[] = $gt->toArray();
                }
            }
            \App\Models\GroupTeam::upsert($upsertData, ['id'], ['played', 'won', 'points', 'goals_for', 'goals_against', 'goal_difference', 'form']);

            return true;
        });
    }
}