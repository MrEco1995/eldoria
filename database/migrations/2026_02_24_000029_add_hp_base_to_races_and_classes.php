<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('races')) {
            Schema::table('races', function (Blueprint $table) {
                if (!Schema::hasColumn('races', 'hp_base')) {
                    $table->unsignedSmallInteger('hp_base')->default(0)->after('description');
                }
            });
        }

        if (Schema::hasTable('character_classes')) {
            Schema::table('character_classes', function (Blueprint $table) {
                if (!Schema::hasColumn('character_classes', 'hp_base')) {
                    $table->unsignedSmallInteger('hp_base')->default(0)->after('description');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('races') && Schema::hasColumn('races', 'hp_base')) {
            Schema::table('races', function (Blueprint $table) {
                $table->dropColumn('hp_base');
            });
        }

        if (Schema::hasTable('character_classes') && Schema::hasColumn('character_classes', 'hp_base')) {
            Schema::table('character_classes', function (Blueprint $table) {
                $table->dropColumn('hp_base');
            });
        }
    }
};
