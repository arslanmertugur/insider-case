<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;       // Modellerin doğru yolu
use App\Models\Group;      // Modellerin doğru yolu
use App\Models\GroupTeam;  // Modellerin doğru yolu
use Illuminate\Support\Facades\Schema;

class LeagueSeeder extends Seeder
{
    public function run()
    {
        // Yabancı anahtar kısıtlamalarını geçici olarak kapat (Temizlik yaparken hata almamak için)
        Schema::disableForeignKeyConstraints();

        Team::truncate();
        Group::truncate();
        GroupTeam::truncate();

        Schema::enableForeignKeyConstraints();

        $teams = [
            [
                'name' => 'Manchester City',
                'country' => 'England',
                'pot' => 1,
                'power' => 96,
                'attack' => 95,
                'defense' => 94,
                'goalkeeper' => 92,
                'supporter' => 88
            ],
            [
                'name' => 'Real Madrid',
                'country' => 'Spain',
                'pot' => 1,
                'power' => 95,
                'attack' => 94,
                'defense' => 92,
                'goalkeeper' => 93,
                'supporter' => 95
            ],
            [
                'name' => 'Bayern Munich',
                'country' => 'Germany',
                'pot' => 1,
                'power' => 93,
                'attack' => 93,
                'defense' => 91,
                'goalkeeper' => 90,
                'supporter' => 90
            ],
            [
                'name' => 'Paris Saint-Germain',
                'country' => 'France',
                'pot' => 1,
                'power' => 92,
                'attack' => 94,
                'defense' => 88,
                'goalkeeper' => 89,
                'supporter' => 87
            ],
            [
                'name' => 'Liverpool',
                'country' => 'England',
                'pot' => 1,
                'power' => 91,
                'attack' => 90,
                'defense' => 89,
                'goalkeeper' => 88,
                'supporter' => 92
            ],
            [
                'name' => 'Inter',
                'country' => 'Italy',
                'pot' => 1,
                'power' => 90,
                'attack' => 88,
                'defense' => 91,
                'goalkeeper' => 89,
                'supporter' => 85
            ],
            [
                'name' => 'Barcelona',
                'country' => 'Spain',
                'pot' => 1,
                'power' => 89,
                'attack' => 91,
                'defense' => 85,
                'goalkeeper' => 86,
                'supporter' => 93
            ],
            [
                'name' => 'Chelsea',
                'country' => 'England',
                'pot' => 1,
                'power' => 88,
                'attack' => 87,
                'defense' => 86,
                'goalkeeper' => 85,
                'supporter' => 89
            ],

            // =====================
            // POT 2
            // =====================
            [
                'name' => 'Arsenal',
                'country' => 'England',
                'pot' => 2,
                'power' => 87,
                'attack' => 87,
                'defense' => 86,
                'goalkeeper' => 85,
                'supporter' => 90
            ],
            [
                'name' => 'Atletico Madrid',
                'country' => 'Spain',
                'pot' => 2,
                'power' => 86,
                'attack' => 84,
                'defense' => 88,
                'goalkeeper' => 86,
                'supporter' => 88
            ],
            [
                'name' => 'Borussia Dortmund',
                'country' => 'Germany',
                'pot' => 2,
                'power' => 85,
                'attack' => 86,
                'defense' => 82,
                'goalkeeper' => 83,
                'supporter' => 91
            ],
            [
                'name' => 'RB Leipzig',
                'country' => 'Germany',
                'pot' => 2,
                'power' => 84,
                'attack' => 85,
                'defense' => 83,
                'goalkeeper' => 82,
                'supporter' => 84
            ],
            [
                'name' => 'Napoli',
                'country' => 'Italy',
                'pot' => 2,
                'power' => 83,
                'attack' => 85,
                'defense' => 81,
                'goalkeeper' => 82,
                'supporter' => 83
            ],
            [
                'name' => 'Benfica',
                'country' => 'Portugal',
                'pot' => 2,
                'power' => 82,
                'attack' => 83,
                'defense' => 80,
                'goalkeeper' => 81,
                'supporter' => 86
            ],
            [
                'name' => 'Juventus',
                'country' => 'Italy',
                'pot' => 2,
                'power' => 81,
                'attack' => 80,
                'defense' => 84,
                'goalkeeper' => 83,
                'supporter' => 87
            ],
            [
                'name' => 'Porto',
                'country' => 'Portugal',
                'pot' => 2,
                'power' => 80,
                'attack' => 81,
                'defense' => 79,
                'goalkeeper' => 80,
                'supporter' => 84
            ],

            // =====================
            // POT 3
            // =====================
            [
                'name' => 'PSV Eindhoven',
                'country' => 'Netherlands',
                'pot' => 3,
                'power' => 79,
                'attack' => 80,
                'defense' => 78,
                'goalkeeper' => 79,
                'supporter' => 82
            ],
            [
                'name' => 'Ajax',
                'country' => 'Netherlands',
                'pot' => 3,
                'power' => 78,
                'attack' => 81,
                'defense' => 75,
                'goalkeeper' => 77,
                'supporter' => 88
            ],
            [
                'name' => 'Sporting CP',
                'country' => 'Portugal',
                'pot' => 3,
                'power' => 77,
                'attack' => 78,
                'defense' => 76,
                'goalkeeper' => 77,
                'supporter' => 83
            ],
            [
                'name' => 'Lazio',
                'country' => 'Italy',
                'pot' => 3,
                'power' => 76,
                'attack' => 77,
                'defense' => 75,
                'goalkeeper' => 76,
                'supporter' => 81
            ],
            [
                'name' => 'Sevilla',
                'country' => 'Spain',
                'pot' => 3,
                'power' => 75,
                'attack' => 76,
                'defense' => 74,
                'goalkeeper' => 75,
                'supporter' => 86
            ],
            [
                'name' => 'Shakhtar Donetsk',
                'country' => 'Ukraine',
                'pot' => 3,
                'power' => 74,
                'attack' => 75,
                'defense' => 73,
                'goalkeeper' => 74,
                'supporter' => 80
            ],
            [
                'name' => 'RB Salzburg',
                'country' => 'Austria',
                'pot' => 3,
                'power' => 73,
                'attack' => 74,
                'defense' => 72,
                'goalkeeper' => 73,
                'supporter' => 79
            ],
            [
                'name' => 'Celtic',
                'country' => 'Scotland',
                'pot' => 3,
                'power' => 72,
                'attack' => 73,
                'defense' => 71,
                'goalkeeper' => 72,
                'supporter' => 90
            ],

            // =====================
            // POT 4
            // =====================
            [
                'name' => 'Galatasaray',
                'country' => 'Turkey',
                'pot' => 4,
                'power' => 78,
                'attack' => 79,
                'defense' => 76,
                'goalkeeper' => 77,
                'supporter' => 95
            ],
            [
                'name' => 'Copenhagen',
                'country' => 'Denmark',
                'pot' => 4,
                'power' => 74,
                'attack' => 73,
                'defense' => 75,
                'goalkeeper' => 74,
                'supporter' => 82
            ],
            [
                'name' => 'Young Boys',
                'country' => 'Switzerland',
                'pot' => 4,
                'power' => 73,
                'attack' => 74,
                'defense' => 72,
                'goalkeeper' => 73,
                'supporter' => 78
            ],
            [
                'name' => 'Red Star Belgrade',
                'country' => 'Serbia',
                'pot' => 4,
                'power' => 72,
                'attack' => 73,
                'defense' => 71,
                'goalkeeper' => 72,
                'supporter' => 88
            ],
            [
                'name' => 'Slavia Praha',
                'country' => 'Czech Republic',
                'pot' => 4,
                'power' => 71,
                'attack' => 72,
                'defense' => 70,
                'goalkeeper' => 71,
                'supporter' => 80
            ],
            [
                'name' => 'Qarabag',
                'country' => 'Azerbaijan',
                'pot' => 4,
                'power' => 70,
                'attack' => 69,
                'defense' => 70,
                'goalkeeper' => 71,
                'supporter' => 77
            ],
            [
                'name' => 'Molde',
                'country' => 'Norway',
                'pot' => 4,
                'power' => 69,
                'attack' => 70,
                'defense' => 68,
                'goalkeeper' => 69,
                'supporter' => 76
            ],
            [
                'name' => 'Ludogorets',
                'country' => 'Bulgaria',
                'pot' => 4,
                'power' => 68,
                'attack' => 69,
                'defense' => 67,
                'goalkeeper' => 68,
                'supporter' => 75
            ],
        ];


        foreach ($teams as $teamData) {
            Team::create($teamData);
        }

        foreach (range('A', 'D') as $char) {
            Group::create(['name' => $char]);
        }
    }
}