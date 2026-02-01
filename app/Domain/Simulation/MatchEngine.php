<?php

namespace App\Domain\Simulation;

use App\Models\Team;
use Illuminate\Support\Collection;

class MatchEngine
{
    public function simulateMatch(Team $home, Team $away, Collection $groupTeams, string|int $groupId): array
    {
        $homeForm = $this->getFormBonus($groupTeams, $groupId, $home->id);
        $awayForm = $this->getFormBonus($groupTeams, $groupId, $away->id);

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

    private function generateRealisticGoals(float $xg): int
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

    private function getFormBonus(Collection $groupTeams, string|int $groupId, string|int $teamId): float
    {
        // Handle both Collection structure (from memory) and DB Models
        if (isset($groupTeams[$teamId])) {
            // If it's grouped by team_id
            $teamStats = $groupTeams[$teamId] instanceof Collection
                ? $groupTeams[$teamId]->firstWhere('group_id', $groupId)
                : $groupTeams[$teamId];
        } else {
            // If it's a flat collection
            $teamStats = $groupTeams->firstWhere('team_id', $teamId);
        }

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
}
