<?php

namespace App\Services\League;

use App\Repositories\FixtureRepository;
use App\Repositories\GroupRepository;
use App\Domain\Simulation\MatchEngine;
use Illuminate\Support\Facades\DB;
use App\Services\League\PredictionService;

class MatchService
{
    protected $fixtureRepository;
    protected $groupRepository;
    protected $predictionService;
    protected $matchEngine;

    public function __construct(
        FixtureRepository $fixtureRepository,
        GroupRepository $groupRepository,
        PredictionService $predictionService,
        MatchEngine $matchEngine
    ) {
        $this->fixtureRepository = $fixtureRepository;
        $this->groupRepository = $groupRepository;
        $this->predictionService = $predictionService;
        $this->matchEngine = $matchEngine;
    }

    public function playNextWeek(): array
    {
        $nextWeek = $this->fixtureRepository->getNextUnplayedWeek();
        if (is_null($nextWeek)) {
            throw new \Exception("Oynanacak hafta kalmadı.");
        }

        $matches = $this->fixtureRepository->getUnplayedMatchesByWeek($nextWeek);
        $teamIds = $matches->pluck('home_team_id')->merge($matches->pluck('away_team_id'))->unique()->toArray();
        $groupTeams = $this->groupRepository->getGroupTeamsByTeamIds($teamIds)->groupBy('team_id');

        return DB::transaction(function () use ($matches, $nextWeek, $groupTeams) {
            foreach ($matches as $match) {

                $result = $this->matchEngine->simulateMatch(
                    $match->homeTeam,
                    $match->awayTeam,
                    $groupTeams,
                    $match->group_id
                );


                $match->home_goals = $result['home_goals'];
                $match->away_goals = $result['away_goals'];
                $match->played = true;
                $match->save();


                $this->processStatsInMemory($groupTeams, $match->group_id, $match->home_team_id, $result['home_goals'], $result['away_goals']);
                $this->processStatsInMemory($groupTeams, $match->group_id, $match->away_team_id, $result['away_goals'], $result['home_goals']);
            }

            // Tahminler sadece hafta bittiğinde hesaplanmalı
            // playNextMatch içinde zaten yapılıyor, burada gereksiz

            $this->saveStats($groupTeams);

            return [
                'played_week' => $nextWeek,
                'results' => $this->fixtureRepository->getFixturesByWeek($nextWeek)
            ];
        });
    }

    public function playNextMatch(): array
    {
        $currentWeek = $this->fixtureRepository->getNextUnplayedWeek();
        if (is_null($currentWeek)) {
            throw new \Exception("Oynanacak maç kalmadı.");
        }

        $match = $this->fixtureRepository->getNextUnplayedMatch($currentWeek);
        if (!$match) {
            throw new \Exception("Bu haftada oynanacak maç kalmadı.");
        }

        return DB::transaction(function () use ($match, $currentWeek) {
            $teamIds = [$match->home_team_id, $match->away_team_id];
            $groupTeams = $this->groupRepository->getGroupTeamsByTeamIds($teamIds)->groupBy('team_id');


            $result = $this->matchEngine->simulateMatch(
                $match->homeTeam,
                $match->awayTeam,
                $groupTeams,
                $match->group_id
            );


            $match->home_goals = $result['home_goals'];
            $match->away_goals = $result['away_goals'];
            $match->played = true;
            $match->save();


            $this->processStatsInMemory($groupTeams, $match->group_id, $match->home_team_id, $result['home_goals'], $result['away_goals']);
            $this->processStatsInMemory($groupTeams, $match->group_id, $match->away_team_id, $result['away_goals'], $result['home_goals']);


            $this->saveStats($groupTeams);


            $remainingMatches = $this->fixtureRepository->countRemainingMatchesInWeek($currentWeek);
            $isLastMatch = $remainingMatches === 0;

            if ($isLastMatch) {
                // Hafta bitti - sadece ilgili grubun tahminlerini güncelle
                // Tüm grupları çekmek yerine sadece bu maçın grubunu al
                $affectedGroupTeams = $this->groupRepository->getGroupTeamsByTeamIds(
                    \App\Models\GroupTeam::where('group_id', $match->group_id)->pluck('team_id')->toArray()
                )->groupBy('team_id');

                $this->predictionService->calculatePredictionsInMemory($affectedGroupTeams, $match->group_id);
                $this->saveStats($affectedGroupTeams);
            }

            return [
                'match' => [
                    'id' => $match->id,
                    'home_team_name' => $match->homeTeam->name,
                    'away_team_name' => $match->awayTeam->name,
                    'home_goals' => $match->home_goals,
                    'away_goals' => $match->away_goals,
                    'group' => $match->group->name,
                    'week' => $match->week,
                ],
                'week' => $currentWeek,
                'remaining_matches' => $remainingMatches,
                'is_last_match' => $isLastMatch,
                'status' => 'success'
            ];
        });
    }

    public function playAllWeeks(): array
    {
        $results = [];
        while ($this->fixtureRepository->existsUnplayed()) {
            $results[] = $this->playNextWeek();
        }

        // Tüm haftalar bitti - şimdi tüm gruplar için tahminleri hesapla
        $allGroupTeams = \App\Models\GroupTeam::with('team')->get();
        $groupedByGroup = $allGroupTeams->groupBy('group_id');

        foreach ($groupedByGroup as $groupId => $teams) {
            $this->predictionService->calculatePredictionsInMemory($teams, $groupId);
        }

        // Tahminleri kaydet
        $allGroupTeamsGrouped = $allGroupTeams->groupBy('team_id');
        $this->saveStats($allGroupTeamsGrouped);

        return $results;
    }

    public function updateMatchAndRecalculate($matchId, $homeGoals, $awayGoals): bool
    {
        return DB::transaction(function () use ($matchId, $homeGoals, $awayGoals) {
            $match = $this->fixtureRepository->findById($matchId);

            $match->home_goals = $homeGoals;
            $match->away_goals = $awayGoals;
            $match->played = true;
            $match->save();


            $this->groupRepository->resetGroupStats($match->group_id);


            $playedMatches = $this->fixtureRepository->getPlayedMatchesByGroup($match->group_id);
            $groupTeams = $this->groupRepository->getGroupTeamsByTeamIds(
                \App\Models\GroupTeam::where('group_id', $match->group_id)->pluck('team_id')->toArray()
            )->groupBy('team_id');

            foreach ($playedMatches as $m) {
                $this->processStatsInMemory($groupTeams, $m->group_id, $m->home_team_id, $m->home_goals, $m->away_goals);
                $this->processStatsInMemory($groupTeams, $m->group_id, $m->away_team_id, $m->away_goals, $m->home_goals);
            }

            $this->saveStats($groupTeams);

            return true;
        });
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
            $gt->form = substr(($gt->form ?? '') . ($isWin ? 'W' : ($isDraw ? 'D' : 'L')), -5);
        }
    }

    private function saveStats($groupTeams)
    {
        $upsertData = [];
        foreach ($groupTeams as $collection) {
            foreach ($collection as $gt) {

                $upsertData[] = $gt->attributesToArray();
            }
        }
        $this->groupRepository->upsertGroupTeams($upsertData, ['id'], [
            'played',
            'won',
            'drawn',
            'lost',
            'points',
            'goals_for',
            'goals_against',
            'goal_difference',
            'form',
            'guess'
        ]);
    }
}
