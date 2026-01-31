<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fixture extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'fixture';

    protected $fillable = [
        'group_id',
        'week',
        'match_day',
        'home_team_id',
        'away_team_id',
        'home_goals',
        'away_goals',
        'played'
    ];

    // Cast'leri ekle
    protected $casts = [
        'week' => 'integer',
        'home_goals' => 'integer',
        'away_goals' => 'integer',
        'played' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}