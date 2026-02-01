<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'country',
        'pot',
        'power',
        'attack',
        'defense',
        'goalkeeper',
        'supporter'
    ];

    
    protected $casts = [
        'pot' => 'integer',
        'power' => 'integer',
        'attack' => 'integer',
        'defense' => 'integer',
        'goalkeeper' => 'integer',
        'supporter' => 'integer',
    ];

    
    public function groupStats(): HasOne
    {
        return $this->hasOne(GroupTeam::class, 'team_id');
    }

    
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_teams')
            ->withPivot(['played', 'won', 'drawn', 'lost', 'points', 'goals_for', 'goals_against', 'goal_difference', 'guess'])
            ->withTimestamps();
    }
    public function groupTeams()
    {
        
        return $this->hasMany(GroupTeam::class, 'team_id');
    }
    
    public function homeFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'home_team_id');
    }

    
    public function awayFixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'away_team_id');
    }
}