<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\GroupTeam;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class GroupSeeder extends Seeder
{
    public function run()
    {
        // 1. Sadece Grup ve İlişkileri Temizle (Teams'i elleme)
        Schema::disableForeignKeyConstraints();
        GroupTeam::truncate();
        Group::truncate();
        if (Schema::hasTable('matches')) {
            DB::table('matches')->truncate();
        }
        Schema::enableForeignKeyConstraints();

        // 2. Grupları Oluştur (8 Grup: A-H)
        $groupNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($groupNames as $name) {
            Group::create(['name' => $name]);
        }

        $this->command->info('✅ 8 grup oluşturuldu (A-H)');
        $this->command->info('⚠️  Teams tablosu korundu');
    }
}