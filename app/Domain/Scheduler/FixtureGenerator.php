<?php

namespace App\Domain\Scheduler;

use Illuminate\Support\Collection;

class FixtureGenerator
{
    public function generate(Collection $groups): array
    {
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

        return $fixtures;
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
}
