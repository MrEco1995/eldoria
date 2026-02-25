<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('party_characters')) {
            return;
        }

        Schema::table('party_characters', function (Blueprint $table) {
            if (!Schema::hasColumn('party_characters', 'hp_max')) {
                $table->unsignedSmallInteger('hp_max')->default(0)->after('talents');
            }
            if (!Schema::hasColumn('party_characters', 'hp_current')) {
                $table->unsignedSmallInteger('hp_current')->default(0)->after('hp_max');
            }
            if (!Schema::hasColumn('party_characters', 'hp_temp')) {
                $table->unsignedSmallInteger('hp_temp')->default(0)->after('hp_current');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('party_characters')) {
            return;
        }

        Schema::table('party_characters', function (Blueprint $table) {
            $drop = [];
            if (Schema::hasColumn('party_characters', 'hp_temp')) $drop[] = 'hp_temp';
            if (Schema::hasColumn('party_characters', 'hp_current')) $drop[] = 'hp_current';
            if (Schema::hasColumn('party_characters', 'hp_max')) $drop[] = 'hp_max';

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
