<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quests')) {
            return;
        }

        Schema::table('quests', function (Blueprint $table) {
            if (!Schema::hasColumn('quests', 'ending_release_label')) {
                $table->string('ending_release_label', 180)->nullable()->after('decision_point');
            }
            if (!Schema::hasColumn('quests', 'ending_capture_label')) {
                $table->string('ending_capture_label', 180)->nullable()->after('ending_release');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('quests')) {
            return;
        }

        Schema::table('quests', function (Blueprint $table) {
            if (Schema::hasColumn('quests', 'ending_release_label')) {
                $table->dropColumn('ending_release_label');
            }
            if (Schema::hasColumn('quests', 'ending_capture_label')) {
                $table->dropColumn('ending_capture_label');
            }
        });
    }
};
