<?php

namespace App\Repositories;

use App\Models\Group;
use App\Models\GroupTeam;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GroupRepository
{
    public function getAllGroupsWithTeams(): Collection
    {
        return Group::with(['groupTeams.team:id,name,country,pot'])
            ->get();
    }

    public function getGroupsForStandings(): Collection
    {
        return Group::with([
            'groupTeams' => function ($query) {
                $query->orderBy('points', 'desc')
                    ->orderBy('goal_difference', 'desc');
            },
            'groupTeams.team'
        ])->get();
    }

    public function getTeamRecodsByCountry(): Collection
    {
        return GroupTeam::with('team')->get();
    }

    public function getGroupTeamsByTeamIds(array $teamIds): Collection
    {
        return GroupTeam::whereIn('team_id', $teamIds)->get();
    }

    public function clearGroupAssignments(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        GroupTeam::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function saveGroupAssignments(array $bulkData): void
    {
        GroupTeam::insert($bulkData);
    }

    public function upsertGroupTeams(array $upsertData, array $uniqueBy, array $updateColumns): void
    {
        GroupTeam::upsert($upsertData, $uniqueBy, $updateColumns);
    }

    public function updatePrediction(string|int $teamId, int $guess): void
    {
        GroupTeam::where('id', $teamId)->update(['guess' => $guess]);
    }

    public function resetPredictionsForGroup(string|int $groupId): void
    {
        GroupTeam::where('group_id', $groupId)->update(['guess' => 0]);
    }

    public function incrementGuess(string|int $teamId, int $amount): void
    {
        GroupTeam::where('id', $teamId)->increment('guess', $amount);
    }

    public function getTeamStats(string|int $groupId, string|int $teamId)
    {
        return GroupTeam::where('group_id', $groupId)->where('team_id', $teamId)->first();
    }

    public function resetAllStats(): void
    {
        DB::table('group_teams')->update([
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
            'points' => 0,
            'guess' => 0,
            'form' => ''
        ]);
    }

    public function resetGroupStats(string|int $groupId): void
    {
        GroupTeam::where('group_id', $groupId)->update([
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
    }
}
