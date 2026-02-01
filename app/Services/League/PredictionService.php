<?php

namespace App\Services\League;

use App\Repositories\GroupRepository;
use App\Models\GroupTeam;
use App\Models\Group;
use Illuminate\Support\Collection;

class PredictionService
{
    protected $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    public function calculatePredictions(string|int $groupId): void
    {
        $teams = GroupTeam::with('team')->where('group_id', $groupId)->get();
        $this->updatePredictionsForTeams($teams, $groupId);
    }

    public function calculatePredictionsInMemory(Collection $groupTeams, string|int $groupId): void
    {
        
        $teams = $groupTeams instanceof Collection && $groupTeams->has($groupId) ? $groupTeams : $groupTeams->where('group_id', $groupId);

        
        if ($teams->first() instanceof Collection) {
            
            $teams = $groupTeams->map(function ($t) use ($groupId) {
                return $t instanceof Collection ? $t->firstWhere('group_id', $groupId) : $t;
            })->filter(function ($t) use ($groupId) {
                return $t && $t->group_id == $groupId;
            });
        }

        $this->updatePredictionsForTeams($teams, $groupId, false);
    }

    private function updatePredictionsForTeams(Collection $teams, string|int $groupId, bool $persist = true): void
    {
        if ($teams->max('played') < 4) {
            if ($persist) {
                $this->groupRepository->resetPredictionsForGroup($groupId);
            } else {
                foreach ($teams as $team) {
                    $team->guess = 0;
                }
            }
            return;
        }

        $totalPowerScores = 0;
        $predictionData = [];
        $predictionMap = []; 

        foreach ($teams as $team) {
            $remainingGames = 6 - $team->played;

            
            $strength = $team->team ? $team->team->strength : 0;

            $pointsEffect = pow($team->points, 2);

            $strengthMultiplier = $strength / 10;

            $potential = ($remainingGames * $strengthMultiplier);

            $powerScore = ($pointsEffect + $potential) * $strengthMultiplier;

            $powerScore += ($team->goal_difference * 2);

            $powerScore = max(1, $powerScore);
            $predictionData[$team->id] = $powerScore;
            $totalPowerScores += $powerScore;
        }

        $currentTotal = 0;
        $highestScore = -1;
        $highestTeamId = null;

        foreach ($predictionData as $teamId => $score) {
            $percentage = ($totalPowerScores > 0) ? ($score / $totalPowerScores) * 100 : 0;
            $rounded = (int) round($percentage);

            $currentTotal += $rounded;
            $predictionMap[$teamId] = $rounded;

            if ($score > $highestScore) {
                $highestScore = $score;
                $highestTeamId = $teamId;
            }
        }

        if ($currentTotal !== 100 && $currentTotal > 0 && $highestTeamId) {
            $diff = 100 - $currentTotal;
            $predictionMap[$highestTeamId] += $diff;
        }

        
        foreach ($teams as $team) {
            if (isset($predictionMap[$team->id])) {
                $val = $predictionMap[$team->id];
                if ($persist) {
                    $this->groupRepository->updatePrediction($team->id, $val);
                } else {
                    $team->guess = $val;
                }
            }
        }
    }

    public function getPredictions(): array
    {
        $groups = Group::with(['groupTeams.team'])->get();
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
}
