<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name'];

    // GroupTeam pivot tablosu üzerinden
    public function groupTeams(): HasMany
    {
        return $this->hasMany(GroupTeam::class, 'group_id');
    }

    // Direkt takımlar (belongsToMany ile)
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'group_teams')
            ->withPivot(['played', 'won', 'drawn', 'lost', 'points', 'goals_for', 'goals_against', 'goal_difference', 'guess'])
            ->withTimestamps()
            ->orderByPivot('points', 'desc')
            ->orderByPivot('goal_difference', 'desc');
    }

    // Grubun maçları
    public function matches(): HasMany
    {
        return $this->hasMany(Fixture::class, 'group_id');
    }
}