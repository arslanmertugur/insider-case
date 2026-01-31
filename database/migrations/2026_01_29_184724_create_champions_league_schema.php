<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        // 1. Gruplar
        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID
            $table->char('name', 1);
            $table->timestamps();
        });

        // 2. Takımlar
        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID
            $table->string('name', 100);
            $table->string('country', 50)->nullable();
            $table->integer('pot')->default(1);
            $table->integer('power')->default(50);
            $table->integer('attack')->default(50);
            $table->integer('defense')->default(50);
            $table->integer('goalkeeper')->default(50);
            $table->integer('supporter')->default(50);
            $table->timestamps();
        });

        // 3. Grup-Takım İlişkisi
        Schema::create('group_teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignUuid('team_id')->constrained('teams')->onDelete('cascade');
            $table->integer('played')->default(0);
            $table->integer('won')->default(0);
            $table->integer('drawn')->default(0);
            $table->integer('lost')->default(0);
            $table->integer('points')->default(0);
            $table->integer('goals_for')->default(0);
            $table->integer('goals_against')->default(0);
            $table->integer('goal_difference')->default(0);
            $table->string('forms', 100);
            $table->integer('guess')->default(0);
            $table->timestamps();
        });

        // 4. Maçlar
        Schema::create('fixture', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups')->onDelete('cascade');
            $table->integer('week');
            $table->enum('match_day', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            $table->foreignUuid('home_team_id')->constrained('teams');
            $table->foreignUuid('away_team_id')->constrained('teams');
            $table->integer('home_goals')->nullable();
            $table->integer('away_goals')->nullable();
            $table->boolean('played')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fixture');
        Schema::dropIfExists('group_teams');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('groups');
    }
};