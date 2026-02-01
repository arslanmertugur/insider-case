<?php

namespace App\Services\League;

use App\Repositories\GroupRepository;
use App\Models\GroupTeam;
use App\Models\Group;

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

        if ($teams->max('played') < 4) {
            $this->groupRepository->resetPredictionsForGroup($groupId);
            return;
        }

        $totalPowerScores = 0;
        $predictionData = [];

        foreach ($teams as $team) {
            $remainingGames = 6 - $team->played;
            $strength = $team->team->strength;

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
        foreach ($predictionData as $teamId => $score) {
            $percentage = ($totalPowerScores > 0) ? ($score / $totalPowerScores) * 100 : 0;
            $rounded = (int) round($percentage);

            $currentTotal += $rounded;

            $this->groupRepository->updatePrediction($teamId, $rounded);
        }

        if ($currentTotal !== 100 && $currentTotal > 0) {
            $diff = 100 - $currentTotal;
            $highestTeamId = array_search(max($predictionData), $predictionData);
            $this->groupRepository->incrementGuess($highestTeamId, $diff);
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
