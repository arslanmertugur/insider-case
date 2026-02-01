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


        $homeAttackRating = ($home->attack * 0.8) + ($home->power * 0.8) + ($home->supporter * 0.1) + ($homeForm * 1);
        $awayAttackRating = ($away->attack * 0.8) + ($away->power * 0.8) + ($awayForm * 1);


        $homeDefenseRating = ($home->defense * 0.9) + ($home->goalkeeper * 0.4) + 5;
        $awayDefenseRating = ($away->defense * 0.9) + ($away->goalkeeper * 0.3);


        $homeXG = ($homeAttackRating / max(1, $awayDefenseRating)) * 1.5;
        $awayXG = ($awayAttackRating / max(1, $homeDefenseRating)) * 1.3;

        $homeGoals = $this->generateRealisticGoals($homeXG);
        $awayGoals = $this->generateRealisticGoals($awayXG);

        return ['home_goals' => $homeGoals, 'away_goals' => $awayGoals];
    }

    private function generateRealisticGoals(float $xg): int
    {
        // Poisson dağılımına benzer yaklaşım - O(1) complexity
        // while döngüsü yerine direkt matematiksel hesaplama

        // xG değerine göre beklenen gol sayısını hesapla
        $lambda = min($xg, 6); // Maksimum 6'ya sınırla (gerçekçilik için)

        // Poisson dağılımı kullanarak gol üret
        $L = exp(-$lambda);
        $p = 1.0;
        $k = 0;

        do {
            $k++;
            $p *= (mt_rand() / mt_getrandmax());
        } while ($p > $L && $k < 10); // Maksimum 10 gol

        return max(0, $k - 1);
    }

    private function getFormBonus(Collection $groupTeams, string|int $groupId, string|int $teamId): float
    {

        if (isset($groupTeams[$teamId])) {

            $teamStats = $groupTeams[$teamId] instanceof Collection
                ? $groupTeams[$teamId]->firstWhere('group_id', $groupId)
                : $groupTeams[$teamId];
        } else {

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
