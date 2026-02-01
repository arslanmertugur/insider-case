<?php

namespace App\Repositories;

use App\Models\Fixture;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class FixtureRepository
{
    public function deleteAll(): void
    {
        DB::table('fixture')->delete();
    }

    public function insertBulk(array $fixtures): void
    {
        DB::table('fixture')->insert($fixtures);
    }

    public function getFixturesGroupedByWeek(): Collection
    {
        return Fixture::with([
            'homeTeam:id,name',
            'awayTeam:id,name',
            'group:id,name'
        ])
            ->orderBy('week')
            ->orderBy('match_day')
            ->get()
            ->groupBy('week');
    }

    public function getFixturesByWeek(int $week): Collection
    {
        return Fixture::with(['homeTeam:id,name', 'awayTeam:id,name'])
            ->where('week', $week)
            ->get();
    }

    public function getNextUnplayedWeek(): ?int
    {
        return DB::table('fixture')->where('played', false)->min('week');
    }

    public function getUnplayedMatchesByWeek(int $week): Collection
    {
        return Fixture::where('week', $week)->where('played', false)->get();
    }

    public function getNextUnplayedMatch(int $week): ?Fixture
    {
        return Fixture::where('week', $week)
            ->where('played', false)
            ->with(['homeTeam', 'awayTeam', 'group'])
            ->first();
    }

    public function countRemainingMatchesInWeek(int $week): int
    {
        return Fixture::where('week', $week)
            ->where('played', false)
            ->count();
    }

    public function findById(string $id): Fixture
    {
        return Fixture::findOrFail($id);
    }

    public function getPlayedMatchesByGroup(int $groupId): Collection
    {
        return Fixture::where('group_id', $groupId)
            ->where('played', true)
            ->orderBy('week')
            ->get();
    }

    public function resetAll(): void
    {
        DB::table('fixture')->update([
            'home_goals' => 0,
            'away_goals' => 0,
            'played' => 0,
        ]);
    }

    public function existsUnplayed(): bool
    {
        return DB::table('fixture')->where('played', false)->exists();
    }
}
